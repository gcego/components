<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Display;

use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;

class Stat extends TextualComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('display.stat')
            ->withProperties(
                new PropertyDefinition('value', [Type::string(), Type::int(), Type::float()]),
                new PropertyDefinition('description', Type::string()),
                new PropertyDefinition('icon', Type::string()),
            );
    }
}
