<?php

declare(strict_types=1);

namespace Gcego\Components\Definitions;

use Gcego\Components\Contracts\Buildable;
use Gcego\Components\Contracts\ComponentInterface;
use Gcego\Components\Contracts\Definition;
use Gcego\Components\Exceptions\InvalidDefinitionException;

/**
 * The ordered child components of a container:
 *
 *     Card::make(Children::make([Heading::make(Content::make('Title')), Text::make(Content::make('Body'))]));
 *
 * Builders are built immediately, so the list holds only immutable components
 * and reusing one in several parents never shares mutable state.
 */
final readonly class Children implements Definition
{
    /**
     * @param  list<ComponentInterface>  $components
     */
    private function __construct(public array $components) {}

    /**
     * @param  iterable<mixed>  $children
     */
    public static function make(iterable $children): self
    {
        $components = [];

        foreach ($children as $child) {
            if ($child instanceof Buildable) {
                $child = $child->build();
            }

            if (! $child instanceof ComponentInterface) {
                throw InvalidDefinitionException::invalidValue(self::class, 'component instances', $child);
            }

            $components[] = $child;
        }

        return new self($components);
    }

    public function items(): array
    {
        return $this->components;
    }
}
