<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether media shows playback controls.
 */
final readonly class Controls extends FlagValue
{
    public static function key(): string
    {
        return 'controls';
    }
}
