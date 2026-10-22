<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * The code language, such as "php" or "json".
 */
final readonly class Language extends TextValue
{
    public static function key(): string
    {
        return 'language';
    }
}
