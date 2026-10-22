<?php

declare(strict_types=1);

namespace Gcego\Components\Concerns;

use Gcego\Components\Contracts\Concern;
use Gcego\Components\Evaluation\Computed;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;

/**
 * State for components that hold a value:
 *
 *     TextInput::make(
 *         Name::make('title'),
 *         DefaultValue::make('Untitled'),
 *         Live::make(),                  // optional: dependents are detected automatically
 *         Dehydrated::make(false),       // keep out of dehydrate()
 *         AfterStateUpdated::make(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
 *     );
 */
final class HasState implements Concern
{
    public static function properties(): array
    {
        return [
            ...HasStatePath::properties(),
            new PropertyDefinition('default', Type::mixed(), nullable: true),
            new PropertyDefinition('live', Type::bool()),
            new PropertyDefinition('dehydrated', Type::bool()),
            new PropertyDefinition('afterStateUpdated', Type::instanceOf(Computed::class)),
        ];
    }
}
