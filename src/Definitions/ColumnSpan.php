<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Support\Responsive;

/**
 * The number of grid columns a component spans, optionally per breakpoint; ColumnSpan::full() spans every column.
 */
final readonly class ColumnSpan extends PropertyValue
{
    /**
     * @param  int|string|array<string, int|string>  $value
     */
    public static function make(int|string|array $value): self
    {
        if (! Responsive::accepts($value, static fn (mixed $item): bool => (is_int($item) && $item > 0) || $item === 'full')) {
            throw InvalidDefinitionException::invalidValue(self::class, Responsive::describe('positive integer or "full"'), $value);
        }

        return new self($value);
    }

    public static function full(): self
    {
        return new self('full');
    }

    public static function key(): string
    {
        return 'columnSpan';
    }
}
