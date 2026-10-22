<?php

declare(strict_types=1);

namespace Gcego\Components\Foundation;

use Gcego\Components\Component;
use Gcego\Components\Concerns\CanBeHidden;
use Gcego\Components\Concerns\CanSpanColumns;
use Gcego\Components\Concerns\HasExtraAttributes;
use Gcego\Components\Contracts\DeclarativeComponent;
use Gcego\Components\Schema\ComponentDefinition;

/**
 * The base of every catalog component, and of custom components:
 *
 *     Card::make(
 *         Title::make('Profile'),
 *         Children::make([
 *             TextInput::make(Name::make('email'), Label::make('Email'), Required::make()),
 *         ]),
 *     );
 *
 * It adds the declarative make() factory, visibility, column spans and extra
 * attributes. Subclasses describe their schema in defineSchema() and may add
 * presets().
 */
abstract class SchemaComponent extends Component implements DeclarativeComponent
{
    use DeclarativeFactory;

    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withConcerns(
            CanBeHidden::class,
            CanSpanColumns::class,
            HasExtraAttributes::class,
        );
    }
}
