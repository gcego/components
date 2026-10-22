<?php

declare(strict_types=1);

namespace Gcego\Components\Live;

use Gcego\Components\Component;
use Gcego\Components\Contracts\ComponentInterface;
use Gcego\Components\Contracts\HoldsState;
use Gcego\Components\Evaluation\Computed;
use Gcego\Components\Evaluation\EvaluationContext;
use Gcego\Components\Evaluation\Evaluator;
use Gcego\Components\Evaluation\State;
use Gcego\Components\Facades\Components;
use Gcego\Components\Support\Presentation;
use Gcego\Components\Traversal\ComponentWalker;
use Gcego\Components\Trees\TreeNode;
use Illuminate\Support\Arr;

/**
 * An immutable schema brought to life over mutable state.
 *
 *     $live = Components::live($schema, ['type' => 'person'], ['operation' => 'create']);
 *
 *     $live->isLive('type');               // true: some closure reads it, no ->live() needed
 *     $changes = $live->set('type', 'business');
 *     $changes->components;                // exactly the components to redraw
 *     $live->dehydrate();                  // visible, dehydrated field values only
 *     $live->toArray();                    // a snapshot any frontend can render
 *
 * Construction fills missing field defaults and resolves the tree. Every
 * closure runs while the state records what it reads, so the dependency
 * graph is discovered rather than declared. An update resolves again only
 * the components that read a written state path or hold one; the rest of
 * the tree is reused.
 */
final class LiveSchema
{
    private ResolvedComponent $root;

    /**
     * @var array<string, ResolvedComponent>
     */
    private array $index = [];

    /**
     * Visible state holders by state path.
     *
     * @var array<string, list<ResolvedComponent>>
     */
    private array $fields = [];

    /**
     * Component tree paths by the state path they read.
     *
     * @var array<string, list<string>>
     */
    private array $readers = [];

    /**
     * Tree nodes by component path, so updates can resolve one node again.
     *
     * @var array<string, TreeNode>
     */
    private array $nodes = [];

    /**
     * What each resolved component looked like, to report what an update changed.
     *
     * @var array<string, string>
     */
    private array $fingerprints = [];

    public function __construct(
        private readonly ComponentInterface $schema,
        private readonly State $state,
        private readonly EvaluationContext $context,
        private readonly ComponentWalker $walker,
        private readonly Evaluator $evaluator,
    ) {
        Components::getFacadeRoot();

        $this->fill($this->walker->rootNode($this->schema), '');
        $this->refresh();
    }

    public function schema(): ComponentInterface
    {
        return $this->schema;
    }

    public function state(): State
    {
        return $this->state;
    }

    public function root(): ResolvedComponent
    {
        return $this->root;
    }

    /**
     * The resolved component at a tree path ("#0.account.email").
     */
    public function at(string $componentPath): ?ResolvedComponent
    {
        return $this->index[$componentPath] ?? null;
    }

    /**
     * The first visible state holder at a state path ("address.city").
     */
    public function field(string $statePath): ?ResolvedComponent
    {
        return $this->fields[$statePath][0] ?? null;
    }

    /**
     * Whether changing this state path can change the schema: a field
     * declared live(), or a value some closure reads (or a parent or child
     * path of one).
     */
    public function isLive(string $statePath): bool
    {
        foreach ($this->fields[$statePath] ?? [] as $field) {
            if ($field->get('live') === true) {
                return true;
            }
        }

        return $this->dependentsOf($statePath) !== [];
    }

    /**
     * State paths of the visible fields that are live.
     *
     * @return list<string>
     */
    public function livePaths(): array
    {
        return array_values(array_filter(array_map(strval(...), array_keys($this->fields)), $this->isLive(...)));
    }

    /**
     * Tree paths of the components whose closures read this state path.
     *
     * @return list<string>
     */
    public function dependentsOf(string $statePath): array
    {
        $dependents = [];

        foreach ($this->readers as $read => $components) {
            $read = (string) $read;

            if (self::related($read, $statePath)) {
                array_push($dependents, ...$components);
            }
        }

        return array_values(array_unique($dependents));
    }

