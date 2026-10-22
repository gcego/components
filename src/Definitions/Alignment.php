<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Cross-axis alignment of a layout's children: "start", "center", "end", "stretch" or "baseline".
 */
final readonly class Alignment extends TokenValue
{
    public const string Start = 'start';

    public const string Center = 'center';

    public const string End = 'end';

    public const string Stretch = 'stretch';

    public const string Baseline = 'baseline';

    public static function key(): string
    {
        return 'align';
    }

    public static function options(): array
    {
        return [self::Start, self::Center, self::End, self::Stretch, self::Baseline];
    }
}
