<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The active state: a flag for menu items, breadcrumbs and links, or the name
 * of the active tab, accordion item or wizard step for their containers.
 */
final readonly class Active extends PropertyValue
{
    public static function make(bool|string $value = true): self
    {
        if (is_string($value) && trim($value) === '') {
            throw InvalidDefinitionException::invalidValue(self::class, 'a boolean or a non-empty child name', $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'active';
    }
}