    /**
     * Update one value as a user would: write it, run the afterStateUpdated
     * hooks of the visible fields at that path (with $state and $old), then
     * resolve the affected components again and report what changed.
     *
     * Affected components are those whose closures read a written path (or
     * a parent or child path of one) and the state holders of written
     * paths. A closure that reads no state is not run again.
     */
    public function set(string $statePath, mixed $value): Changes
    {
        $before = $this->fingerprints;
        $old = $this->state->peek($statePath);

        $recording = $this->state->record(function () use ($statePath, $value, $old): void {
            $this->state->set($statePath, $value);

            foreach ($this->fields[$statePath] ?? [] as $field) {
                $hook = $field->component instanceof Component ? $field->component->get('afterStateUpdated') : null;

                if ($hook instanceof Computed) {
                    $this->evaluator->evaluate(
                        $hook,
                        $this->contextFor($field->containerPath)->with('state', $value)->with('old', $old),
                        $field->component,
                        sprintf('afterStateUpdated on component "%s" (%s)', $field->type(), $field->path),
                    );
                }
            }
        });

        $writes = array_values(array_unique([$statePath, ...$recording['writes']]));
        $this->update($writes);
        $changed = [];

        foreach (array_keys($before + $this->fingerprints) as $path) {
            if (($before[$path] ?? null) !== ($this->fingerprints[$path] ?? null)) {
                $changed[] = (string) $path;
            }
        }

        $order = array_flip(array_keys($this->index));
        usort($changed, static fn (string $a, string $b): int => ($order[$a] ?? PHP_INT_MAX) <=> ($order[$b] ?? PHP_INT_MAX));

        return new Changes($writes, $changed);
    }

    /**
     * Write several values, merging what changed.
     *
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): Changes
    {
        $statePaths = [];
        $components = [];

        foreach ($values as $path => $value) {
            $changes = $this->set($path, $value);
            array_push($statePaths, ...$changes->statePaths);
            array_push($components, ...$changes->components);
        }

        $order = array_flip(array_keys($this->index));
        $components = array_values(array_unique($components));
        usort($components, static fn (string $a, string $b): int => ($order[$a] ?? PHP_INT_MAX) <=> ($order[$b] ?? PHP_INT_MAX));

        return new Changes(array_values(array_unique($statePaths)), $components);
    }

    /**
     * The values to submit: visible state holders that are not
     * dehydrated(false), nested by state path. Hidden fields, and fields in
     * hidden containers, are never included.
     *
     * @return array<array-key, mixed>
     */
    public function dehydrate(): array
    {
        return $this->visibleState(dehydratedOnly: true);
    }

