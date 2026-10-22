<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Closure;
use Gcego\Components\Evaluation\Computed;

/**
 * A trusted hook the live engine runs after an input's state changes:
 *
 *     AfterStateUpdated::make(fn (Set $set, ?string $state) => $set('slug', Str::slug($state)))
 *
 * Like conditions, the callback is a non-serializable Evaluation\Computed value.
 */
final readonly class AfterStateUpdated extends PropertyValue
{
    public static function make(Closure $hook): self
    {
        return new self(new Computed($hook));
    }

    public static function key(): string
    {
        return 'afterStateUpdated';
    }
}
