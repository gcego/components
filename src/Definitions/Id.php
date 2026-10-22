<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Component;
use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * An explicit component id, unique within a tree, for lookups independent of the component's path.
 */
final readonly class Id extends PropertyValue
{
    public static function make(string $id): self
    {
        if (preg_match(Component::IdPattern, $id) !== 1) {
            throw InvalidDefinitionException::invalidValue(self::class, 'an identifier starting with a letter followed by letters, digits, "_", "-", "." or ":"', $id);
        }

        return new self($id);
    }

    public static function key(): string
    {
        return Component::Id;
    }
}
