<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether media starts playing automatically.
 */
final readonly class Autoplay extends FlagValue
{
    public static function key(): string
    {
        return 'autoplay';
    }
}
