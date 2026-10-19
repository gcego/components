<?php

declare(strict_types=1);

namespace Gcego\Components\Layout;

use Gcego\Components\Component;
use Gcego\Components\Schema\ComponentDefinition;

class Schema extends Component
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('schema');
    }
}
