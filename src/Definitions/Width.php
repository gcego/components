<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Support\Dimension;

/**
 * A width: a positive pixel count or a CSS length token such as "full" or
 * "24rem". Every component that takes it accepts both forms (see Support\Dimension).
 */
final readonly class Width extends PropertyValue
{
    public const string Pattern = Dimension::Pattern;

    public static function make(int|string $value): self
    {
        if (! Dimension::accepts($value)) {
            throw InvalidDefinitionException::invalidValue(self::class, Dimension::Description, $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'width';
    }
}
