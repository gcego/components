<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Contracts\ConfigurationNode;
use Gcego\Components\Contracts\Definition;

/**
 * Configuration nodes attached to a component, such as styling or behaviour
 * modifiers provided by other packages:
 *
 *     Card::make(Configurations::make(Tailwind::make(...)), Children::make([...]));
 *
 * The component's schema decides which node types it accepts.
 */
final readonly class Configurations implements Definition
{
    /**
     * @param  list<ConfigurationNode>  $nodes
     */
    private function __construct(public array $nodes) {}

    public static function make(ConfigurationNode ...$nodes): self
    {
        return new self(array_values($nodes));
    }

    public function items(): array
    {
        return $this->nodes;
    }
}
