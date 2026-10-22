<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Requires a value while the condition returns true.
 */
final readonly class RequiredWhen extends Condition
{
    public static function key(): string
    {
        return 'required';
    }
}
