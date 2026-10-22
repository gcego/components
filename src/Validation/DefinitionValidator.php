<?php

declare(strict_types=1);

namespace Gcego\Components\Validation;

use Gcego\Components\Component;
use Gcego\Components\Contracts\ComponentInterface;
use Gcego\Components\Contracts\ConfigurationNode;
use Gcego\Components\Contracts\Definition;
use Gcego\Components\Contracts\RuntimeValue;
use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Properties\Property;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Support\SubtreeIds;

/**
 * Checks typed definitions against the schema of the component receiving them.
 *
 * Every property a definition produces must be declared by the component (or
 * the component must allow unknown properties) and must satisfy the declared
 * type, allowed values and rule; an explicit null is accepted when the
 * property is nullable, as validate() accepts it. Every child and
 * configuration node must be accepted by the component. Runtime values are
 * checked when they are evaluated; identity properties (name, id) refuse them.
 */
final class DefinitionValidator
{
    /**
     * Properties that identify a component in its tree. Tree paths, patches
     * and lookups read them before anything is evaluated, so they never hold
     * runtime values.
     *
     * @var list<string>
     */
    public const array IdentityProperties = ['name', 'id'];

    /**
     * Expand one definition into the items a component constructor accepts.
     *
     * @param  class-string<Component>  $component
     * @return list<Property|ComponentInterface|ConfigurationNode>
     */
    public static function expand(string $component, Definition $definition, ?ComponentDefinition $schema = null): array
    {
        $items = $definition->items();

        self::check($component, $definition::class, $items, $schema ?? $component::definition());

        return $items;
    }

    /**
     * Check properties, children and configuration nodes against the schema of
     * the component receiving them, as make() does for definitions.
     *
     * @param  class-string<Component>  $component
     * @param  class-string  $source  The definition (or other source) the items came from, named in errors.
     * @param  iterable<Property|ComponentInterface|ConfigurationNode>  $items
     *
     * @throws InvalidDefinitionException
     */
    public static function check(string $component, string $source, iterable $items, ?ComponentDefinition $schema = null): void
    {
        $schema ??= $component::definition();

        foreach ($items as $item) {
            if ($item instanceof ComponentInterface) {
                if (! $schema->allowsChild($item)) {
                    throw InvalidDefinitionException::childNotAccepted($component, $item::class);
                }

                continue;
            }

            if ($item instanceof ConfigurationNode) {
                if (! $schema->allowsConfiguration($item)) {
                    throw InvalidDefinitionException::configurationNotAccepted($component, $item::class);
                }

                continue;
            }

            $property = $schema->property($item->key);

            if ($property === null) {
                if (! $schema->allowsUnknownProperties) {
                    throw InvalidDefinitionException::unsupported($component, $source, $item->key, $schema->propertyNames(), self::closest($item->key, $schema->propertyNames()));
                }

                continue;
            }

            if ($property->isCallback()) {
                if (! $item->value instanceof RuntimeValue) {
                    throw InvalidDefinitionException::rejectedValue($component, $source, $item->key, 'a trusted callback such as AfterStateUpdated::make(fn ...)', $item->value);
                }

                continue;
            }

            if ($item->value instanceof RuntimeValue) {
                if (in_array($item->key, self::IdentityProperties, true)) {
                    throw InvalidDefinitionException::rejectedValue($component, $source, $item->key, $property->expectedType().' (identity properties cannot be computed)', $item->value);
                }

                continue;
            }

            if (! $property->acceptsType($item->value)) {
                throw InvalidDefinitionException::rejectedValue($component, $source, $item->key, $property->expectedType(), $item->value);
            }

            if ($item->value === null) {
                continue;
            }

            if (! $property->allowsValue($item->value)) {
                throw InvalidDefinitionException::rejectedValue($component, $source, $item->key, $property->expectedValues(), $item->value);
            }

            if (! $property->satisfiesRule($item->value)) {
                throw InvalidDefinitionException::rejectedValue($component, $source, $item->key, $property->expectedRule(), $item->value);
            }
        }
    }

    /**
     * Check what only the finished component can show: required properties,
     * the number of children, the schema's constraints and unique ids in its
     * subtree. make(), withDefinitions() and the engine copy methods of
     * declarative components call it on the result.
     *
     * @throws InvalidDefinitionException
     */
    public static function assertComplete(Component $component, ?ComponentDefinition $schema = null): void
    {
        $schema ??= $component::definition();

        $missing = array_values(array_filter(
            $schema->requiredProperties(),
            static fn (string $key): bool => ! $component->has($key),
        ));

        if ($missing !== []) {
            throw InvalidDefinitionException::missing($component::class, $missing);
        }

        $count = $component->childCount();

        if ($schema->allowsChildren && $count < $schema->minChildren) {
            throw InvalidDefinitionException::tooFewChildren($component::class, $schema->minChildren, $count);
        }

        if ($schema->allowsChildren && $schema->maxChildren !== null && $count > $schema->maxChildren) {
            throw InvalidDefinitionException::tooManyChildren($component::class, $schema->maxChildren, $count);
        }

        foreach ($schema->constraints() as $constraint) {
            $messages = $constraint($component);

            foreach (is_iterable($messages) ? $messages : [$messages] as $message) {
                if (is_string($message)) {
                    throw InvalidDefinitionException::constraintFailed($component::class, $message);
                }
            }
        }

        $duplicate = SubtreeIds::duplicate($component);

        if ($duplicate !== null) {
            throw InvalidDefinitionException::duplicateId($component::class, $duplicate);
        }
    }

    /**
     * Expand the definitions passed to a component factory, rejecting repeated properties.
     *
     * @param  class-string<Component>  $component
     * @param  array<mixed>  $definitions
     * @return list<Property|ComponentInterface|ConfigurationNode>
     */
    public static function expandDefinitions(string $component, array $definitions, ?ComponentDefinition $schema = null): array
    {
        $schema ??= $component::definition();
        $expanded = [];
        $seen = [];

        foreach ($definitions as $definition) {
            if (! $definition instanceof Definition) {
                throw InvalidDefinitionException::notADefinition($component, $definition);
            }

            foreach (self::expand($component, $definition, $schema) as $item) {
                if ($item instanceof Property) {
                    self::remember($component, $seen, $item->key);
                }

                $expanded[] = $item;
            }
        }

        return $expanded;
    }

    /**
     * @param  class-string<Component>  $component
     * @param  array<string, true>  $seen
     */
    private static function remember(string $component, array &$seen, string $key): void
    {
        if (isset($seen[$key])) {
            throw InvalidDefinitionException::duplicate($component, $key);
        }

        $seen[$key] = true;
    }

    /**
     * @param  list<string>  $candidates
     */
    private static function closest(string $key, array $candidates): ?string
    {
        $best = null;
        $bestDistance = 4;

        foreach ($candidates as $candidate) {
            $distance = levenshtein(strtolower($key), strtolower($candidate));

            if ($distance < $bestDistance) {
                $best = $candidate;
                $bestDistance = $distance;
            }
        }

        return $best;
    }
}
