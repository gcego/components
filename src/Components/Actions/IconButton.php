<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Actions;

use Gcego\Components\Component;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;

/**
 * A button that shows only an icon. The label is required because it is the
 * button's accessible name.
 */
class IconButton extends Button
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('actions.iconButton')
            ->withProperties(
                new PropertyDefinition('icon', Type::string(), required: true),
                new PropertyDefinition(Component::Label, Type::string(), required: true),
            );
    }
}
