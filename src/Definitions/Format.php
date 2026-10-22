<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * A display format such as "Y-m-d" for dates or columns.
 */
final readonly class Format extends TextValue
{
    public static function key(): string
    {
        return 'format';
    }
}
