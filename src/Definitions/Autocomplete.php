<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * The browser autocomplete hint, such as "email" or "off".
 */
final readonly class Autocomplete extends TextValue
{
    public static function key(): string
    {
        return 'autocomplete';
    }
}
