<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Inputs;

use Gcego\Components\Concerns\HasColumns;
use Gcego\Components\Concerns\HasStatePath;
use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;
use Gcego\Components\Support\SafeUrl;

class Form extends SchemaComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('inputs.form')
            ->withConcerns(HasColumns::class, HasStatePath::class)
            ->withProperties(
                new PropertyDefinition('action', Type::string(), rule: SafeUrl::accepts(...), ruleDescription: SafeUrl::Description),
                new PropertyDefinition('method', Type::string(), allowedValues: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']),
                new PropertyDefinition('disabled', Type::bool()),
            );
    }
}
