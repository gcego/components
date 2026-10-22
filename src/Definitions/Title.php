<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * A heading shown by titled blocks such as cards, panels and modals, as plain text.
 */
final readonly class Title extends TextValue
{
    public static function key(): string
    {
        return 'title';
    }
}
