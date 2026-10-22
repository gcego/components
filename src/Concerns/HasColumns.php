<?php

declare(strict_types=1);

namespace Gcego\Components\Concerns;

use Gcego\Components\Contracts\Concern;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;
use Gcego\Components\Support\Responsive;

/**
 * The columns property of components that lay out children: Columns::make(2)
 * or Columns::make(['default' => 1, 'lg' => 3]) (engine builders: columns()).
 */
final class HasColumns implements Concern
{
    public static function properties(): array
    {
        return [
            new PropertyDefinition(
                'columns',
                [Type::int(), Type::array()],
                rule: static fn (mixed $value): bool => Responsive::accepts($value, static fn (mixed $item): bool => is_int($item) && $item > 0),
                ruleDescription: Responsive::describe('positive integer'),
            ),
        ];
    }
}
