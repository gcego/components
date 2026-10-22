<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether feedback can be dismissed.
 */
final readonly class Dismissible extends FlagValue
{
    public static function key(): string
    {
        return 'dismissible';
    }
}
