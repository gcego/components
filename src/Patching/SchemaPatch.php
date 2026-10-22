<?php

declare(strict_types=1);

namespace Gcego\Components\Patching;

use Closure;
use Gcego\Components\Building\ComponentBuilder;
use Gcego\Components\Component;
use Gcego\Components\Contracts\Buildable;
use Gcego\Components\Contracts\ComponentInterface;
use Gcego\Components\Contracts\DeclarativeComponent;
use Gcego\Components\Contracts\Definition;
use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Exceptions\PatchException;
use Gcego\Components\Facades\Components;
use Gcego\Components\Traversal\ComponentWalker;
use Gcego\Components\Trees\TreeNode;

/**
 * Immutable edits to a built schema, so one module can extend another's
 * schema without copying it:
 *
 *     Components::patch($schema)
 *         ->insertAfter('account.email', TextInput::make(Name::make('phone')))
 *         ->modify('email', Required::make())
 *         ->remove('legacy_code')
 *         ->apply();
 *
 * A selector is a dot-separated chain of component names matched against
 * the end of each component's tree path: "email" matches every component
 * named email, "account.email" only the one inside "account". Use "name#1"
 * for a repeated sibling. Every selector must match, or the patch fails
 * with a suggestion. The root itself cannot be targeted.
 *
 * Every container whose children change must accept them: a child type the
 * container refuses, or a child count outside its limits, fails the patch
 * with the container's path. Declarative components are also checked as
 * make() checks them (required properties, constraints, unique ids).
 */
final class SchemaPatch
{
    /**
     * @var list<array{operation: string, selector: string, payload: mixed}>
     */
    private array $operations = [];

    public function __construct(private readonly ComponentInterface $schema)
    {
        Components::getFacadeRoot();
    }

    /**
     * Change matching components.
     *
     * Declarative components take typed definitions, applied as an immutable
     * copy: ->modify('email', Required::make(), HelpText::make('Work address')).
     * Engine components of framework packages may instead take a callback that
     * receives a builder holding the component's properties, children and
     * configuration nodes.
     *
     * @param  Definition|(Closure(ComponentBuilder): (ComponentBuilder|ComponentInterface))  $change
     */
    public function modify(string $selector, Definition|Closure $change, Definition ...$definitions): self
    {
        if ($change instanceof Closure && $definitions !== []) {
            throw PatchException::invalidModification($selector);
        }

        return $this->with('modify', $selector, $change instanceof Closure ? $change : [$change, ...$definitions]);
    }

    public function remove(string $selector): self
    {
        return $this->with('remove', $selector, null);
    }

    public function insertBefore(string $selector, ComponentInterface|Buildable ...$components): self
    {
        return $this->with('insertBefore', $selector, $components);
    }

    public function insertAfter(string $selector, ComponentInterface|Buildable ...$components): self
    {
        return $this->with('insertAfter', $selector, $components);
    }

    /**
     * Add children at the end of matching containers.
     */
    public function append(string $selector, ComponentInterface|Buildable ...$components): self
    {
        return $this->with('append', $selector, $components);
    }

    /**
     * Add children at the start of matching containers.
     */
    public function prepend(string $selector, ComponentInterface|Buildable ...$components): self
    {
        return $this->with('prepend', $selector, $components);
    }

    /**
     * Build the patched schema. The original is never changed.
     */
    public function apply(): ComponentInterface
    {
        $walker = Components::getFacadeRoot()->walker();
        $root = $walker->rootNode($this->schema);
        $paths = [];

        foreach ($walker->preOrder($this->schema) as $node) {
            $paths[] = $node->path->segments();
        }

        foreach ($this->operations as $operation) {
            if ($operation['selector'] === '') {
                throw PatchException::rootOperation($operation['operation']);
            }

            $matched = array_filter(array_slice($paths, 1), static fn (array $segments): bool => self::matches($segments, $operation['selector']));

            if ($matched !== []) {
                continue;
            }

            if ($operation['selector'] === ($paths[0][0] ?? null)) {
                throw PatchException::rootOperation($operation['operation']);
            }

            throw PatchException::noMatch($operation['selector'], self::suggest($operation['selector'], array_slice($paths, 1)));
        }

        $patched = $this->rebuild($walker, $root, true);

        return $patched[0];
    }

