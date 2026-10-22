<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Media;

use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;
use Gcego\Components\Support\Dimension;
use Gcego\Components\Support\SafeUrl;

abstract class ImageComponent extends SchemaComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()
            ->withProperties(
                new PropertyDefinition('src', Type::string(), rule: SafeUrl::accepts(...), ruleDescription: SafeUrl::Description),
                new PropertyDefinition('alt', Type::string()),
                new PropertyDefinition('width', [Type::int(), Type::string()], rule: Dimension::accepts(...), ruleDescription: Dimension::Description),
                new PropertyDefinition('height', [Type::int(), Type::string()], rule: Dimension::accepts(...), ruleDescription: Dimension::Description),
            )
            ->withoutChildren();
    }
}
