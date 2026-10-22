<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Display;

use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;

/**
 * A thematic break between content, optionally labelled.
 */
class Separator extends SchemaComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('display.separator')
            ->withProperties(
                new PropertyDefinition('orientation', Type::string(), default: 'horizontal', allowedValues: ['horizontal', 'vertical']),
            )
            ->withoutChildren();
    }
}
