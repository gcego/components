<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether an input is re-evaluated as soon as its state changes.
 */
final readonly class Live extends FlagValue
{
    public static function key(): string
    {
        return 'live';
    }
}
