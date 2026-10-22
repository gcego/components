<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether a table or column is sortable.
 */
final readonly class Sortable extends FlagValue
{
    public static function key(): string
    {
        return 'sortable';
    }
}
