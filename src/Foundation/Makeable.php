<?php

declare(strict_types=1);

namespace Gcego\Components\Foundation;

use Gcego\Components\Building\ComponentBuilder;
use Gcego\Components\Facades\Components;

/**
 * The builder entry point for engine component families of framework
 * packages: TextColumn::make('email')->searchable()->sortable(). Declarative
 * catalog components use Foundation\DeclarativeFactory instead.
 *
 * make() starts a define() builder, passes it through the class's setUp()
 * hook, then through the application's configureUsing() modifiers.
 */
trait Makeable
{
    public static function make(mixed ...$items): ComponentBuilder
    {
        return Components::configurator()->configure(static::setUp(static::define(...$items)));
    }
}
