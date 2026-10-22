<?php

declare(strict_types=1);

namespace Gcego\Components\Evaluation;

use Closure;
use Gcego\Components\Contracts\RuntimeValue;

/**
 * A property value computed when the component is evaluated, created by the
 * reactive definitions:
 *
 *     TextInput::make(
 *         Name::make('slug'),
 *         DisabledWhen::make(fn (Get $get): bool => (bool) $get('locked')),
 *         Label::using(fn (string $operation): string => $operation === 'create' ? 'Slug' : 'Address'),
 *     );
 *
 * Validation defers the property's checks until evaluation. A non-null result
 * first passes $check, when given: the definition that created the value
 * applies the same rules make() applies to a declared value (Label::using()
 * refuses empty text like Label::make() does) and returns the value to use, or
 * throws InvalidDefinitionException. The result is then checked against the
 * property definition. Normalization replaces the value with a RuntimePlaceholder.
 */
final readonly class Computed implements RuntimeValue
{
    /**
     * @param  (Closure(mixed): mixed)|null  $check
     */
    public function __construct(public Closure $closure, public ?Closure $check = null) {}
}
