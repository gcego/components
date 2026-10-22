<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * An icon name such as "save" or "heroicon:save"; renderers resolve it.
 */
final readonly class Icon extends TokenValue
{
    public static function key(): string
    {
        return 'icon';
    }
}
