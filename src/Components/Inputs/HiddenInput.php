<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Inputs;

use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;

class HiddenInput extends FieldComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('inputs.hiddenInput')
            ->withProperties(
                new PropertyDefinition('value', Type::string()),
            );
    }
}
