<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The map longitude, from -180 to 180.
 */
final readonly class Longitude extends PropertyValue
{
    public static function make(int|float $value): self
    {
        if (! is_finite($value) || $value < -180 || $value > 180) {
            throw InvalidDefinitionException::invalidValue(self::class, 'a finite number from -180 to 180', $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'longitude';
    }
}
