<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether a select, table or column is searchable.
 */
final readonly class Searchable extends FlagValue
{
    public static function key(): string
    {
        return 'searchable';
    }
}
