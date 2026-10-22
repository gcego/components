<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The file types a file input accepts, such as ["image/png", ".pdf"].
 */
final readonly class Accept extends PropertyValue
{
    public static function make(string ...$values): self
    {
        if ($values === [] || in_array('', array_map('trim', $values), true)) {
            throw InvalidDefinitionException::invalidValue(self::class, 'one or more non-empty strings', $values);
        }

        return new self(array_values($values));
    }

    public static function key(): string
    {
        return 'accept';
    }
}
