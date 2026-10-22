<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Contracts\Definition;
use Gcego\Components\Properties\Property;

/**
 * A definition that sets exactly one component property.
 *
 * Subclasses declare the property key and a typed make() that validates the
 * value before it reaches the private constructor.
 */
abstract readonly class PropertyValue implements Definition
{
    final protected function __construct(public mixed $value) {}

    /**
     * The component property this definition sets.
     */
    abstract public static function key(): string;

    public function items(): array
    {
        return [new Property(static::key(), $this->value)];
    }
}
