<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * The chart type, as a token such as "line", "bar" or "pie".
 */
final readonly class ChartType extends TokenValue
{
    public static function key(): string
    {
        return 'chartType';
    }
}
