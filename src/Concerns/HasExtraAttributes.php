<?php

declare(strict_types=1);

namespace Gcego\Components\Concerns;

use Gcego\Components\Contracts\Concern;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;
use Gcego\Components\Support\SafeAttributes;

/**
 * The extraAttributes property, set with Attributes::make(['data-test' => 'name']).
 *
 * The schema applies the same rules as the Attributes definition, so values
 * that reach the property another way are still refused by validate().
 */
final class HasExtraAttributes implements Concern
{
    public static function properties(): array
    {
        return [
            new PropertyDefinition(
                'extraAttributes',
                Type::array(),
                rule: SafeAttributes::isSafe(...),
                ruleDescription: SafeAttributes::Description,
            ),
        ];
    }
}
