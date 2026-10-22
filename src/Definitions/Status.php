<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * A status token such as "success" or "pending".
 */
final readonly class Status extends TokenValue
{
    public static function key(): string
    {
        return 'status';
    }
}
