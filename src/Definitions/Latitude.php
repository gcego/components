<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The map latitude, from -90 to 90.
 */
final readonly class Latitude extends PropertyValue
{
    public static function make(int|float $value): self
    {
        if (! is_finite($value) || $value < -90 || $value > 90) {
            throw InvalidDefinitionException::invalidValue(self::class, 'a finite number from -90 to 90', $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'latitude';
    }
}
