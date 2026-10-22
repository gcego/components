<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Overlays;

use Gcego\Components\Components\Feedback\Message;
use Gcego\Components\Schema\ComponentDefinition;

class Tooltip extends Message
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('overlays.tooltip');
    }
}
