<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * How long a toast stays visible, in milliseconds.
 */
final readonly class Duration extends PropertyValue
{
    public static function make(int $value): self
    {
        if ($value < 0) {
            throw InvalidDefinitionException::invalidValue(self::class, 'an integer of at least 0', $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'duration';
    }
}
