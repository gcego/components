<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * The text body of text-bearing components such as Text and Heading, as plain text.
 */
final readonly class Content extends TextValue
{
    public static function key(): string
    {
        return 'content';
    }
}
