<?php

declare(strict_types=1);

namespace Gcego\Components\Contracts;

use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * A component declared only through typed definitions:
 *
 *     Button::make(Label::make('Save'), Variant::make('primary'));
 *
 * make() is the single public construction API. It returns the immutable,
 * validated component; constructors, builders, configuration arrays and
 * property wrappers are not available to application code.
 */
interface DeclarativeComponent extends ComponentInterface
{
    /**
     * @throws InvalidDefinitionException when a definition is not accepted
     */
    public static function make(Definition ...$definitions): static;

    /**
     * A copy with the given definitions applied; Children replaces the children.
     *
     * @throws InvalidDefinitionException when a definition is not accepted
     */
    public function withDefinitions(Definition ...$definitions): static;
}
