<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * A heading level from 1 to 6.
 */
final readonly class Level extends PropertyValue
{
    public static function make(int $level): self
    {
        if ($level < 1 || $level > 6) {
            throw InvalidDefinitionException::invalidValue(self::class, 'a heading level between 1 and 6', $level);
        }

        return new self($level);
    }

    public static function key(): string
    {
        return 'level';
    }
}
