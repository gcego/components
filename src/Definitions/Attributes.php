<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Support\SafeAttributes;

/**
 * Extra HTML attributes (the "extraAttributes" property), such as
 * Attributes::make(['data-test' => 'save', 'aria-describedby' => 'hint']).
 *
 * Values are scalars or null and are escaped by the renderer. Attributes that
 * execute code are refused: event handlers ("on*"), framework directives
 * ("@*", ":*", "x-*", "wire:*", "hx-*", "v-*"), "srcdoc", "style", unsafe
 * URLs in URL attributes and "srcset", and any value starting with an
 * executable scheme such as "javascript:". See Support\SafeAttributes.
 */
final readonly class Attributes extends PropertyValue
{
    public const string NamePattern = SafeAttributes::NamePattern;

    /**
     * @param  array<string, scalar|null>  $attributes
     */
    public static function make(array $attributes): self
    {
        $violation = SafeAttributes::violation($attributes);

        if ($violation !== null) {
            throw InvalidDefinitionException::invalidValue(self::class, $violation['expectation'], $violation['value']);
        }

        return new self($attributes);
    }

    public static function key(): string
    {
        return 'extraAttributes';
    }
}
