<?php

declare(strict_types=1);

namespace Gcego\Components\Live;

use Gcego\Components\Contracts\ComponentInterface;

/**
 * One component evaluated for the current state, before its children are resolved.
 *
 * @internal
 */
final readonly class Evaluated
{
    /**
     * @param  array<string, mixed>  $properties
     * @param  list<string>  $dependencies
     */
    public function __construct(
        public ComponentInterface $component,
        public string $path,
        public ?string $parentPath,
        public string $containerPath,
        public string $childContainer,
        public ?string $statePath,
        public array $properties,
        public bool $hidden,
        public ?string $label,
        public array $dependencies,
    ) {}

    /**
     * @param  list<ResolvedComponent>  $children
     */
    public function toResolved(array $children): ResolvedComponent
    {
        return new ResolvedComponent(
            component: $this->component,
            path: $this->path,
            containerPath: $this->containerPath,
            statePath: $this->statePath,
            properties: $this->properties,
            hidden: $this->hidden,
            label: $this->label,
            dependencies: $this->dependencies,
            children: $children,
            parentPath: $this->parentPath,
        );
    }
}
