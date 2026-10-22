<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Shows the component only while the condition returns true.
 */
final readonly class VisibleWhen extends Condition
{
    public static function key(): string
    {
        return 'visible';
    }
}
