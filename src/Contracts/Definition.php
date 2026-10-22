<?php

declare(strict_types=1);

namespace Gcego\Components\Contracts;

use Gcego\Components\Properties\Property;

/**
 * A typed, immutable piece of component configuration:
 *
 *     Button::make(Label::make('Save Changes'), Variant::make('primary'));
 *
 * A definition expands into the engine's ordinary items (properties, child
 * components and configuration nodes). Components check every expanded property against their schema,
 * so a custom definition cannot bypass validation: it can only produce values
 * the receiving component declares and accepts.
 */
interface Definition
{
    /**
     * @return list<Property|ComponentInterface|ConfigurationNode>
     */
    public function items(): array;
}