    /**
     * @return list<ComponentInterface>
     */
    private function rebuild(ComponentWalker $walker, TreeNode $node, bool $isRoot = false): array
    {
        $component = $node->component;
        $original = $component->children()->all();
        $children = [];

        foreach ($walker->childNodes($node) as $child) {
            array_push($children, ...$this->rebuild($walker, $child));
        }

        if ($children !== $original) {
            $component = $this->copied($node, fn (): Component => $this->copyable($component, $node, 'patch')->withChildren(...$children), $children);
        }

        $before = [];
        $after = [];
        $removed = false;
        $segments = $node->path->segments();

        foreach ($this->operations as $operation) {
            if ($isRoot || ! self::matches($segments, $operation['selector'])) {
                continue;
            }

            $payload = $operation['payload'];
            $selector = $operation['selector'];
            $built = in_array($operation['operation'], ['insertBefore', 'insertAfter', 'append', 'prepend'], true) ? self::built($payload) : [];

            match ($operation['operation']) {
                'modify' => $component = $this->copied($node, fn (): ComponentInterface => $this->modified($component, $node, $selector, $payload)),
                'remove' => $removed = true,
                'insertBefore' => array_push($before, ...$built),
                'insertAfter' => array_push($after, ...$built),
                'append' => $component = $this->copied($node, fn (): Component => $this->copyable($component, $node, 'append')->withAppendedChildren(...$built), [...$component->children()->all(), ...$built]),
                'prepend' => $component = $this->copied($node, fn (): Component => $this->copyable($component, $node, 'prepend')->withChildren(...$built, ...$component->children()->all()), [...$built, ...$component->children()->all()]),
                default => null,
            };
        }

        return [...$before, ...($removed ? [] : [$component]), ...$after];
    }

    /**
     * Run one copy of the component at $node, checking the children it ends
     * up with against its schema and reporting failures with its path.
     *
     * @param  Closure(): ComponentInterface  $copy
     * @param  list<ComponentInterface>|null  $children  The children the copy receives, when they change.
     */
    private function copied(TreeNode $node, Closure $copy, ?array $children = null): ComponentInterface
    {
        if ($children !== null) {
            $definition = $node->component::definition();

            foreach ($children as $child) {
                if (! $definition->allowsChild($child)) {
                    throw PatchException::childNotAccepted($node->path->toString(), $child->type());
                }
            }

            $count = count($children);

            if ($count < $definition->minChildren || ($definition->maxChildren !== null && $count > $definition->maxChildren)) {
                throw PatchException::childCount($node->path->toString(), $count, $definition->minChildren, $definition->maxChildren);
            }
        }

        try {
            return $copy();
        } catch (InvalidDefinitionException $exception) {
            throw PatchException::invalidResult($node->path->toString(), $exception);
        }
    }

    private function modified(ComponentInterface $component, TreeNode $node, string $selector, mixed $callback): ComponentInterface
    {
        $component = $this->copyable($component, $node, 'modify');

        if (is_array($callback)) {
            if (! $component instanceof DeclarativeComponent) {
                throw PatchException::invalidModification($selector);
            }

            return $component->withDefinitions(...array_filter($callback, static fn (mixed $definition): bool => $definition instanceof Definition));
        }

        if (! $callback instanceof Closure || $component instanceof DeclarativeComponent) {
            throw PatchException::invalidModification($selector);
        }

        $builder = Components::define($component::class, [
            ...array_values($component->properties()->all()),
            ...$component->children()->all(),
            ...$component->configurations()->all(),
        ]);

        $result = $callback($builder);

        if ($result instanceof Buildable) {
            $result = $result->build();
        }

        if (! $result instanceof ComponentInterface) {
            throw PatchException::invalidModification($selector);
        }

        return $result;
    }

    private function copyable(ComponentInterface $component, TreeNode $node, string $operation): Component
    {
        if (! $component instanceof Component) {
            throw PatchException::notAComponent($node->path->toString(), $operation);
        }

        return $component;
    }

    /**
     * @return list<ComponentInterface>
     */
    private static function built(mixed $components): array
    {
        $built = [];

        foreach (is_array($components) ? $components : [] as $component) {
            if ($component instanceof Buildable) {
                $built[] = $component->build();
            } elseif ($component instanceof ComponentInterface) {
                $built[] = $component;
            }
        }

        return $built;
    }

    private function with(string $operation, string $selector, mixed $payload): self
    {
        $copy = clone $this;
        $copy->operations[] = ['operation' => $operation, 'selector' => trim($selector, '.'), 'payload' => $payload];

        return $copy;
    }

    /**
     * @param  list<string>  $segments  The node's tree path segments, root first.
     */
    private static function matches(array $segments, string $selector): bool
    {
        $wanted = explode('.', $selector);
        $tail = array_slice(array_slice($segments, 1), -count($wanted));

        if (count($tail) !== count($wanted)) {
            return false;
        }

        foreach ($wanted as $index => $segment) {
            $actual = $tail[$index];

            if ($actual !== $segment && (str_contains($segment, '#') || preg_match('/\A'.preg_quote($segment, '/').'#\d+\z/', $actual) !== 1)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<list<string>>  $paths
     */
    private static function suggest(string $selector, array $paths): ?string
    {
        $count = count(explode('.', $selector));
        $best = null;
        $bestDistance = 4;

        $wantsIndex = str_contains($selector, '#');

        foreach ($paths as $segments) {
            $candidate = implode('.', array_slice(array_slice($segments, 1), -$count));

            if (! $wantsIndex && str_contains($candidate, '#')) {
                continue;
            }

            $distance = levenshtein(strtolower($selector), strtolower($candidate));

            if ($candidate !== '' && $distance < $bestDistance) {
                $best = $candidate;
                $bestDistance = $distance;
            }
        }

        return $best;
    }
}
