<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Inputs;

use Gcego\Components\Schema\ComponentDefinition;

class EmailInput extends TextField
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('inputs.emailInput');
    }
}
