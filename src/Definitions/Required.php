<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether an input requires a value.
 */
final readonly class Required extends FlagValue
{
    public static function key(): string
    {
        return 'required';
    }
}
