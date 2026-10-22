<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * A color token such as "primary" or "danger"; renderers map it to a palette.
 */
final readonly class Color extends TokenValue
{
    public static function key(): string
    {
        return 'color';
    }
}
