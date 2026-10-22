<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Actions;

use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;
use Gcego\Components\Support\SafeUrl;

class Button extends Action
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('actions.button')
            ->withProperties(
                new PropertyDefinition('action', Type::string(), rule: SafeUrl::accepts(...), ruleDescription: SafeUrl::Description),
                new PropertyDefinition('buttonType', Type::string(), allowedValues: ['button', 'submit', 'reset']),
            )
            ->withoutChildren();
    }
}
