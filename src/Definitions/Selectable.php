<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether table rows are selectable.
 */
final readonly class Selectable extends FlagValue
{
    public static function key(): string
    {
        return 'selectable';
    }
}
