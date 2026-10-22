<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Display;

use Gcego\Components\Components\Media\ImageComponent;
use Gcego\Components\Schema\ComponentDefinition;

class Avatar extends ImageComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('display.avatar');
    }
}
