<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The number of grid tracks a column spans.
 */
final readonly class Span extends PropertyValue
{
    public static function make(int $value): self
    {
        if ($value < 1) {
            throw InvalidDefinitionException::invalidValue(self::class, 'an integer of at least 1', $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'span';
    }
}
