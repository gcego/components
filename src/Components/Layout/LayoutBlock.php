<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Layout;

use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;
use Gcego\Components\Support\Dimension;

/** Shared schema contract for the LayoutBlock family. */
abstract class LayoutBlock extends SchemaComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withProperties(
            new PropertyDefinition('align', Type::string()),
            new PropertyDefinition('justify', Type::string()),
            new PropertyDefinition('gap', Type::string()),
            new PropertyDefinition('width', [Type::int(), Type::string()], rule: Dimension::accepts(...), ruleDescription: Dimension::Description),
            new PropertyDefinition('height', [Type::int(), Type::string()], rule: Dimension::accepts(...), ruleDescription: Dimension::Description),
            new PropertyDefinition('hidden', Type::bool()),
        );
    }
}
