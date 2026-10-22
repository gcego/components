<?php

declare(strict_types=1);

namespace Gcego\Components\Live;

use Gcego\Components\Contracts\ComponentInterface;
use Gcego\Components\Contracts\HoldsState;

/**
 * One component occurrence as it stands for the current state and context:
 * evaluated properties, visibility, label, state path and the state paths
 * its closures read. Hidden components have no resolved children.
 */
final readonly class ResolvedComponent
{
    /**
     * @param  array<string, mixed>  $properties
     * @param  list<string>  $dependencies  State paths read by the component's closures.
     * @param  list<ResolvedComponent>  $children
     * @param  string|null  $parentPath  The parent occurrence's tree path; null for the root.
     */
    public function __construct(
        public ComponentInterface $component,
        public string $path,
        public string $containerPath,
        public ?string $statePath,
        public array $properties,
        public bool $hidden,
        public ?string $label,
        public array $dependencies,
        public array $children,
        public ?string $parentPath = null,
    ) {}

    /**
     * @internal The same resolution with other resolved children.
     *
     * @param  list<ResolvedComponent>  $children
     */
    public function withChildren(array $children): self
    {
        return new self(
            $this->component,
            $this->path,
            $this->containerPath,
            $this->statePath,
            $this->properties,
            $this->hidden,
            $this->label,
            $this->dependencies,
            $children,
            $this->parentPath,
        );
    }

    public function type(): string
    {
        return $this->component->type();
    }

    public function name(): ?string
    {
        $name = $this->properties['name'] ?? null;

        return is_string($name) ? $name : null;
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        return array_key_exists($key, $this->properties) ? $this->properties[$key] : $fallback;
    }

    public function holdsState(): bool
    {
        return $this->component instanceof HoldsState && $this->statePath !== null;
    }

    public function isVisible(): bool
    {
        return ! $this->hidden;
    }
}
