<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Properties\Property;

/**
 * The choices of a select, radio group or chart, keyed by value: ["admin" => "Administrator"].
 *
 * Values are plain data (scalars, arrays, enums or value objects); closures are refused.
 */
final readonly class Options extends PropertyValue
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
        return 'options';
    }
}
