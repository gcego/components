<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Concerns\HasStatePath;
use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The state path a container nests its inputs under, such as "address" or "billing.address".
 */
final readonly class StatePath extends PropertyValue
{
    public static function make(string $path): self
    {
        if (preg_match(HasStatePath::Pattern, $path) !== 1) {
            throw InvalidDefinitionException::invalidValue(self::class, 'dot-separated path segments of letters, digits, "_" or "-"', $path);
        }

        return new self($path);
    }

    public static function key(): string
    {
        return 'statePath';
    }
}
