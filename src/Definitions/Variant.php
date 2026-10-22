<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * The visual variant, such as Variant::make('primary') or Variant::make(Variant::Danger).
 *
 * Any well-formed token is accepted so themes can add variants; a component
 * may restrict the values it supports in its schema.
 */
final readonly class Variant extends TokenValue
{
    public const string Primary = 'primary';

    public const string Secondary = 'secondary';

    public const string Success = 'success';

    public const string Warning = 'warning';

    public const string Danger = 'danger';

    public const string Info = 'info';

    public const string Neutral = 'neutral';

    public const string Ghost = 'ghost';

    public const string Outline = 'outline';

    public const string Link = 'link';

    public static function key(): string
    {
        return 'variant';
    }
}
