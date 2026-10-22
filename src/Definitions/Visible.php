<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether the component is visible.
 */
final readonly class Visible extends FlagValue
{
    public static function key(): string
    {
        return 'visible';
    }
}
