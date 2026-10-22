<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Hides the component while the condition returns true.
 */
final readonly class HiddenWhen extends Condition
{
    public static function key(): string
    {
        return 'hidden';
    }
}
