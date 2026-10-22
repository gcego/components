<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Feedback;

use Gcego\Components\Schema\ComponentDefinition;

class Alert extends DismissibleMessage
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('feedback.alert');
    }
}
