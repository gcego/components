<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The total number of records paginated.
 */
final readonly class Total extends PropertyValue
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
        return 'total';
    }
}
