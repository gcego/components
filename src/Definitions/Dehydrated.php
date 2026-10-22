<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether an input's state is included when state is dehydrated.
 */
final readonly class Dehydrated extends FlagValue
{
    public static function key(): string
    {
        return 'dehydrated';
    }
}
