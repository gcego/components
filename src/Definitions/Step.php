<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The increment of a number or range input; must be positive.
 */
final readonly class Step extends PropertyValue
{
    public static function make(int|float $value): self
    {
        if (! is_finite($value) || $value <= 0) {
            throw InvalidDefinitionException::invalidValue(self::class, 'a positive finite number', $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'step';
    }
}
