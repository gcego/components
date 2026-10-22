<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Feedback;

use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;
use Gcego\Components\Support\Dimension;

class Skeleton extends SchemaComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('feedback.skeleton')
            ->withProperties(
                new PropertyDefinition('rows', Type::int(), rule: static fn (mixed $value): bool => is_int($value) && $value >= 0, ruleDescription: 'non-negative integer'),
                new PropertyDefinition('width', [Type::int(), Type::string()], rule: Dimension::accepts(...), ruleDescription: Dimension::Description),
                new PropertyDefinition('height', [Type::int(), Type::string()], rule: Dimension::accepts(...), ruleDescription: Dimension::Description),
            )
            ->withoutChildren();
    }
}
