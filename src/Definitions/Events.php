<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Properties\Property;

/**
 * The events shown by a calendar.
 *
 * Values are plain data (scalars, arrays, enums or value objects); closures are refused.
 */
final readonly class Events extends PropertyValue
{
    /**
     * @param  array<array-key, mixed>  $values
     */
    public static function make(array $values): self
    {
        if (! Property::isSupportedValue($values)) {
            throw InvalidDefinitionException::invalidValue(self::class, 'plain data without closures or arbitrary objects', $values);
        }

        return new self($values);
    }

    public static function key(): string
    {
        return 'events';
    }
}
