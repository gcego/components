<?php

declare(strict_types=1);

namespace Gcego\Components\Exceptions;

use Gcego\Components\Support\ValueDescriber;
use InvalidArgumentException;

/**
 * Thrown when a typed definition receives an invalid value, or when a component
 * receives a definition it does not accept.
 */
class InvalidDefinitionException extends InvalidArgumentException implements ComponentException
{
    /**
     * @param  class-string  $definition
     */
    public static function invalidValue(string $definition, string $expectation, mixed $value): self
    {
        return new self(sprintf(
            '%s expects %s, %s given.',
            ValueDescriber::className($definition),
            $expectation,
            ValueDescriber::describe($value),
        ));
    }

    /**
     * @param  class-string  $component
     * @param  class-string  $definition
     * @param  list<string>  $accepted
     */
    public static function unsupported(string $component, string $definition, string $key, array $accepted, ?string $suggestion = null): self
    {
        return new self(sprintf(
            '%s does not accept %s (property "%s").%s Accepted properties: %s.',
            ValueDescriber::className($component),
            ValueDescriber::className($definition),
            $key,
            $suggestion === null ? '' : sprintf(' Did you mean "%s"?', $suggestion),
            $accepted === [] ? 'none' : implode(', ', $accepted),
        ));
    }

    /**
     * @param  class-string  $component
     */
    public static function configurationNotAccepted(string $component, string $configuration): self
    {
        return new self(sprintf(
            '%s does not accept the %s configuration node.',
            ValueDescriber::className($component),
            ValueDescriber::className($configuration),
        ));
    }

    /**
     * @param  class-string  $component
     * @param  class-string  $definition
     */
    public static function rejectedValue(string $component, string $definition, string $key, string $expectation, mixed $value): self
    {
        return new self(sprintf(
            '%s rejects %s: property "%s" expects %s, %s given.',
            ValueDescriber::className($component),
            ValueDescriber::className($definition),
            $key,
            $expectation,
            ValueDescriber::describe($value),
        ));
    }

    /**
     * @param  class-string  $component
     */
    public static function duplicate(string $component, string $key): self
    {
        return new self(sprintf(
            '%s received property "%s" more than once. Pass each definition once.',
            ValueDescriber::className($component),
            $key,
        ));
    }

    /**
     * @param  class-string  $component
     */
    public static function childNotAccepted(string $component, string $child): self
    {
        return new self(sprintf(
            '%s does not accept %s as a child.',
            ValueDescriber::className($component),
            ValueDescriber::className($child),
        ));
    }

    /**
     * @param  class-string  $component
     * @param  list<string>  $keys
     */
    public static function missing(string $component, array $keys): self
    {
        return new self(sprintf(
            '%s requires %s.',
            ValueDescriber::className($component),
            implode(', ', array_map(static fn (string $key): string => '"'.$key.'"', $keys)),
        ));
    }

    /**
     * @param  class-string  $component
     */
    public static function notADefinition(string $component, mixed $value): self
    {
        return new self(sprintf(
            '%s accepts typed definitions only, %s given.',
            ValueDescriber::className($component),
            ValueDescriber::describe($value),
        ));
    }

    /**
     * @param  class-string  $component
     */
    public static function tooFewChildren(string $component, int $minimum, int $count): self
    {
        return new self(sprintf(
            '%s needs at least %d child component(s), %d given.',
            ValueDescriber::className($component),
            $minimum,
            $count,
        ));
    }

    /**
     * @param  class-string  $component
     */
    public static function tooManyChildren(string $component, int $maximum, int $count): self
    {
        return new self(sprintf(
            '%s accepts at most %d child component(s), %d given.',
            ValueDescriber::className($component),
            $maximum,
            $count,
        ));
    }

    /**
     * @param  class-string  $component
     */
    public static function constraintFailed(string $component, string $message): self
    {
        return new self(sprintf('%s: %s', ValueDescriber::className($component), $message));
    }

    /**
     * @param  class-string  $component
     */
    public static function duplicateId(string $component, string $id): self
    {
        return new self(sprintf(
            '%s contains id "%s" more than once. Ids must be unique within a tree; give each copy its own Id::make().',
            ValueDescriber::className($component),
            $id,
        ));
    }

    /**
     * @param  class-string  $component
     * @param  list<string>  $keys
     */
    public static function requiredRemoved(string $component, array $keys): self
    {
        return new self(sprintf(
            '%s cannot remove required %s.',
            ValueDescriber::className($component),
            implode(', ', array_map(static fn (string $key): string => '"'.$key.'"', $keys)),
        ));
    }
}
