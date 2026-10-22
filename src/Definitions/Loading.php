<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether the component shows a loading state.
 */
final readonly class Loading extends FlagValue
{
    public static function key(): string
    {
        return 'loading';
    }
}
