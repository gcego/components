<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

/**
 * A boolean switch; make() without an argument enables it.
 */
abstract readonly class FlagValue extends PropertyValue
{
    public static function make(bool $enabled = true): static
    {
        return new static($enabled);
    }
}
