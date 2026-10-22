<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * The date shown by a timeline item, as plain text.
 */
final readonly class DateLabel extends TextValue
{
    public static function key(): string
    {
        return 'date';
    }
}
