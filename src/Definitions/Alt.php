<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Alternative text describing an image, as plain text.
 */
final readonly class Alt extends TextValue
{
    public static function key(): string
    {
        return 'alt';
    }
}
