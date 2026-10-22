<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Support\Responsive;

/**
 * The grid column a component starts at, optionally per breakpoint.
 */
final readonly class ColumnStart extends PropertyValue
{
    /**
     * @param  int|array<string, int>  $value
     */
    public static function make(int|array $value): self
    {
        if (! Responsive::accepts($value, static fn (mixed $item): bool => is_int($item) && $item > 0)) {
            throw InvalidDefinitionException::invalidValue(self::class, Responsive::describe('positive integer'), $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'columnStart';
    }
}
