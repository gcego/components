<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * Where a link opens: "_self", "_blank", "_parent" or "_top".
 */
final readonly class Target extends PropertyValue
{
    public const string Self = '_self';

    public const string Blank = '_blank';

    public const string Parent = '_parent';

    public const string Top = '_top';

    public static function make(string $target): self
    {
        if (! in_array($target, [self::Self, self::Blank, self::Parent, self::Top], true)) {
            throw InvalidDefinitionException::invalidValue(self::class, 'one of "_self", "_blank", "_parent" or "_top"', $target);
        }

        return new self($target);
    }

    public static function key(): string
    {
        return 'target';
    }
}
