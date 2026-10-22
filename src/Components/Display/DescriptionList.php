<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Display;

use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;

class DescriptionList extends SchemaComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('display.descriptionList')
            ->withProperties(
                new PropertyDefinition('data', Type::array()),
            )
            ->withoutChildren();
    }
}
