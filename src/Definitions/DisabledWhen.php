<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Disables the component while the condition returns true.
 */
final readonly class DisabledWhen extends Condition
{
    public static function key(): string
    {
        return 'disabled';
    }
}
