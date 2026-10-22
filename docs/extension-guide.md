# Extension guide

How to add components, definitions and concerns, and where the package boundary
with renderers lies. [Custom components](#custom-components) covers what
applications and packages add to the catalog, declared with the single
`Component::make(Definition::make(...))` API. [Framework engine](#framework-engine)
covers the layer GCEGO framework packages (`gcego/tables`, `gcego/forms`,
`gcego/infolists`, ...) build their own component families on.

## Custom components

### Component class

Extend `Foundation\SchemaComponent`. It provides the declarative `make()`
factory, `withDefinitions()`, `configureUsing()`, and the visibility
(`CanBeHidden`), column-span (`CanSpanColumns`) and `extraAttributes`
(`HasExtraAttributes`) properties:

```php
namespace App\Components;

use App\Values\Money;
use Gcego\Components\Definitions\Icon;
use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\Field;
use Gcego\Components\Schema\Type;

final class RevenueMetric extends SchemaComponent
{
    public const string Amount = 'amount';

    public const string Highlight = 'highlight';

    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()
            ->withType('app.revenueMetric')
            ->withFields([
                Field::of(self::Amount, Type::instanceOf(Money::class))->mustExist(),
                Field::boolean(self::Highlight)->defaultsTo(false),
                Field::string('icon'),
            ])
            ->withoutChildren();
    }

    protected static function presets(): array
    {
        return [Icon::make('banknotes')];
    }
}
```

The schema is the whole contract. Start from `parent::defineSchema()` to inherit
`name`, `label`, `description`, `id` and the concern properties, then:

| Schema method | Purpose |
| --- | --- |
| `withType('app.revenueMetric')` | renderer type identifier; defaults to the class name. Use a namespaced, unique value. |
| `withFields([...])` | add properties with the `Schema\Field` builder (`string()`, `boolean()`, `integer()`, `number()`, `array()`, `list()`, `mixed()`, `of()`), then `mustExist()`, `nullable()`, `defaultsTo()`, `allowedValues()`, `rule()`, `normalizeUsing()`, `description()` |
| `withProperties(new PropertyDefinition(...))` | the same with `PropertyDefinition` objects ([properties](properties.md)) |
| `withConcerns(HasColumns::class, ...)` | merge reusable property sets ([concerns](#concerns)) |
| `withoutProperties('gap')` | drop inherited properties |
| `withoutChildren()`, `withAllowedChildren([TextInput::class], min: 1, max: 3)` | child rules |
| `withAllowedConfigurations([Tailwind::class])` | accepted configuration nodes (placement configuration is always accepted) |
| `withConstraint(fn (ComponentInterface $component): string\|iterable\|null => ...)` | cross-property rules, reported as `ConstraintFailed` |
| `withUnknownProperties()` | accept undeclared properties (rarely appropriate) |

A property declared under an existing name replaces it, so
`Field::string(self::Name)->mustExist()` makes `name` required.

`make()` checks each definition against this schema, then the finished
component: missing required properties, child counts outside the
`withAllowedChildren()` bounds, failed constraints and ids repeated in its
subtree all throw `InvalidDefinitionException`. `withDefinitions()` and the
inherited copy methods (`withProperty()`, `withChildren()`, …) run the same
checks, and `withoutProperty()` refuses required properties. Tree validation
(`validate()` / `assertValid()`) reports the same rules with paths across whole
trees.

The constructor is `final` and only runs inside `make()`; `define()` and
`macro()` throw `InvalidConfigurationException`. Customize through the schema,
`presets()` and definitions.

### Presets and application defaults

`presets()` returns definitions every instance starts with. Applications add
their own defaults from a service provider's `boot()`; the callback takes no
arguments and returns a definition or a list of definitions:

```php
use App\Components\RevenueMetric;
use App\Definitions\Highlight;
use App\Definitions\Amount;
use Gcego\Components\Components\Inputs\FieldComponent;
use Gcego\Components\Definitions\HelpText;
use Gcego\Components\Definitions\Name;

RevenueMetric::configureUsing(fn (): array => [Highlight::make()]);

// Applies to every subclass; important callbacks run after the others.
FieldComponent::configureUsing(fn (): HelpText => HelpText::make('Required fields are marked.'), isImportant: true);

// Only while the callback runs; returns the callback's result.
$preview = RevenueMetric::configureUsing(
    fn (): Highlight => Highlight::make(),
    during: fn (): RevenueMetric => RevenueMetric::make(Name::make('preview'), Amount::make(100, 'XAF')),
);
```

Precedence, lowest first: schema defaults, `presets()`, `configureUsing()`
callbacks (important ones last), explicit definitions. `configureUsing()`
definitions are checked against the schema like explicit ones.

### Concerns

A concern is a reusable list of property definitions. Implement
`Contracts\Concern` and merge it with `withConcerns()`:

```php
use Gcego\Components\Contracts\Concern;
use Gcego\Components\Schema\Field;

final class HasTooltip implements Concern
{
    public static function properties(): array
    {
        return [
            Field::string('tooltip')->build(),
            Field::string('tooltipPlacement')->allowedValues(['top', 'bottom'])->defaultsTo('top')->build(),
        ];
    }
}
```

Built-in concerns: `CanBeHidden`, `CanSpanColumns`, `HasExtraAttributes` (all
`SchemaComponent`s), `HasColumns` and `HasStatePath` (containers), `HasState`
(form controls). See the [API reference](api-reference.md#concerns).
`withConcerns()` refuses classes that do not implement `Concern`.

### Extending catalog, family and layout classes

Catalog classes and their abstract family bases are non-final. Extend one to
inherit its schema, then set a distinct type and change only what differs:

```php
use Gcego\Components\Components\Inputs\TextInput;
use Gcego\Components\Components\Layout\Row;
use Gcego\Components\Definitions\MaxLength;
use Gcego\Components\Definitions\Placeholder;
use Gcego\Components\Layout\Section;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\Field;

final class SlugInput extends TextInput
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('app.slugInput');
    }

    protected static function presets(): array
    {
        return [MaxLength::make(64), Placeholder::make('my-page')];
    }
}

final class DensityRow extends Row
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('app.densityRow')
            ->withoutProperties('gap')
            ->withFields([Field::string('density')->defaultsTo('compact')]);
    }
}

final class DashboardSection extends Section
{
    public const string Collapsible = 'collapsible';

    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()->withType('app.dashboardSection')
            ->withFields([Field::boolean(self::Collapsible)->defaultsTo(false)]);
    }
}
```

Use `Components\Inputs\FieldComponent` (or `TextField`, `NumericField`,
`ChoiceField`, `TemporalField`) for a form control: it implements
`Contracts\HoldsState`, so the [live engine](reactivity.md) treats it as a
state holder, and adds `statePath`, `default`, `live`, `dehydrated` and
`afterStateUpdated`. Use `SchemaComponent` for anything that is not tied to a
family. `Schema\Field` is a property-schema builder, not a UI control.
`Layout\Item` subclasses keep the leaf restriction.

### Definitions

Every new property needs a typed definition, because `make()` accepts nothing
else. Extend the base that matches the value, implement `key()`, and validate in
`make()`:

| Base | Gives you |
| --- | --- |
| `Definitions\PropertyValue` | the protected `new self($value)` constructor; write your own `make()` |
| `Definitions\TextValue` | `make(string)` (non-empty plain text) and `using(Closure)`, whose result passes your `make()` at evaluation |
| `Definitions\FlagValue` | `make(bool $enabled = true)` |
| `Definitions\TokenValue` | `make(string\|BackedEnum)` checked against `TokenValue::Pattern`; override `options()` for a fixed list |
| `Definitions\Condition` | `make(Closure)` storing a trusted `Computed` callback |

A definition that computes its value wraps the closure in
`new Computed($closure, $check)`; `$check` receives a non-null result and
returns the value to use or throws `InvalidDefinitionException`, which
evaluation reports as `EvaluationException::refusedResult()`. Pass
`static fn (mixed $result): mixed => self::make($result)->value` to apply the
same rules as a declared value.

Properties that hold URLs or attributes should carry the package's rules in the
schema too, so `validate()` and the copy methods refuse unsafe values however
they arrive: `new PropertyDefinition('url', Type::string(), rule:
SafeUrl::accepts(...), ruleDescription: SafeUrl::Description)`, and likewise
`Support\SafeAttributes::isSafe(...)` or `Support\Dimension::accepts(...)`.

```php
namespace App\Definitions;

use App\Components\RevenueMetric;
use App\Values\Money;
use Gcego\Components\Definitions\FlagValue;
use Gcego\Components\Definitions\PropertyValue;
use Gcego\Components\Definitions\TokenValue;
use Gcego\Components\Exceptions\InvalidDefinitionException;

final readonly class Amount extends PropertyValue
{
    public static function make(int $minorUnits, string $currency): self
    {
        if ($minorUnits < 0) {
            throw InvalidDefinitionException::invalidValue(self::class, 'a non-negative amount', $minorUnits);
        }

        return new self(new Money($minorUnits, $currency));
    }

    public static function key(): string
    {
        return RevenueMetric::Amount;
    }
}

final readonly class Highlight extends FlagValue
{
    public static function key(): string
    {
        return RevenueMetric::Highlight;
    }
}

final readonly class Trend extends TokenValue
{
    public const string Up = 'up';

    public const string Down = 'down';

    public static function key(): string
    {
        return 'trend';
    }

    public static function options(): array
    {
        return [self::Up, self::Down];
    }
}

RevenueMetric::make(Name::make('revenue'), Label::make('Revenue'), Amount::make(250000, 'XAF'), Highlight::make());
```

A `Condition` subclass makes a boolean property reactive:

```php
use Gcego\Components\Definitions\Condition;

final readonly class CollapsibleWhen extends Condition
{
    public static function key(): string
    {
        return DashboardSection::Collapsible;
    }
}

DashboardSection::make(Name::make('stats'), CollapsibleWhen::make(fn (Get $get): bool => $get('mode') === 'compact'));
```

Implement `Contracts\Definition` directly for a definition that expands into
several properties, children or configuration nodes. This replaces builder
macros:

```php
use Gcego\Components\Contracts\Definition;
use Gcego\Components\Properties\Property;

final readonly class Slug implements Definition
{
    private function __construct(private int $length) {}

    public static function make(int $length = 64): self
    {
        return new self($length);
    }

    public function items(): array
    {
        return [
            new Property('maxLength', $this->length),
            new Property('helpText', 'Lowercase letters, digits and dashes.'),
        ];
    }
}

TextInput::make(Name::make('handle'), Slug::make(32));
```

`Validation\DefinitionValidator` checks every expanded item against the
receiving schema in `make()`, `withDefinitions()`, `configureUsing()`, the copy
methods of declarative components and schema patches, so a definition cannot
bypass validation: `Slug` is refused by
a component without `maxLength`. Value objects held by definitions implement
`Contracts\ValueObject` ([object property values](#4-object-property-values)).
See [definitions](definitions.md) for the built-in set.

### Catalog registration

Renderers resolve components by class or type, so custom components need no
central registration. Register an alias when tooling or a renderer adapter
enumerates the catalog:

```php
use App\Components\RevenueMetric;
use Gcego\Components\Catalog\ComponentCatalog;

public function register(): void
{
    $this->app->afterResolving(ComponentCatalog::class, function (ComponentCatalog $catalog): void {
        $catalog->register('revenueMetric', RevenueMetric::class);
    });
}
```

Aliases are camelCase, unique (case-insensitively) and must name a concrete
`Component` subclass. The catalog is scoped, so registrations made directly via
`Components::catalog()->register()` last only for the current request or job.
See [custom registrations](component-catalog.md#custom-registrations).

### Rendering integration boundary

This package stops at validated, immutable data:

| This package | The consuming renderer or runtime |
| --- | --- |
| schemas, definitions, validation, normalization | maps `type()` (or the class) to a view, Blade/Livewire/Inertia component or JSON transport |
| `Computed` callbacks, `Evaluator`, `LiveSchema` state and `Changes` | decides when to evaluate, sends `Changes` to the client, persists `dehydrate()` output |
| URL safety (`SafeUrl`) and plain-text definitions | escapes text, authorizes actions, validates submitted data server-side |
| `ConfigurationNode` and `extraAttributes` data | turns them into classes or attributes |

Renderers should:

1. call `$root->assertValid()` once, when a schema is registered;
2. use `Components::normalize()`, `ComponentTree`, `ComponentWalker` or
   `Components::live($schema, $state)->toArray()` instead of recursing manually;
3. evaluate computed properties through `Component::evaluateProperty()` or the
   live engine, never by calling `Computed::$closure` directly (that skips
   injection, `Computed::$check` and the property checks);
4. key per-occurrence state by `TreeNode::identity()->stableKey()`
   (`id:…` or `path:…`).

`Required` and `RequiredWhen` describe UI behavior only; they do not replace
server-side validation.

### Testing custom components

Tests need the provider. The package's own suite registers it in `tests/Pest.php`
before each test:

```php
use Gcego\Components\ComponentServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;

pest()->beforeEach(function (): void {
    $app = new Application(dirname(__DIR__));
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($app);
    $app->register(ComponentServiceProvider::class);
})->afterEach(function (): void {
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication(null);
    Container::setInstance(null);
})->in('Unit', 'Integration');
```

Inside a Laravel application the framework's `TestCase` does this already.
Cover the schema, presets, definition checks and tree validation:

```php
use App\Components\RevenueMetric;
use App\Definitions\Amount;
use Gcego\Components\Definitions\Level;
use Gcego\Components\Definitions\Name;
use Gcego\Components\Exceptions\InvalidDefinitionException;

it('declares a revenue metric with its presets', function (): void {
    $metric = RevenueMetric::make(Name::make('revenue'), Amount::make(250000, 'XAF'));

    expect($metric->type())->toBe('app.revenueMetric')
        ->and($metric->get('icon'))->toBe('banknotes')
        ->and($metric->get('highlight'))->toBeFalse()
        ->and($metric->validate()->isValid())->toBeTrue();
});

it('refuses missing and unsupported definitions', function (): void {
    expect(fn () => RevenueMetric::make(Name::make('revenue')))
        ->toThrow(InvalidDefinitionException::class, 'requires "amount"')
        ->and(fn () => RevenueMetric::make(Name::make('revenue'), Amount::make(1, 'XAF'), Level::make(2)))
        ->toThrow(InvalidDefinitionException::class, 'does not accept');
});
```

Assert `InvalidDefinitionException` from `make()` for child counts,
constraints and duplicate ids (`'needs at least 1 child component(s)'`), use
`$component->validate()->errorsWithCode(...)` for trees `make()` did not check,
`Components::live()` for conditions and hooks,
and `Components::normalize()` to pin the exported shape. Real examples:
`tests/Fixtures` (`ColorSwatch`, `Hex`, `PresetInput`, `DashboardSection`,
`DensityRow`) and `tests/Integration/Authoring`. See also
[validation](validation.md#testing-custom-components).

## Framework engine

The sections below describe the engine: the base `Component`,
`Properties\Property`, `Building\ComponentBuilder`, `Foundation\Makeable` and
configuration nodes. Framework packages use it to define their own component
families with builders and shorthand. It is not a way to declare catalog or
custom `SchemaComponent` classes, which refuse the engine's constructor, builder
and macro entry points.

### 1. Extend `Component`

```php
namespace Gcego\Tables\Columns;

use Gcego\Components\Building\ComponentBuilder;
use Gcego\Components\Component;
use Gcego\Components\Foundation\Makeable;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;

final class Text extends Component
{
    use Makeable;

    public const string Searchable = 'searchable';

    public const string Sortable = 'sortable';

    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()
            ->withType('tables.columns.text')
            ->withProperties(
                new PropertyDefinition(self::Name, Type::string(), required: true),
                new PropertyDefinition(self::Searchable, Type::bool(), default: false),
                new PropertyDefinition(self::Sortable, Type::bool(), default: false),
            )
            ->withoutChildren();
    }

    protected static function setUp(ComponentBuilder $builder): ComponentBuilder
    {
        return $builder->sortable();
    }

    protected static function shorthand(): array
    {
        return [self::Name, self::Label];
    }
}
```

- `defineSchema()` runs once per class within the active Laravel scope;
  `definition()` caches it. Subclasses reject unknown properties unless they
  call `withUnknownProperties()`.
- The accessors and copy methods of `Component` are `final`. Customize through
  the schema, not by overriding accessors.

Three ways to construct an engine component:

```php
$column = new Text('name', 'Subject Name');                    // shorthand constructor
$column = Text::define('name', 'Name')->searchable()->build(); // builder, no setUp()
$column = Text::make('email')->build();                        // Makeable: setUp() + configureUsing()
```

Builder setters resolve by class constant, property key or snake case; a bool
or mixed property set without an argument becomes `true`; closures are stored as
`Evaluation\Computed`. Engine families register builder defaults and macros:

```php
Text::configureUsing(fn (ComponentBuilder $column): ComponentBuilder => $column->searchable());
Text::macro('compact', fn (): ComponentBuilder => $this->label('Compact'));

Text::make('code')->compact()->build();
```

Macros are bound to the builder and must return it. Builder setters and
shorthand values are not schema-checked when set (only `Definition` items are);
validate engine trees with `assertValid()` or `buildValidated()`.

### 2. Containers with shorthand

```php
return new Table(
    Subject::class,

    new Text('name', 'Subject Name'),
    new Text('code', 'Code'),
);
```

is just:

```php
final class Table extends Component
{
    public const string Model = 'model';

    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()
            ->withType('tables.table')
            ->withProperties(new PropertyDefinition(self::Model, Type::classString()))
            ->withAllowedChildren([Text::class], min: 1);
    }

    protected static function shorthand(): array
    {
        return [self::Model];
    }
}
```

This package only checks that the model value names an existing class and never
instantiates it. Prefer `shorthand()` to custom constructors.

If a constructor requires a scalar argument, give the component a custom builder
that adapts the canonical items through `instantiate()`:

```php
use Gcego\Components\Building\ComponentBuilder;
use Gcego\Components\Component;
use Gcego\Components\Contracts\ComponentInterface;
use Gcego\Components\Contracts\ConfigurationNode;
use Gcego\Components\Exceptions\InvalidBuilderException;
use Gcego\Components\Properties\Property;

final class KeyedComponent extends Component
{
    public function __construct(string $name, mixed ...$items)
    {
        parent::__construct(new Property(self::Name, $name), ...$items);
    }

    public static function builderClass(): string
    {
        return KeyedComponentBuilder::class;
    }

    protected static function shorthand(): array
    {
        return [self::Name];
    }
}

final class KeyedComponentBuilder extends ComponentBuilder
{
    /** @param list<Property|ComponentInterface|ConfigurationNode> $items */
    protected function instantiate(array $items): Component
    {
        $name = $this->propertyValue(Component::Name);

        if (! is_string($name)) {
            throw InvalidBuilderException::invalidValue($this->component, 'name', 'a string name is required');
        }

        $rest = array_values(array_filter(
            $items,
            static fn ($item): bool => ! $item instanceof Property || $item->key !== Component::Name,
        ));

        return new KeyedComponent($name, ...$rest);
    }
}

KeyedComponent::define('example')->label('Example')->build();
```

The hook receives built children, properties and configuration nodes in
declaration order within each group. `prepare()` adapts incoming builder
arguments; `instantiate()` adapts the final constructor call.

### 3. Configuration nodes

Implement `Contracts\ConfigurationNode` and return plain data from
`normalize()`. Nodes are kept apart from renderable children by contract:

```php
use Gcego\Components\Contracts\ConfigurationNode;

final readonly class Tailwind implements ConfigurationNode
{
    public function __construct(public string $classes) {}

    public function normalize(): array
    {
        return ['classes' => $this->classes];
    }
}

new Table(Subject::class, new Text('name'), new Tailwind('p-4'));                         // engine
Card::make(Title::make('Profile'), Configurations::make(new Tailwind('gap-2')));          // declarative
```

Components accept every node unless their schema restricts the list with
`withAllowedConfigurations()`. Implement `Contracts\PlacementConfiguration`
for nodes that tell the parent how to place a component; every component
accepts those.

### 4. Object property values

- **Immutable data:** implement `Contracts\ValueObject`, preferably as a
  `readonly` class, and declare `Type::instanceOf(YourValue::class)`.
- **Runtime-only behavior:** implement `Contracts\RuntimeValue`. The engine
  stores it untouched and normalizes it to a `RuntimePlaceholder`. Use
  `Evaluation\Computed` for closures; invoking other runtime values is the
  runtime package's responsibility.

### 5. Testing engine components

```php
use Gcego\Components\Properties\Property;
use Gcego\Components\Validation\ErrorCode;

it('supports shorthand and explicit properties equally', function (): void {
    expect((new Text('name', 'Name'))->normalize())
        ->toBe((new Text(new Property(Text::Name, 'name'), new Property(Text::Label, 'Name')))->normalize());
});

it('rejects children', function (): void {
    $result = (new Text('name'))->withChildren(new Text('other'))->validate();

    expect($result->first()?->code)->toBe(ErrorCode::ChildrenNotAllowed);
});
```

### Builder invariants

Components and their builders must be concrete, instantiable classes. The
manager checks that `builderClass()` extends `ComponentBuilder`, that container
bindings target the requested component, and that the result has the requested
type (`InvalidBuilderException`). Nested builder item lists are limited to 32
levels.

### Reserved builder method names

Concrete builder methods take precedence over dynamic property setters:
`schema`, `components`, `child`, `label`, `columnSpanFull`, `hiddenOn`,
`visibleOn`, `extraAttributes`, `set`, `setMany`, `setIfMissing`, `get`, `has`,
`properties`, `forget`, `onlyProperties`, `clearProperties`,
`replaceProperties`, `with`, `replaceChildren`, `prependChildren`,
`clearChildren`, `filterChildren`, `mapChildren`, `children`, `childCount`,
`configure`, `replaceConfigurations`, `clearConfigurations`, `tap`, `apply`,
`when`, `unless`, `build`, `buildValidated`, `validate`, `normalize`, `tree`,
`inspect` and `componentClass`. Use `set('child', $value)` when such a name is a
property in your schema.

## Exceptions

Every exception implements `Exceptions\ComponentException`:

| Exception | Parent | Thrown by |
| --- | --- | --- |
| `InvalidDefinitionException` | `InvalidArgumentException` | invalid definition values; definitions, children or configuration nodes a component does not accept; repeated or missing required properties; child counts, constraints and duplicate ids refused by `make()` or a declarative copy |
| `InvalidConfigurationException` | `InvalidArgumentException` | closed construction routes on declarative components, `configureUsing()` callbacks returning non-definitions, engine constructor items, schema definitions, catalog registrations, unnormalizable values, missing provider |
| `InvalidBuilderException` | `BadMethodCallException` | engine builders: unknown methods, invalid values, wrong builder or component classes, excessive nesting |
| `InvalidPropertyException` | `InvalidArgumentException` | property keys and values |
| `DuplicatePropertyException` | `InvalidPropertyException` | repeated keys in engine construction |
| `MissingPropertyException` | `OutOfBoundsException` | `Component::require()` |
| `InvalidChildException` | `InvalidArgumentException` | cycles and excessive nesting during traversal |
| `InvalidComponentException` | `DomainException` | strict validation; exposes `result()` |
| `EvaluationException` | `RuntimeException` | unresolvable closure parameters, computed results the definition or property rejects |
| `PatchException` | `InvalidArgumentException` | unmatched selectors (with a suggestion), the root as a target, refused children and child counts, invalid patch operations and results |

## Integration map for GCEGO packages

| Package | Uses |
| --- | --- |
| `gcego/blueprints` | `Component`, `ComponentDefinition`, `assertValid()` |
| `gcego/registry` | `normalize()`, `ComponentIdentity::stableKey()`, `ComponentDefinition::toArray()`, `ComponentCatalog` |
| `gcego/runtime` | `ComponentWalker`, `TreeNode`, `LiveSchema`, `Evaluator`, `RuntimeValue` |
| `gcego/chroma` | `ConfigurationNode`, `ConfigurationCollection` |
| `gcego/tables`, `gcego/forms`, `gcego/infolists`, `gcego/widgets`, `gcego/layouts`, `gcego/actions` | engine component families (subclasses, builders, `Makeable`) and `Components::extend()` |
| `gcego/cli`, `gcego/studio`, `gcego/testing` | `ComponentInspector`, `ValidationResult::toArray()` |

## Complete extension example

[`examples/custom-component.php`](../examples/custom-component.php) declares a
custom `SchemaComponent` with presets, two custom definitions, a value object
and a current-scope catalog alias. Run `php examples/run.php --custom` or follow
the [usage guide](usage.md#12-custom-components-and-definitions).
