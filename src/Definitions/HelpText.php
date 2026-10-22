<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * Guidance shown with an input, as plain text.
 */
final readonly class HelpText extends TextValue
{
    public static function key(): string
    {
        return 'helpText';
    }
}
