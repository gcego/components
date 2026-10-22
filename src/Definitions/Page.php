<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The current page of a pagination component.
 */
final readonly class Page extends PropertyValue
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
        return 'page';
    }
}
