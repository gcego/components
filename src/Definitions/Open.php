<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether an overlay starts open.
 */
final readonly class Open extends FlagValue
{
    public static function key(): string
    {
        return 'open';
    }
}
