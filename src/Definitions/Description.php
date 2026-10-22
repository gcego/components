<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Supporting text, as plain text.
 */
final readonly class Description extends TextValue
{
    public static function key(): string
    {
        return 'description';
    }
}
