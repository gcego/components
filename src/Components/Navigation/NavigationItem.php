<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Navigation;

use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;
use Gcego\Components\Support\SafeUrl;

/** Shared schema contract for the NavigationItem family. */
abstract class NavigationItem extends SchemaComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withProperties(
            new PropertyDefinition('url', Type::string(), rule: SafeUrl::accepts(...), ruleDescription: SafeUrl::Description),
            new PropertyDefinition('icon', Type::string()),
            new PropertyDefinition('target', Type::string()),
            new PropertyDefinition('disabled', Type::bool()),
            new PropertyDefinition('active', Type::bool()),
        );
    }
}
