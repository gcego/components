<?php

declare(strict_types=1);

namespace Gcego\Components\Layout;

use Gcego\Components\Component;
use Gcego\Components\Concerns\HasColumns;
use Gcego\Components\Concerns\HasStatePath;
use Gcego\Components\Contracts\DeclarativeComponent;
use Gcego\Components\Foundation\DeclarativeFactory;
use Gcego\Components\Schema\ComponentDefinition;

/**
 * The root of a layout:
 *
 *     Schema::make(Columns::make(2), Children::make([...]))
 */
class Schema extends Component implements DeclarativeComponent
{
    use DeclarativeFactory;

    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('schema')
            ->withConcerns(HasColumns::class, HasStatePath::class);
    }
}
