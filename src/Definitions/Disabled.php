<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether the component is disabled.
 */
final readonly class Disabled extends FlagValue
{
    public static function key(): string
    {
        return 'disabled';
    }
}
