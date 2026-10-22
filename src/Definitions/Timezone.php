<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * An IANA timezone such as "Africa/Douala".
 */
final readonly class Timezone extends TextValue
{
    public static function key(): string
    {
        return 'timezone';
    }
}
