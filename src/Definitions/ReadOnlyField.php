<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Whether an input is shown but cannot be edited ("readonly" is a reserved word in PHP, hence the name).
 */
final readonly class ReadOnlyField extends FlagValue
{
    public static function key(): string
    {
        return 'readOnly';
    }
}
