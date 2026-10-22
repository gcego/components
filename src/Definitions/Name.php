<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The component name: an input's field name, or a stable handle used by paths,
 * patches and lookups. Letters, digits, "_" and "-", in "."-separated segments.
 */
final readonly class Name extends PropertyValue
{
    public const string Pattern = '/\A[A-Za-z0-9_\-]+(?:\.[A-Za-z0-9_\-]+)*\z/';

    public static function make(string $name): self
    {
        if (preg_match(self::Pattern, $name) !== 1) {
            throw InvalidDefinitionException::invalidValue(self::class, 'a name of letters, digits, "_" and "-" in "."-separated segments', $name);
        }

        return new self($name);
    }

    public static function key(): string
    {
        return 'name';
    }
}
