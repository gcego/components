<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Contracts\RuntimeValue;
use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Properties\Property;

/**
 * The current value of an input or display component.
 *
 * Accepts the values a Property can store: null, scalars, arrays of them, enums
 * and immutable value objects. Closures and runtime values (such as
 * Evaluation\Computed) are refused.
 */
final readonly class Value extends PropertyValue
{
    public static function make(mixed $value): self
    {
        if ($value instanceof RuntimeValue || ! Property::isSupportedValue($value)) {
            throw InvalidDefinitionException::invalidValue(self::class, 'null, a scalar, an array of supported values, an enum or a value object', $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'value';
    }
}
