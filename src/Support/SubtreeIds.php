<?php

declare(strict_types=1);

namespace Gcego\Components\Support;

use Gcego\Components\Contracts\ComponentInterface;
use Gcego\Components\Traversal\ComponentWalker;
use WeakMap;

/**
 * The explicit ids in a component's subtree, so make() can refuse a
 * duplicate id as soon as the parent holding both copies is declared.
 *
 * Components are immutable, so each subtree's ids are computed once and
 * remembered per instance; a parent only merges its children's sets.
 * Subtrees deeper than the walker's default limit are left to validate(),
 * which reports the depth error with its path.
 *
 * @internal
 */
final class SubtreeIds
{
    /**
     * @var WeakMap<ComponentInterface, array<string, true>>|null
     */
    private static ?WeakMap $remembered = null;

    /**
     * The first id that appears more than once in the subtree, or null.
     */
    public static function duplicate(ComponentInterface $component): ?string
    {
        return self::collect($component, 0)['duplicate'];
    }

    /**
     * @return array{ids: array<string, true>, duplicate: ?string}
     */
    private static function collect(ComponentInterface $component, int $depth): array
    {
        $id = $component->id();
        $ids = $id === null ? [] : [$id => true];

        if (! $component->hasChildren()) {
            return ['ids' => $ids, 'duplicate' => null];
        }

        self::$remembered ??= new WeakMap;

        if (isset(self::$remembered[$component])) {
            return ['ids' => self::$remembered[$component], 'duplicate' => null];
        }

        if ($depth < ComponentWalker::DefaultMaxDepth) {
            foreach ($component->children() as $child) {
                $collected = self::collect($child, $depth + 1);

                if ($collected['duplicate'] !== null) {
                    return $collected;
                }

                foreach ($collected['ids'] as $childId => $true) {
                    if (isset($ids[$childId])) {
                        return ['ids' => $ids, 'duplicate' => (string) $childId];
                    }

                    $ids[$childId] = true;
                }
            }
        }

        self::$remembered[$component] = $ids;

        return ['ids' => $ids, 'duplicate' => null];
    }
}
