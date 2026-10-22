<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Display;

use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;

class TableColumn extends SchemaComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('display.tableColumn')
            ->withProperties(
                new PropertyDefinition('field', Type::string()),
                new PropertyDefinition('format', Type::string()),
                new PropertyDefinition('align', Type::string()),
                new PropertyDefinition('sortable', Type::bool()),
                new PropertyDefinition('searchable', Type::bool()),
            )
            ->withoutChildren();
    }
}
