<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether a file input accepts several files.
 */
final readonly class Multiple extends FlagValue
{
    public static function key(): string
    {
        return 'multiple';
    }
}