    /**
     * A renderer-neutral snapshot: the values of the visible state holders
     * (including dehydrated(false) ones, which a client still displays) and
     * the resolved tree with normalized properties, live flags and
     * dependencies. Hidden fields, fields in hidden containers and state no
     * field holds (such as extra record attributes) are left out, so the
     * snapshot is safe to send to the client. The full state stays available
     * through state().
     *
     * @return array{state: array<array-key, mixed>, components: array<string, mixed>}
     */
    public function toArray(): array
    {
        return ['state' => $this->visibleState(dehydratedOnly: false), 'components' => $this->export($this->root)];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function visibleState(bool $dehydratedOnly): array
    {
        $data = [];

        foreach ($this->index as $component) {
            if (! $component->holdsState() || $component->hidden || $component->statePath === null) {
                continue;
            }

            if ($dehydratedOnly && $component->get('dehydrated') === false) {
                continue;
            }

            Arr::set($data, $component->statePath, $this->state->peek($component->statePath));
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function export(ResolvedComponent $component): array
    {
        return [
            'type' => $component->type(),
            'path' => $component->path,
            'name' => $component->name(),
            'label' => $component->label,
            'statePath' => $component->statePath,
            'hidden' => $component->hidden,
            'live' => $component->holdsState() && $component->statePath !== null && $this->isLive($component->statePath),
            'dependsOn' => $component->dependencies,
            'properties' => Components::normalizeValue($component->properties, $component->path),
            'children' => array_map($this->export(...), $component->children),
        ];
    }

    private function refresh(): void
    {
        $this->nodes = [];
        $this->root = $this->resolve($this->walker->rootNode($this->schema), '');
        $this->reindex(array_keys($this->nodes));
    }

    /**
     * Resolve again the components affected by the written state paths,
     * reusing every other resolved component.
     *
     * @param  list<string>  $writes
     */
    private function update(array $writes): void
    {
        $affected = [];

        foreach ($writes as $write) {
            foreach ($this->dependentsOf($write) as $path) {
                $affected[$path] = true;
            }
        }

        foreach ($this->index as $path => $component) {
            if ($component->statePath !== null && array_filter($writes, static fn (string $write): bool => self::related($write, (string) $component->statePath)) !== []) {
                $affected[(string) $path] = true;
            }
        }

        if ($affected === []) {
            return;
        }

        $ancestors = [];

        foreach (array_keys($affected) as $path) {
            for ($parent = $this->index[$path]->parentPath ?? null; $parent !== null; $parent = $this->index[$parent]->parentPath ?? null) {
                $ancestors[$parent] = true;
            }
        }

        $fresh = [];
        $this->root = $this->rebuild($this->root, $affected, $ancestors, $fresh);
        $this->reindex($fresh);
    }

    /**
     * @param  array<string, true>  $affected
     * @param  array<string, true>  $ancestors
     * @param  list<string>  $fresh  Paths resolved again, collected for fingerprinting.
     */
    private function rebuild(ResolvedComponent $resolved, array $affected, array $ancestors, array &$fresh): ResolvedComponent
    {
        $path = $resolved->path;

        if (! isset($affected[$path]) && ! isset($ancestors[$path])) {
            return $resolved;
        }

        $node = $this->nodes[$path];

        if (! isset($affected[$path])) {
            return $resolved->withChildren($this->rebuildChildren($resolved, $affected, $ancestors, $fresh));
        }

        $evaluated = $this->resolveOwn($node, $resolved->containerPath);
        $fresh[] = $path;

        if ($evaluated->hidden) {
            $children = [];
        } elseif ($resolved->hidden) {
            $children = [];

            foreach ($this->walker->childNodes($node) as $child) {
                $before = array_keys($this->nodes);
                $children[] = $this->resolve($child, $evaluated->childContainer, $path);
                array_push($fresh, ...array_diff(array_keys($this->nodes), $before));
            }
        } else {
            $children = $this->rebuildChildren($resolved, $affected, $ancestors, $fresh);
        }

        return $evaluated->toResolved($children);
    }

    /**
     * @param  array<string, true>  $affected
     * @param  array<string, true>  $ancestors
     * @param  list<string>  $fresh
     * @return list<ResolvedComponent>
     */
    private function rebuildChildren(ResolvedComponent $resolved, array $affected, array $ancestors, array &$fresh): array
    {
        $children = [];

        foreach ($resolved->children as $child) {
            $children[] = $this->rebuild($child, $affected, $ancestors, $fresh);
        }

        return $children;
    }

    /**
     * Rebuild the lookup maps from the resolved tree and fingerprint the given paths.
     *
     * @param  list<string>  $fresh
     */
    private function reindex(array $fresh): void
    {
        $this->index = self::preOrder($this->root);
        $this->nodes = array_intersect_key($this->nodes, $this->index);
        $this->fields = [];
        $this->readers = [];

        foreach ($this->index as $path => $component) {
            foreach ($component->dependencies as $read) {
                $this->readers[$read][] = (string) $path;
            }

            if ($component->statePath !== null && ! $component->hidden) {
                $this->fields[$component->statePath][] = $component;
            }
        }

        $this->fingerprints = array_intersect_key($this->fingerprints, $this->index);

        foreach ($fresh as $path) {
            if (isset($this->index[$path])) {
                $this->fingerprints[$path] = $this->fingerprint($this->index[$path]);
            }
        }
    }

    private function fill(TreeNode $node, string $containerPath): void
    {
        [$statePath, $childContainer] = $this->paths($node->component, $containerPath);
        $component = $node->component;

        if ($statePath !== null && $component instanceof Component && ! $this->state->has($statePath)) {
            $this->state->set($statePath, $component->evaluateProperty('default', $this->contextFor($containerPath)));
        }

        foreach ($this->walker->childNodes($node) as $child) {
            $this->fill($child, $childContainer);
        }
    }

    private function resolve(TreeNode $node, string $containerPath, ?string $parentPath = null): ResolvedComponent
    {
        $evaluated = $this->resolveOwn($node, $containerPath, $parentPath);
        $children = [];

        if (! $evaluated->hidden) {
            foreach ($this->walker->childNodes($node) as $child) {
                $children[] = $this->resolve($child, $evaluated->childContainer, $evaluated->path);
            }
        }

        return $evaluated->toResolved($children);
    }

    /**
     * Evaluate one component, without its children.
     */
    private function resolveOwn(TreeNode $node, string $containerPath, ?string $parentPath = null): Evaluated
    {
        $component = $node->component;
        [$statePath, $childContainer] = $this->paths($component, $containerPath);
        $context = $this->contextFor($containerPath);

        if ($statePath !== null) {
            $context = $context->with('state', $this->state->peek($statePath));
        }

        $recording = $this->state->record(fn (): array => $this->evaluate($component, $context));
        $properties = $recording['result'];
        $path = $node->path->toString();
        $name = $properties['name'] ?? null;
        $this->nodes[$path] = $node;

        return new Evaluated(
            component: $component,
            path: $path,
            parentPath: $parentPath ?? $this->index[$path]->parentPath ?? null,
            containerPath: $containerPath,
            childContainer: $childContainer,
            statePath: $statePath,
            properties: $properties,
            hidden: Presentation::isHidden($properties, $context->operation()),
            label: Presentation::label($properties['label'] ?? null, is_string($name) ? $name : null),
            dependencies: array_values(array_diff($recording['reads'], [$statePath ?? ''])),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluate(ComponentInterface $component, EvaluationContext $context): array
    {
        if (! $component instanceof Component) {
            return [];
        }

        $definition = $component::definition();
        $properties = [];

        foreach (array_keys($component->effectiveProperties()) as $key) {
            $key = (string) $key;

            if ($key === 'default' || $definition->property($key)?->isCallback() === true) {
                continue;
            }

            $properties[$key] = $component->evaluateProperty($key, $context);
        }

        return $properties;
    }

    /**
     * The component's own state path (for state holders) and the container
     * path its children use.
     *
     * @return array{0: ?string, 1: string}
     */
    private function paths(ComponentInterface $component, string $containerPath): array
    {
        if (! $component instanceof Component) {
            return [null, $containerPath];
        }

        $segment = $component->evaluateProperty('statePath', $this->context);
        $segment = is_string($segment) && $segment !== '' ? $segment : null;

        if ($component instanceof HoldsState) {
            $own = $segment ?? $component->name();

            return [$own === null ? null : self::join($containerPath, $own), $containerPath];
        }

        return [null, $segment === null ? $containerPath : self::join($containerPath, $segment)];
    }

    private function contextFor(string $containerPath): EvaluationContext
    {
        return $this->context->withState($this->state, $containerPath);
    }

    private function fingerprint(ResolvedComponent $component): string
    {
        return md5(serialize([
            $component->hidden,
            $component->label,
            Components::normalizeValue($component->properties, $component->path),
            $component->statePath === null ? null : $this->state->peek($component->statePath),
            count($component->children),
        ]));
    }

    /**
     * @return array<string, ResolvedComponent>
     */
    private static function preOrder(ResolvedComponent $component): array
    {
        $index = [$component->path => $component];

        foreach ($component->children as $child) {
            $index += self::preOrder($child);
        }

        return $index;
    }

    /**
     * Whether two state paths are the same, or one contains the other.
     */
    private static function related(string $a, string $b): bool
    {
        return $a === $b || str_starts_with($a, $b.'.') || str_starts_with($b, $a.'.');
    }

    private static function join(string $containerPath, string $segment): string
    {
        return $containerPath === '' ? $segment : $containerPath.'.'.$segment;
    }
}
