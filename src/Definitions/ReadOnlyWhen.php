<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Makes an input read-only while the condition returns true.
 */
final readonly class ReadOnlyWhen extends Condition
{
    public static function key(): string
    {
        return 'readOnly';
    }
}
