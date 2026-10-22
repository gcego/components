<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * The spacing between layout children, as a token such as "small" or "medium".
 */
final readonly class Gap extends TokenValue
{
    public static function key(): string
    {
        return 'gap';
    }
}
