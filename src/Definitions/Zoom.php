<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The map zoom level, from 0 to 24.
 */
final readonly class Zoom extends PropertyValue
{
    public static function make(int|float $value): self
    {
        if (! is_finite($value) || $value < 0 || $value > 24) {
            throw InvalidDefinitionException::invalidValue(self::class, 'a finite number from 0 to 24', $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'zoom';
    }
}
