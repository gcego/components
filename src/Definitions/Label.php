<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * The visible label, as plain text.
 */
final readonly class Label extends TextValue
{
    public static function key(): string
    {
        return 'label';
    }
}
