<?php

declare(strict_types=1);

namespace Gcego\Components\Support;

/**
 * A width or height: a positive pixel count, or a CSS length token such as
 * "full", "screen", "50%" or "24rem". Every component that takes Width or
 * Height accepts both forms; renderers map them to their output.
 */
final class Dimension
{
    public const string Pattern = '/\A[A-Za-z0-9.%\-]+\z/';

    public const string Description = 'a positive pixel count or a length such as "full", "50%" or "24rem"';

    public static function accepts(mixed $value): bool
    {
        return is_int($value) ? $value > 0 : is_string($value) && preg_match(self::Pattern, $value) === 1;
    }
}
