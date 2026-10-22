<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The HTML button type: "button", "submit" or "reset".
 */
final readonly class ButtonType extends PropertyValue
{
    public const string Button = 'button';

    public const string Submit = 'submit';

    public const string Reset = 'reset';

    public static function make(string $value): self
    {
        if (! in_array($value, [self::Button, self::Submit, self::Reset], true)) {
            throw InvalidDefinitionException::invalidValue(self::class, 'one of "button", "submit", "reset"', $value);
        }

        return new self($value);
    }

    public static function key(): string
    {
        return 'buttonType';
    }
}
