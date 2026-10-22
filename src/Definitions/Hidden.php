<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether the component is hidden.
 */
final readonly class Hidden extends FlagValue
{
    public static function key(): string
    {
        return 'hidden';
    }
}
