<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * The calendar view, as a token such as "month" or "week".
 */
final readonly class CalendarView extends TokenValue
{
    public static function key(): string
    {
        return 'view';
    }
}
