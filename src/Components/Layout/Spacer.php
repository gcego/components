<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Layout;

use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;

/**
 * Empty space between layout items, sized by a Size token.
 */
class Spacer extends SchemaComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('layout.spacer')
            ->withProperties(
                new PropertyDefinition('size', Type::string(), default: 'medium'),
            )
            ->withoutChildren();
    }
}
