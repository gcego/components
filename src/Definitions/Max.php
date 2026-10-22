<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The upper bound of a number, range, progress or date input: a number, or a date string for temporal inputs.
 */
final readonly class Max extends PropertyValue
{
    public static function make(int|float|string $value): self
    {
        if (is_string($value) ? trim($value) === '' : ! is_finite((float) $value)) {
            throw InvalidDefinitionException::invalidValue(self::class, 'a finite number or a non-empty date string', $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'max';
    }
}
