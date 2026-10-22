<?php

declare(strict_types=1);

namespace Gcego\Components\Components\Actions;

use Gcego\Components\Schema\ComponentDefinition;

/**
 * A primary action joined to a menu of secondary DropdownItem actions.
 */
class SplitButton extends Action
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('actions.splitButton')
            ->withAllowedChildren([DropdownItem::class], min: 1);
    }
}
