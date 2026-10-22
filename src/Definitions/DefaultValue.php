<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Closure;
use Gcego\Components\Contracts\RuntimeValue;
use Gcego\Components\Evaluation\Computed;
use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Properties\Property;

/**
 * The value an input starts with when its state has none.
 *
 * Accepts the values a Property can store: null, scalars, arrays of them, enums
 * and immutable value objects. Closures and runtime values (such as
 * Evaluation\Computed) are refused; a default computed at
 * evaluation time uses DefaultValue::using(fn (string $operation) => ...).
 */
final readonly class DefaultValue extends PropertyValue
{
    public static function make(mixed $value): self
    {
        if ($value instanceof RuntimeValue || ! Property::isSupportedValue($value)) {
            throw InvalidDefinitionException::invalidValue(self::class, 'null, a scalar, an array of supported values, an enum or a value object', $value);
        }

        return new self($value);
    }

    public static function using(Closure $value): self
    {
        return new self(new Computed($value, static fn (mixed $result): mixed => self::make($result)->value));
    }

    public static function key(): string
    {
        return 'default';
    }
}
