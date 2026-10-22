<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Media;

use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;
use Gcego\Components\Support\SafeUrl;

/** Shared schema contract for the PlayableMedia family. */
abstract class PlayableMedia extends SchemaComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withProperties(
            new PropertyDefinition('src', Type::string(), rule: SafeUrl::accepts(...), ruleDescription: SafeUrl::Description),
            new PropertyDefinition('controls', Type::bool()),
            new PropertyDefinition('autoplay', Type::bool()),
            new PropertyDefinition('muted', Type::bool()),
            new PropertyDefinition('loop', Type::bool()),
        )->withoutChildren();
    }
}
