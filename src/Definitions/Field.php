<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The record attribute a table column reads, such as "email" or "author.name".
 */
final readonly class Field extends PropertyValue
{
    public static function make(string $field): self
    {
        if (preg_match(Name::Pattern, $field) !== 1) {
            throw InvalidDefinitionException::invalidValue(self::class, 'an attribute name of letters, digits, "_" and "-" in "."-separated segments', $field);
        }

        return new self($field);
    }

    public static function key(): string
    {
        return 'field';
    }
}
