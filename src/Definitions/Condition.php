<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Closure;
use Gcego\Components\Evaluation\Computed;

/**
 * A boolean property decided at evaluation time by a trusted callback:
 *
 *     VisibleWhen::make(fn (Get $get): bool => $get('type') === 'business')
 *
 * The callback is wrapped in an Evaluation\Computed runtime value. It is never
 * serialized (normalization reports a placeholder) and runs only when the
 * live engine or Component::evaluateProperty() evaluates the component, with
 * the usual $get, $set, $state, $record, $operation and typed injection.
 */
abstract readonly class Condition extends PropertyValue
{
    public static function make(Closure $condition): static
    {
        return new static(new Computed($condition));
    }
}
