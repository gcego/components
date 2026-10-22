<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Closure;
use Gcego\Components\Evaluation\Computed;
use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * Plain, non-empty text. Renderers must escape it; it is never trusted HTML.
 *
 * Text computed at evaluation time uses a trusted callback:
 * Label::using(fn (Get $get): string => ...). See Condition.
 */
abstract readonly class TextValue extends PropertyValue
{
    public static function make(string $text): static
    {
        if (trim($text) === '') {
            throw InvalidDefinitionException::invalidValue(static::class, 'non-empty plain text', $text);
        }

        return new static($text);
    }

    /**
     * The callback's result passes the same checks as make().
     */
    public static function using(Closure $text): static
    {
        return new static(new Computed($text, static fn (mixed $result): mixed => is_string($result)
            ? static::make($result)->value
            : throw InvalidDefinitionException::invalidValue(static::class, 'plain text', $result)));
    }
}
