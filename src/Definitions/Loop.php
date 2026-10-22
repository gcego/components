<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether media loops.
 */
final readonly class Loop extends FlagValue
{
    public static function key(): string
    {
        return 'loop';
    }
}
