<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Overlays;

use Gcego\Components\Components\Feedback\Message;
use Gcego\Components\Schema\ComponentDefinition;

class Popover extends Message
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('overlays.popover');
    }
}
