<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The HTTP method a form declares: "GET", "POST", "PUT", "PATCH" or "DELETE".
 */
final readonly class Method extends PropertyValue
{
    public const string Get = 'GET';

    public const string Post = 'POST';

    public const string Put = 'PUT';

    public const string Patch = 'PATCH';

    public const string Delete = 'DELETE';

    public static function make(string $value): self
    {
        if (! in_array($value, [self::Get, self::Post, self::Put, self::Patch, self::Delete], true)) {
            throw InvalidDefinitionException::invalidValue(self::class, 'one of "GET", "POST", "PUT", "PATCH", "DELETE"', $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'method';
    }
}
