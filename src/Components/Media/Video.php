<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Media;

use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;
use Gcego\Components\Support\SafeUrl;

class Video extends PlayableMedia
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('media.video')
            ->withProperties(
                new PropertyDefinition('poster', Type::string(), rule: SafeUrl::accepts(...), ruleDescription: SafeUrl::Description),
            );
    }
}
