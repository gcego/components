<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Support\SafeUrl;

/**
 * A link destination (the "url" property): a same-origin relative URL or an http,
 * https, mailto or tel URL. Executable schemes such as javascript: and data:, and
 * protocol-relative URLs ("//host"), are refused.
 */
final readonly class Href extends PropertyValue
{
    public static function make(string $url): self
    {
        if (! SafeUrl::isSafe($url)) {
            throw InvalidDefinitionException::invalidValue(self::class, SafeUrl::Description, $url);
        }

        return new self($url);
    }

    public static function key(): string
    {
        return 'url';
    }
}
