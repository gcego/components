<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Hint text shown by an empty input, as plain text.
 */
final readonly class Placeholder extends TextValue
{
    public static function key(): string
    {
        return 'placeholder';
    }
}
