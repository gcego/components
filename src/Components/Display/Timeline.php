<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Display;

use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;

class Timeline extends SchemaComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('display.timeline');
    }
}
