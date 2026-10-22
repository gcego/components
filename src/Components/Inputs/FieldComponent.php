<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Inputs;

use Gcego\Components\Concerns\HasState;
use Gcego\Components\Contracts\HoldsState;
use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;

abstract class FieldComponent extends SchemaComponent implements HoldsState
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withConcerns(HasState::class)->withProperties(
            new PropertyDefinition(self::Name, Type::string(), required: true, rule: static fn (mixed $value): bool => $value !== '', ruleDescription: 'non-empty string'),
            new PropertyDefinition('required', Type::bool()),
            new PropertyDefinition('disabled', Type::bool()),
            new PropertyDefinition('readOnly', Type::bool()),
            new PropertyDefinition('hidden', Type::bool()),
            new PropertyDefinition('placeholder', Type::string()),
            new PropertyDefinition('helpText', Type::string()),
            new PropertyDefinition('autocomplete', Type::string()),
        )->withoutChildren();
    }
}
