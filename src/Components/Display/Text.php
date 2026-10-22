<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Display;

use Gcego\Components\Schema\ComponentDefinition;

class Text extends TextualComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('display.text');
    }
}
