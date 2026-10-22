<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Support\Responsive;

/**
 * The number of grid columns: Columns::make(2) or Columns::make(['default' => 1, 'lg' => 3]).
 */
final readonly class Columns extends PropertyValue
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
        return 'columns';
    }
}
