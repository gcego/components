<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * The direction of a separator or other one-dimensional component: "horizontal" or "vertical".
 */
final readonly class Orientation extends TokenValue
{
    public const string Horizontal = 'horizontal';

    public const string Vertical = 'vertical';

    public static function key(): string
    {
        return 'orientation';
    }

    public static function options(): array
    {
        return [self::Horizontal, self::Vertical];
    }
}
