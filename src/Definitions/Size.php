<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * The component size: one of "extra-small", "small", "medium", "large" or "extra-large".
 */
final readonly class Size extends TokenValue
{
    public const string ExtraSmall = 'extra-small';

    public const string Small = 'small';

    public const string Medium = 'medium';

    public const string Large = 'large';

    public const string ExtraLarge = 'extra-large';

    public static function key(): string
    {
        return 'size';
    }

    public static function options(): array
    {
        return [self::ExtraSmall, self::Small, self::Medium, self::Large, self::ExtraLarge];
    }
}
