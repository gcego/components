<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether media starts muted.
 */
final readonly class Muted extends FlagValue
{
    public static function key(): string
    {
        return 'muted';
    }
}
