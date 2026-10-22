<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Main-axis distribution of layout children, as a token such as "start", "center" or "between".
 */
final readonly class Justify extends TokenValue
{
    public static function key(): string
    {
        return 'justify';
    }
}
