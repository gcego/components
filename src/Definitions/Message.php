<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * The message text of feedback components, as plain text.
 */
final readonly class Message extends TextValue
{
    public static function key(): string
    {
        return 'message';
    }
}
