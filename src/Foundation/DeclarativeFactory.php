<?php

declare(strict_types=1);

namespace Gcego\Components\Foundation;

use Closure;
use Gcego\Components\Building\ComponentBuilder;
use Gcego\Components\Contracts\ComponentInterface;
use Gcego\Components\Contracts\ConfigurationNode;
use Gcego\Components\Contracts\Definition;
use Gcego\Components\Definitions\Children;
use Gcego\Components\Definitions\Configurations;
use Gcego\Components\Exceptions\InvalidBuilderException;
use Gcego\Components\Exceptions\InvalidConfigurationException;
use Gcego\Components\Facades\Components;
use Gcego\Components\Properties\Property;
use Gcego\Components\Validation\DefinitionValidator;

/**
 * The single GCEGO Studio component declaration API:
 *
 *     Button::make(
 *         Label::make('Save Changes'),
 *         Variant::make('primary'),
 *     );
 *
 * make() accepts typed definitions only and returns the immutable component.
 * Each definition is checked against the component's schema; repeated and
 * missing required properties, too few or too many children, failed schema
 * constraints and duplicate ids in the subtree are refused. Defaults come from the schema,
 * the class's presets() and the application's configureUsing() callbacks, and
 * explicit definitions override them.
 *
 * The constructor only runs inside make(), and the inherited fluent entry
 * points (define(), macro()) are closed, so there is no second way to
 * declare these components.
 */
trait DeclarativeFactory
{
    /**
     * Nesting depth of make() calls currently constructing a component.
     */
    private static int $declaring = 0;

    /**
     * @internal Components are constructed by make() only.
     */
    final public function __construct(mixed ...$items)
    {
        if (self::$declaring === 0) {
            throw InvalidConfigurationException::declarativeOnly(static::class, 'new '.static::class.'(...)');
        }

        parent::__construct(...$items);
    }

    public static function make(Definition ...$definitions): static
    {
        $schema = static::definition();
        $explicit = DefinitionValidator::expandDefinitions(static::class, $definitions, $schema);
        $presets = DefinitionValidator::expandDefinitions(static::class, static::presets(), $schema);
        $hasModifiers = Components::configurator()->hasModifiersFor(static::class);

        self::$declaring++;

        try {
            $component = $hasModifiers
                ? self::configured($presets, $explicit)
                : new static(...self::merge($presets, $explicit));
        } finally {
            self::$declaring--;
        }

        DefinitionValidator::assertComplete($component, $schema);

        return $component;
    }

    public function withDefinitions(Definition ...$definitions): static
    {
        $properties = [];
        $children = null;
        $configurations = null;

        foreach (DefinitionValidator::expandDefinitions(static::class, $definitions) as $item) {
            if ($item instanceof Property) {
                $properties[] = $item;
            } elseif ($item instanceof ConfigurationNode) {
                $configurations[] = $item;
            } else {
                $children[] = $item;
            }
        }

        foreach ($definitions as $definition) {
            if ($definition instanceof Children) {
                $children ??= [];
            } elseif ($definition instanceof Configurations) {
                $configurations ??= [];
            }
        }

        $copy = $properties === [] ? $this : $this->withProperty(...$properties);
        $copy = $children === null ? $copy : $copy->withChildren(...$children);

        return $configurations === null ? $copy : $copy->withConfigurations(...$configurations);
    }

    /**
     * Application-wide defaults for this class and its subclasses. The callback
     * returns definitions, which explicit definitions override:
     *
     *     Button::configureUsing(fn (): array => [Variant::make('secondary')]);
     *
     * @param  Closure(): (Definition|list<Definition>)  $modifier
     */
    public static function configureUsing(Closure $modifier, ?Closure $during = null, bool $isImportant = false): mixed
    {
        $component = static::class;

        return parent::configureUsing(static function (ComponentBuilder $builder) use ($modifier, $component): ComponentBuilder {
            $defaults = $modifier();
            $defaults = $defaults instanceof Definition ? [$defaults] : $defaults;

            if (! is_array($defaults) || array_filter($defaults, static fn (mixed $default): bool => ! $default instanceof Definition) !== []) {
                throw InvalidConfigurationException::invalidDefaults($component, $defaults);
            }

            return $builder->with(...$defaults);
        }, $during, $isImportant);
    }

    /**
     * Not available: declare the component with make() and typed definitions.
     */
    public static function define(mixed ...$items): never
    {
        throw InvalidConfigurationException::declarativeOnly(static::class, static::class.'::define()');
    }

    /**
     * Not available: add a custom Definition class instead of a builder macro.
     */
    public static function macro(string $name, Closure $macro): never
    {
        throw InvalidConfigurationException::declarativeOnly(static::class, static::class.'::macro()');
    }

    /**
     * Definitions every instance of this class starts with; explicit definitions override them.
     *
     * @return list<Definition>
     */
    protected static function presets(): array
    {
        return [];
    }

    /**
     * Declarative components are customized with presets(), not builder hooks.
     */
    final protected static function setUp(ComponentBuilder $builder): ComponentBuilder
    {
        return $builder;
    }

    /**
     * Presets first, then explicit definitions: explicit properties replace
     * preset ones, and explicit children or configuration nodes replace the
     * preset ones of the same kind.
     *
     * @param  list<Property|ComponentInterface|ConfigurationNode>  $presets
     * @param  list<Property|ComponentInterface|ConfigurationNode>  $explicit
     * @return list<Property|ComponentInterface|ConfigurationNode>
     */
    private static function merge(array $presets, array $explicit): array
    {
        if ($presets === []) {
            return $explicit;
        }

        [$presetProperties, $presetChildren, $presetConfigurations] = self::split($presets);
        [$properties, $children, $configurations] = self::split($explicit);

        return [
            ...array_values([...$presetProperties, ...$properties]),
            ...($children === [] ? $presetChildren : $children),
            ...($configurations === [] ? $presetConfigurations : $configurations),
        ];
    }

    /**
     * @param  list<Property|ComponentInterface|ConfigurationNode>  $items
     * @return array{0: array<string, Property>, 1: list<ComponentInterface>, 2: list<ConfigurationNode>}
     */
    private static function split(array $items): array
    {
        $properties = [];
        $children = [];
        $configurations = [];

        foreach ($items as $item) {
            if ($item instanceof Property) {
                $properties[$item->key] = $item;
            } elseif ($item instanceof ConfigurationNode) {
                $configurations[] = $item;
            } else {
                $children[] = $item;
            }
        }

        return [$properties, $children, $configurations];
    }

    /**
     * Apply configureUsing() callbacks between presets and explicit definitions.
     *
     * @param  list<Property|ComponentInterface|ConfigurationNode>  $presets
     * @param  list<Property|ComponentInterface|ConfigurationNode>  $explicit
     */
    private static function configured(array $presets, array $explicit): static
    {
        [$properties, $children, $configurations] = self::split($explicit);
        $builder = Components::configurator()->configure(new ComponentBuilder(static::class, $presets))->with(...array_values($properties));
        $builder = $children === [] ? $builder : $builder->replaceChildren($children);
        $builder = $configurations === [] ? $builder : $builder->replaceConfigurations(...$configurations);
        $component = $builder->build();

        if (! $component instanceof static) {
            throw InvalidBuilderException::wrongComponent(static::class, $component::class);
        }

        return $component;
    }
}
