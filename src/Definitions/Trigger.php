<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * What opens a dropdown, as a token such as "click" or "hover".
 */
final readonly class Trigger extends TokenValue
{
    public static function key(): string
    {
        return 'trigger';
    }
}
