<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use BackedEnum;
use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * A design token such as a size, variant or icon name.
 *
 * Tokens are lowercase words joined by "-", "_", "." or ":" (for example
 * "primary", "extra-large" or "heroicon:save"). Subclasses may restrict them
 * to a fixed list of options. String-backed enums are accepted by value.
 */
abstract readonly class TokenValue extends PropertyValue
{
    public const string Pattern = '/\A[a-z0-9]+(?:[-_.:][a-z0-9]+)*\z/';

    public static function make(string|BackedEnum $token): static
    {
        $value = $token instanceof BackedEnum ? $token->value : $token;
        $options = static::options();

        if (! is_string($value) || preg_match(self::Pattern, $value) !== 1) {
            throw InvalidDefinitionException::invalidValue(static::class, 'a lowercase token such as "primary" or "extra-large"', $value);
        }

        if ($options !== null && ! in_array($value, $options, true)) {
            throw InvalidDefinitionException::invalidValue(static::class, 'one of "'.implode('", "', $options).'"', $value);
        }

        return new static($value);
    }

    /**
     * The accepted tokens, or null to accept any well-formed token.
     *
     * @return list<string>|null
     */
    public static function options(): ?array
    {
        return null;
    }
}
