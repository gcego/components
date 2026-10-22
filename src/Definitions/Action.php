<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Support\SafeUrl;

/**
 * The action name or form endpoint a button or form submits to, such as
 * "save" or "/customers". It is never executed by this package. Because a
 * form renders it as its action URL, it must be a same-origin relative URL
 * or an http, https, mailto or tel URL; javascript: and other executable
 * schemes are refused.
 */
final readonly class Action extends TextValue
{
    public static function make(string $text): static
    {
        if (trim($text) !== '' && ! SafeUrl::isSafe($text)) {
            throw InvalidDefinitionException::invalidValue(self::class, 'an action name or '.SafeUrl::Description, $text);
        }

        return parent::make($text);
    }

    public static function key(): string
    {
        return 'action';
    }
}
