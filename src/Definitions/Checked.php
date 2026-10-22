<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether a checkbox or toggle starts checked.
 */
final readonly class Checked extends FlagValue
{
    public static function key(): string
    {
        return 'checked';
    }
}
