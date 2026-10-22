<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The maximum number of characters an input accepts.
 */
final readonly class MaxLength extends PropertyValue
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
        return 'maxLength';
    }
}
