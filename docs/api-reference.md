# API reference

Every public class, interface, trait and enum under `src/`, with its public
signatures. All names are relative to `Gcego\Components`. Per-item detail for the
95 definitions lives in [definitions](definitions.md) and for the 85 catalog
components in the [component catalog](component-catalog.md).

Application code declares components only through
`Component::make(Definition::make(...))` ([declarative API](#declarative-api)).
The base `Component`, `Properties\Property` and `Building\ComponentBuilder` form
the [framework engine](#framework-engine) that GCEGO packages build their own
component families on.

- [Entry points](#entry-points): `Facades\Components`, `ComponentManager`, `ComponentServiceProvider`
- [Declarative API](#declarative-api): `Foundation`, `Contracts\Definition`, `Validation\DefinitionValidator`
- [Definitions](#definitions), [Catalog and components](#catalog-and-components), [Layout](#layout)
- [Framework engine](#framework-engine): `Component`, `Building\ComponentBuilder`, `Foundation\Makeable`
- [Configuration](#configuration), [Concerns](#concerns), [Properties and schema](#properties-and-schema), [Collections](#collections)
- [Evaluation](#evaluation), [Live](#live), [Patching](#patching)
- [Trees](#trees), [Traversal](#traversal), [Validation](#validation), [Normalization](#normalization), [Inspection](#inspection)
- [Contracts](#contracts), [Support](#support), [Exceptions](#exceptions)

## Entry points

### `Facades\Components` (final, extends `Facade`)

Laravel facade for `ComponentManager`, discovered as the `Components` alias. It
proxies every public manager method below. `static getFacadeRoot(): ComponentManager`
returns the registered manager and throws
`InvalidConfigurationException::providerNotRegistered()` when Laravel facades or
the provider are not initialized.

### `ComponentManager` (final)

Request/job-scoped service behind the facade, also available through constructor
injection. Managers from inactive applications or scopes reject use; debug output
hides the container and native serialization throws.

| Method | Description |
| --- | --- |
| `__construct(Container $container)` | created by the provider |
| `collect(iterable $components): ComponentCollection` | builds builders, skips `null`, flattens nested lists up to 32 levels |
| `tree(ComponentInterface $root): ComponentTree` | indexed tree for queries |
| `validate(ComponentInterface $root): ValidationResult` | collected schema validation of the whole tree |
| `normalize(ComponentInterface $root): array` | canonical array ([normalization](normalization.md)) |
| `normalizeValue(mixed $value, string $location = 'value'): mixed` | normalizes one property value |
| `inspect(ComponentInterface $root): ComponentInspection` | diagnostic snapshot of the root |
| `live(ComponentInterface\|Buildable $schema, State\|array $state = [], EvaluationContext\|array $context = []): LiveSchema` | starts the [live engine](reactivity.md) |
| `patch(ComponentInterface\|Buildable $schema): SchemaPatch` | starts an immutable [schema patch](patching.md) |
| `extend(string $schema, Closure $extension): void` | registers `fn (SchemaPatch $patch): SchemaPatch` under a key |
| `extended(string $schema, ComponentInterface\|Buildable $component): ComponentInterface` | applies every extension registered under the key, in order |
| `configurator(): ComponentConfigurator` | `configureUsing()` modifiers, engine macros, schema extensions |
| `evaluator(): Evaluator` | closure evaluator with injection |
| `walker(): ComponentWalker` | shared traversal service |
| `catalog(): ComponentCatalog` | scoped alias registry |
| `rememberDefinition(string $component, Closure $factory): ComponentDefinition` | per-scope schema cache used by `Component::definition()` |
| `define(string $component, array $items = []): ComponentBuilder` | *engine*; throws `InvalidConfigurationException::declarativeOnly()` for declarative classes |
| `make(string $component = Component::class, array $items = []): Component` | *engine*; builds immediately; same restriction |

### `ComponentServiceProvider` (final, extends `ServiceProvider`, implements `Gcego\Core\Contracts\PackageInterface`)

| Method | Description |
| --- | --- |
| `register(): void` | binds `ComponentManager` and `ComponentCatalog` as scoped; `ComponentWalker`, the validators, `ComponentNormalizer`, `ComponentInspector`, `ComponentConfigurator` and `Evaluator` as singletons; `ComponentBuilder` and `ComponentTree` as transient factories. Existing bindings are preserved. |
| `provides(): list<class-string>` | the twelve services above |
| `descriptor(): PackageDescriptor` | `gcego/components`, `requires: ['gcego/core']`, capabilities `components`, `components.schema`, `components.validation`, `components.normalization`, `components.traversal` |

See [Laravel integration](laravel.md).

## Declarative API

### `Contracts\DeclarativeComponent` (interface, extends `ComponentInterface`)

`static make(Definition ...$definitions): static` and
`withDefinitions(Definition ...$definitions): static`. Implemented by
`Foundation\SchemaComponent` (every catalog and custom component) and `Layout\Schema`.

### `Foundation\DeclarativeFactory` (trait)

| Member | Description |
| --- | --- |
| `static make(Definition ...$definitions): static` | expands and checks each definition against the schema, merges schema defaults, `presets()` and `configureUsing()` definitions beneath the explicit ones, refuses missing required properties and returns the immutable component |
| `withDefinitions(Definition ...$definitions): static` | schema-checked copy; properties are replaced by key, `Children` replaces the children and `Configurations` the configuration nodes |
| `static configureUsing(Closure $modifier, ?Closure $during = null, bool $isImportant = false): mixed` | the callback takes no arguments and returns a `Definition` or `list<Definition>`; anything else throws `InvalidConfigurationException::invalidDefaults()`. Returns the `$during` result when given. |
| `protected static presets(): list<Definition>` | definitions every instance starts with |
| `final __construct(mixed ...$items)` | runs only inside `make()`; otherwise throws `InvalidConfigurationException::declarativeOnly()` |
| `static define(mixed ...$items): never`, `static macro(string $name, Closure $macro): never` | closed; throw `declarativeOnly()` |
| `final protected static setUp(ComponentBuilder $builder): ComponentBuilder` | closed builder hook; use `presets()` |

### `Foundation\SchemaComponent` (abstract, extends `Component`, implements `DeclarativeComponent`)

Uses `DeclarativeFactory`. `protected static defineSchema(): ComponentDefinition`
adds the `CanBeHidden`, `CanSpanColumns` and `HasExtraAttributes` concerns. Extend
it for custom components ([extension guide](extension-guide.md)).

### `Contracts\Definition` (interface)

`items(): list<Property|ComponentInterface|ConfigurationNode>`. A definition
expands into engine items; the receiving component checks every one.

### `Validation\DefinitionValidator` (final)

| Method | Description |
| --- | --- |
| `const IdentityProperties = ['name', 'id']` | properties that refuse runtime values |
| `static expand(string $component, Definition $definition, ?ComponentDefinition $schema = null): list<Property\|ComponentInterface\|ConfigurationNode>` | expands one definition and runs `check()` on its items |
| `static check(string $component, string $source, iterable $items, ?ComponentDefinition $schema = null): void` | checks that each property is declared (or unknown properties are allowed) and satisfies its type, allowed values and rule (an explicit `null` passes for a nullable property); that callback properties hold a `RuntimeValue`; and that children and configuration nodes are accepted. `RuntimeValue` items (such as `Computed`) skip type checks, except for the identity properties. `$source` (a definition class, or `Property::class` for engine copies) is named in errors. Also run by the copy methods of declarative components. |
| `static assertComplete(Component $component, ?ComponentDefinition $schema = null): void` | checks the finished component: required properties, child-count bounds, schema constraints and unique ids in its subtree. Run by `make()`, `withDefinitions()` and the copy methods of declarative components. |
| `static expandDefinitions(string $component, array $definitions, ?ComponentDefinition $schema = null): list<...>` | expands a list, refusing non-definitions and repeated property keys |

Every method throws `InvalidDefinitionException`.

## Definitions

All definitions are `final readonly` classes under `Definitions\` implementing
`Contracts\Definition`; each validates its value in `make()` and throws
`InvalidDefinitionException` on bad input. See [definitions](definitions.md).

### Bases

| Class | Members |
| --- | --- |
| `PropertyValue` (abstract) | `final protected __construct(mixed $value)`, public `$value`, `abstract static key(): string`, `items(): list<Property>` |
| `TextValue` (abstract) | `static make(string $text): static` (non-empty plain text), `static using(Closure $text): static` (stores an `Evaluation\Computed` whose `$check` applies `make()` to the result) |
| `FlagValue` (abstract) | `static make(bool $enabled = true): static` |
| `TokenValue` (abstract) | `const Pattern`, `static make(string\|BackedEnum $token): static`, `static options(): ?list<string>` |
| `Condition` (abstract) | `static make(Closure $condition): static`, wrapping the callback in `Evaluation\Computed` |
| `Children` | `static make(iterable $children): self`, public `$components`, `items()` |
| `Configurations` | `static make(ConfigurationNode ...$nodes): self`, public `$nodes`, `items()` |

### Concrete definitions

Every concrete definition also exposes `static key(): string`. The key equals the
class name in lower camel case unless shown in parentheses.

| Base | Definitions |
| --- | --- |
| `TextValue` | `Action` (an action name or a safe URL), `Alt`, `Autocomplete`, `Content`, `DateLabel` (`date`), `Description`, `Format`, `HelpText`, `Label`, `Language`, `Message`, `Placeholder`, `Timezone`, `Title` |
| `FlagValue` | `Autoplay`, `Checked`, `Controls`, `Dehydrated`, `Disabled`, `Dismissible`, `Hidden`, `Live`, `Loading`, `Loop`, `Multiple`, `Muted`, `Open`, `ReadOnlyField` (`readOnly`), `Required`, `Searchable`, `Selectable`, `Sortable`, `Visible` |
| `TokenValue` | `Alignment` (`align`; options `Start`, `Center`, `End`, `Stretch`, `Baseline`), `CalendarView` (`view`), `ChartType`, `Color`, `Gap`, `Icon`, `Justify`, `Orientation` (options `Horizontal`, `Vertical`), `Size` (options `ExtraSmall`, `Small`, `Medium`, `Large`, `ExtraLarge`), `Status`, `Trigger`, `Variant` (constants `Primary`, `Secondary`, `Success`, `Warning`, `Danger`, `Info`, `Neutral`, `Ghost`, `Outline`, `Link`; any well-formed token) |
| `Condition` | `DisabledWhen` (`disabled`), `HiddenWhen` (`hidden`), `ReadOnlyWhen` (`readOnly`), `RequiredWhen` (`required`), `VisibleWhen` (`visible`) |
| `PropertyValue` | see below |

| `PropertyValue` definition | `make()` signature |
| --- | --- |
| `Accept`, `HiddenOn`, `VisibleOn` | `make(string ...$values)` |
| `Active` | `make(string\|bool $value = true)` |
| `AfterStateUpdated` | `make(Closure $hook)` |
| `Attributes` (`extraAttributes`) | `make(array $attributes)`; `const NamePattern` |
| `ButtonType` | `make(string $value)`; `const Button`, `Submit`, `Reset` |
| `Columns`, `ColumnStart` | `make(int\|array $value)` (one value or a breakpoint map) |
| `ColumnSpan` | `make(int\|string\|array $value)`, `full()` |
| `Data`, `Events`, `Options` | `make(array $values)` |
| `DefaultValue` (`default`) | `make(mixed $value)` (refuses closures and runtime values), `using(Closure $value)` (the result passes `make()`) |
| `Duration`, `MaxFiles`, `MaxLength`, `MaxSize`, `MinLength`, `Page`, `PageSize`, `Rows`, `Span`, `Total` | `make(int $value)` |
| `Field` | `make(string $field)` |
| `Height`, `Width` | `make(int\|string $value)` (`Support\Dimension`); `const Pattern` |
| `Href` (`url`), `Poster`, `Src` | `make(string $url)` (safe URL) |
| `Id` | `make(string $id)` |
| `Latitude`, `Longitude`, `Step`, `Zoom` | `make(int\|float $value)` |
| `Level` | `make(int $level)` |
| `Max`, `Min` | `make(string\|int\|float $value)` |
| `Method` | `make(string $value)`; `const Get`, `Post`, `Put`, `Patch`, `Delete` |
| `Name` | `make(string $name)`; `const Pattern` |
| `StatePath` | `make(string $path)` |
| `Target` | `make(string $target)`; `const Self`, `Blank`, `Parent`, `Top` |
| `Value` | `make(mixed $value)` |

## Catalog and components

### `Catalog\ComponentCatalog` (final)

Scoped alias registry with 88 built-in registrations; it never constructs components.

| Method | Description |
| --- | --- |
| `__construct()` | requires the provider |
| `register(string $alias, string $component): void` | adds a concrete `Component` subclass under a new camelCase alias; refuses reserved `Component` method names and case-insensitive duplicates |
| `resolve(string $alias): class-string<Component>` | exact alias lookup; unknown aliases throw `InvalidConfigurationException` |
| `all(): array<string, class-string<Component>>` | every registration |

### `Components\*`

Every class extends `Foundation\SchemaComponent` (directly or through an abstract
family base) and declares only `protected static defineSchema()`. Types are
`<family>.<alias>`, for example `inputs.textInput`. Properties and definitions per
component: [component catalog](component-catalog.md).

| Namespace | Concrete classes | Abstract bases (parent) |
| --- | --- | --- |
| `Components\Actions` (6) | `Button`, `ButtonGroup`, `Dropdown`, `DropdownItem`, `IconButton`, `SplitButton` | `Action` (`SchemaComponent`) |
| `Components\Display` (17) | `Avatar`, `Badge`, `CodeBlock`, `DescriptionList`, `Heading`, `Icon`, `KeyValueList`, `ListItem`, `ListView`, `Progress`, `Separator`, `Stat`, `Table`, `TableColumn`, `Text`, `Timeline`, `TimelineItem` | `TextualComponent` (`SchemaComponent`) |
| `Components\Feedback` (6) | `Alert`, `Banner`, `EmptyState`, `Skeleton`, `Spinner`, `Toast` | `Message` (`SchemaComponent`), `DismissibleMessage` (`Message`) |
| `Components\Inputs` (23) | `Checkbox`, `CodeEditor`, `ColorPicker`, `DatePicker`, `DateTimePicker`, `EmailInput`, `FileUpload`, `Form`, `HiddenInput`, `MultiSelect`, `NumberInput`, `PasswordInput`, `Radio`, `RangeInput`, `RichEditor`, `SearchInput`, `Select`, `TelInput`, `TextInput`, `Textarea`, `TimePicker`, `Toggle`, `UrlInput` | `FieldComponent` (`SchemaComponent`, implements `HoldsState`), `ChoiceField`, `NumericField`, `TemporalField`, `TextField` (all `FieldComponent`) |
| `Components\Layout` (16) | `Accordion`, `AccordionItem`, `Card`, `Column`, `Container`, `Fieldset`, `Grid`, `Panel`, `Row`, `Spacer`, `Splitter`, `Stack`, `Tab`, `Tabs`, `Wizard`, `WizardStep` | `LayoutBlock` (`SchemaComponent`), `TitledBlock` (`LayoutBlock`) |
| `Components\Media` (7) | `Audio`, `Calendar`, `Chart`, `Embed`, `Image`, `Map`, `Video` | `ImageComponent`, `PlayableMedia` (`SchemaComponent`) |
| `Components\Navigation` (6) | `Breadcrumb`, `Breadcrumbs`, `Link`, `Menu`, `MenuItem`, `Pagination` | `NavigationItem` (`SchemaComponent`) |
| `Components\Overlays` (4) | `Drawer`, `Modal`, `Popover`, `Tooltip` | `Overlay` (`TitledBlock`) |

`Display\Avatar` extends `Media\ImageComponent`; `Overlays\Popover` and
`Overlays\Tooltip` extend `Feedback\Message`.

## Layout

| Class | Type | Notes |
| --- | --- | --- |
| `Layout\Schema` | `schema` | extends `Component` directly, uses `DeclarativeFactory`, implements `DeclarativeComponent`; concerns `HasColumns`, `HasStatePath` (no visibility or column-span properties); not in the catalog |
| `Layout\Section` | `section` | extends `SchemaComponent`; concerns `HasColumns`, `HasStatePath` |
| `Layout\Group` | `group` | extends `SchemaComponent`; concerns `HasColumns`, `HasStatePath` |
| `Layout\Item` | `item` | extends `SchemaComponent`; `withoutChildren()` |

All four are non-final and declare only `protected static defineSchema()`. See
[composition](composition.md).

## Framework engine

### `Component` (implements `ComponentInterface`)

Members marked *engine* are construction and builder hooks for framework component
families; `DeclarativeFactory` closes or replaces them.

| Member | Description |
| --- | --- |
| `const Name`, `Label`, `Description`, `Id` | built-in property keys (`'name'`, `'label'`, `'description'`, `'id'`) |
| `const IdPattern` | `/\A[A-Za-z][A-Za-z0-9_.:\-]*\z/` |
| `__construct(mixed ...$items)` | *engine*: properties, definitions, children, configuration nodes and leading shorthand values |
| `static define(mixed ...$items): ComponentBuilder` | *engine*: start an immutable builder |
| `static collect(iterable $components): ComponentCollection` | dynamic component lists, skipping `null` |
| `static configureUsing(Closure $modifier, ?Closure $during = null, bool $isImportant = false): mixed` | *engine*: `Closure(ComponentBuilder): ComponentBuilder` applied by `Makeable::make()` for the class and subclasses |
| `static macro(string $name, Closure $macro): void`, `static hasMacro(string $name): bool` | *engine*: builder macros bound to the builder; they must return it |
| `static builderClass(): class-string<ComponentBuilder>` | *engine*: override to choose a custom builder |
| `final static shorthandKeys(): list<string>` | the keys from `shorthand()` |
| `final static definition(): ComponentDefinition` | the schema, cached per scope |
| `protected static defineSchema(): ComponentDefinition` | override to declare the schema; the base `Component` alone allows unknown properties |
| `protected static setUp(ComponentBuilder $builder): ComponentBuilder` | *engine*: per-class builder defaults for `Makeable::make()`, applied before `configureUsing()` |
| `protected static shorthand(): list<string>` | *engine*: keys for leading positional values |
| `type(): string`, `id(): ?string`, `name(): ?string` | schema type, explicit id, name |
| `properties(): PropertyCollection` | explicit properties |
| `get(string $key, mixed $fallback = null): mixed` | explicit value, then schema default, then fallback |
| `require(string $key): mixed` | throws `MissingPropertyException` without value or default |
| `has(string $key): bool`, `hasExplicit(string $key): bool` | with / without schema defaults |
| `defaults(): array`, `effectiveProperties(): array` | schema defaults; defaults merged with explicit values, sorted by key |
| `children(): ComponentCollection`, `childCount(): int`, `hasChildren(): bool` | |
| `configurations(): ConfigurationCollection` | |
| `isComputed(string $key): bool` | the value is an `Evaluation\Computed` |
| `evaluateProperty(string $key, EvaluationContext\|array $context = []): mixed` | calls a computed value with injection, runs its `Computed::$check`, then checks the result against the property definition; `null` falls back to the default; callback properties are returned uncalled |
| `evaluatedProperties(EvaluationContext\|array $context = []): array` | every effective property, evaluated |
| `isHiddenIn(EvaluationContext\|array $context = []): bool`, `isVisibleIn(...)` | `hidden`, `visible`, `hiddenOn`, `visibleOn` for the context's `operation` |
| `displayLabel(EvaluationContext\|array $context = []): ?string` | evaluated label, or one derived from the name (`first_name` → `First name`) |
| `validate(): ValidationResult`, `assertValid(): static` | memoized per instance and manager scope; strict mode throws `InvalidComponentException` |
| `normalize(): array`, `tree(): ComponentTree` | |
| `withProperty(Property ...)`, `withoutProperty(string ...)`, `withChildren(ComponentInterface ...)`, `withAppendedChildren(ComponentInterface ...)`, `withConfigurations(ConfigurationNode ...)` | immutable copies returning `static`. On declarative components they run `DefinitionValidator::check()` on the new items and `assertComplete()` on the copy; `withoutProperty()` refuses required properties (`InvalidDefinitionException::requiredRemoved()`) |

All accessors, evaluation methods, `validate()`, `assertValid()`, `normalize()`,
`tree()`, the copy methods and `__clone()` are `final`.

### `Building\ComponentBuilder` (implements `Buildable`)

The engine builder returned by `Component::define()` and `Makeable::make()`.
Declarative components expose no builder. Every modifier returns a modified copy
of the same concrete builder type.

| Area | Methods |
| --- | --- |
| Construction | `final __construct(string $component, array $items = [])`, `final componentClass(): string` |
| Properties | `set(string $key, mixed $value)`, `setMany(iterable $properties)`, `setIfMissing(string $key, mixed $value)`, `forget(string ...$keys)`, `onlyProperties(string ...$keys)`, `clearProperties()`, `replaceProperties(iterable $properties)` |
| Property reads | `get(string $key, mixed $fallback = null): mixed`, `has(string $key): bool`, `properties(): array` (explicit builder state only) |
| Dynamic setters | `__call(string $method, array $arguments): static`: registered macro first, then the property named by the class constant, the key or its snake case; a bool/mixed property without an argument is set to `true`; closures are stored as `Computed` |
| Named setters | `label(mixed $label)`, `columnSpanFull()`, `hiddenOn(string\|array ...$operations)`, `visibleOn(string\|array ...$operations)`, `extraAttributes(Closure\|array $attributes, bool $merge = false)` |
| Items | `with(mixed ...$items)` (properties, definitions, children, configuration nodes) |
| Children | `schema($children)`, `components($children)`, `child($children)` append; `replaceChildren($children)`, `prependChildren($children)`, `clearChildren()`; `filterChildren(callable $predicate)`, `mapChildren(callable $callback)` (child and index; map may return `null`); `children(): array`, `childCount(): int` |
| Configuration | `configure(ConfigurationNode ...)`, `replaceConfigurations(ConfigurationNode ...)`, `clearConfigurations()` |
| Composition | `tap(callable $callback)`, `apply(iterable $callbacks)`, `when(bool $condition, callable $callback, ?callable $otherwise = null)`, `unless(...)` |
| Terminal | `build(): Component`, `buildValidated(): Component`, `validate(): ValidationResult`, `normalize(): array`, `tree(): ComponentTree`, `inspect(): ComponentInspection` |
| Protected hooks | `instantiate(array $items): Component`, `buildItems(): array`, `prepare(array $items): array`, `propertyValue(string $key, mixed $fallback = null)`, `hasPropertyValue(string $key)`, `withoutConfiguration(string $class)`, `replaceConfiguration(ConfigurationNode $configuration)`, `configurationsOf(?string $class = null)`, `replaceChild(string $class, ComponentInterface\|Buildable $replacement)`, `childOf(string $class)`, `childItems()`, `withChildItems(array $children)`, `static produces(ComponentInterface\|Buildable $child, string $class)`, `propertyKey(string $method)` |

Child arguments accept a component, a builder, or a (nested) iterable of them up
to 32 levels; `null` entries are skipped. Each terminal call builds a fresh
component; `build()` and `normalize()` do not validate.

### `Foundation\Makeable` (trait)

`static make(mixed ...$items): ComponentBuilder` returns
`define(...$items)` passed through `setUp()` and the application's
`configureUsing()` modifiers. For engine families such as gcego/forms,
gcego/tables and gcego/infolists.

## Configuration

### `Configuration\ComponentConfigurator` (final)

Application singleton behind `Components::configurator()`.

| Method | Description |
| --- | --- |
| `configureUsing(string $component, Closure $modifier, ?Closure $during = null, bool $isImportant = false): mixed` | registers a modifier for the class and its subclasses; with `$during` it applies only while that callback runs and returns its result; important modifiers run last |
| `configure(ComponentBuilder $builder): ComponentBuilder` | applies matching modifiers |
| `hasModifiersFor(string $component): bool` | whether any modifier applies |
| `macro(string $component, string $name, Closure $macro): void`, `macroFor(string $component, string $name): ?Closure` | engine builder macros |
| `extend(string $schema, Closure $extension): void`, `extensionsFor(string $schema): list<Closure>` | schema extensions for `Components::extended()` |

## Concerns

`Contracts\Concern` (interface): `static properties(): list<PropertyDefinition>`.
`ComponentDefinition::withConcerns(class-string<Concern> ...)` merges them. The
concerns are `final` classes.

| Concern | Properties | Used by |
| --- | --- | --- |
| `Concerns\CanBeHidden` | `hidden`, `visible` (bool), `hiddenOn`, `visibleOn` (non-empty operation lists) | `SchemaComponent` |
| `Concerns\CanSpanColumns` | `columnSpan` (positive int or `full`, responsive), `columnStart` (positive int, responsive) | `SchemaComponent` |
| `Concerns\HasExtraAttributes` | `extraAttributes` (attribute map) | `SchemaComponent` |
| `Concerns\HasColumns` | `columns` (positive int, responsive) | layout schema containers, `Form`, `Card`, `Grid`, `Panel`, `Tab`, `Fieldset`, `WizardStep` |
| `Concerns\HasStatePath` | `statePath` (`const Pattern`) | the same containers |
| `Concerns\HasState` | `statePath`, `default`, `live`, `dehydrated`, `afterStateUpdated` (callback) | `Components\Inputs\FieldComponent` |

Responsive values take one value or a map keyed by `default`, `sm`, `md`, `lg`,
`xl`, `2xl` (`Support\Responsive::Breakpoints`).

## Properties and schema

### `Properties\Property` (final readonly)

`__construct(string $key, mixed $value = null)`, public `$key` and `$value`,
`const KeyPattern` (`/\A[A-Za-z_][A-Za-z0-9_.\-]{0,127}\z/`),
`static isSupportedValue(mixed $value): bool`. See [properties](properties.md).

### `Schema\PropertyDefinition` (final readonly)

`__construct(string $name, Type|list<Type> $type, bool $required = false, bool $nullable = false, mixed $default = Absent::Value, ?array $allowedValues = null, ?Closure $rule = null, ?string $ruleDescription = null, ?Closure $normalizer = null, ?string $description = null)`

Public properties: `name`, `types`, `required`, `nullable`, `hasDefault`,
`default`, `allowedValues`, `rule`, `ruleDescription`, `normalizer`, `description`.
Methods: `isCallback(): bool` (typed only as `Evaluation\Computed`),
`acceptsType(mixed): bool`, `allowsValue(mixed): bool`, `satisfiesRule(mixed): bool`,
`expectedType(): string`, `expectedValues(): string`, `expectedRule(): string`,
`toArray(): array`.

### `Schema\Field` (final readonly)

Fluent builder for a `PropertyDefinition` inside `defineSchema()`.

| Method | Description |
| --- | --- |
| `static of(string $name, Type\|array $type)` | any type(s) |
| `static string()`, `boolean()`, `integer()`, `number()`, `array()`, `list()`, `mixed()` | each takes `string $name` |
| `mustExist(bool $required = true)`, `nullable(bool $nullable = true)`, `defaultsTo(mixed $value)` | |
| `allowedValues(array $values)`, `rule(Closure $rule, ?string $description = null)`, `normalizeUsing(Closure $normalizer)`, `description(string $description)` | |
| `build(): PropertyDefinition` | |

Every modifier returns a new `Field`.

### `Schema\Type` (final readonly) and `Schema\TypeKind` (enum)

Factories: `string()`, `int()`, `float()`, `bool()`, `array()`, `list()`, `mixed()`,
`enum(class-string<UnitEnum> $enum)`, `classString(?class-string $parent = null)`,
`instanceOf(class-string $class)`. Public `kind` (`TypeKind`) and `class`.
Methods: `accepts(mixed $value): bool`, `describe(): string`.
`TypeKind` cases: `String`, `Int`, `Float`, `Bool`, `Array`, `List`, `Mixed`,
`Enum`, `ClassString` (`class-string`), `Instance`.

### `Schema\ComponentDefinition` (final readonly)

`__construct(string $type, list<PropertyDefinition> $properties = [], bool $allowsUnknownProperties = false, bool $allowsChildren = true, ?list<class-string> $allowedChildren = null, int $minChildren = 0, ?int $maxChildren = null, ?list<class-string> $allowedConfigurations = null, list<Closure> $constraints = [])`

`const TypePattern`. Public: `type`, `allowsUnknownProperties`, `allowsChildren`,
`allowedChildren`, `minChildren`, `maxChildren`, `allowedConfigurations`.

| Kind | Methods |
| --- | --- |
| Queries | `hasProperty(string)`, `property(string): ?PropertyDefinition`, `properties()`, `propertyNames()`, `requiredProperties()`, `defaults()`, `constraints()`, `allowsChild(ComponentInterface)`, `allowsConfiguration(ConfigurationNode)` (placement configuration is always accepted), `toArray()` |
| Modifiers (new definition) | `withType(string)`, `withProperties(PropertyDefinition ...)`, `withFields(iterable<Field\|PropertyDefinition>)`, `withConcerns(class-string<Concern> ...)`, `withoutProperties(string ...)`, `withUnknownProperties(bool $allowed = true)`, `withAllowedChildren(?array $types, int $min = 0, ?int $max = null)`, `withoutChildren()`, `withAllowedConfigurations(?array $types)`, `withConstraint(Closure)` |

Properties added under an existing name replace it.

## Collections

`final readonly`, `Countable`, `IteratorAggregate`, variadic constructors.

| Class | Methods |
| --- | --- |
| `Collections\PropertyCollection` | `has(string)`, `get(string): ?Property`, `value(string, mixed $fallback = null)`, `keys()`, `all()`, `toArray()`, `with(Property ...)`, `without(string ...)`, `isEmpty()`, `count()` |
| `Collections\ComponentCollection` | `get(int): ?ComponentInterface`, `has(int)`, `first()`, `all()`, `filter(Closure)`, `ofType(string)`, `toArray()`, `isEmpty()`, `count()` |
| `Collections\ConfigurationCollection` | `has(class-string)`, `first(class-string)`, `ofClass(class-string)`, `all()`, `isEmpty()`, `count()` |

## Evaluation

See [reactivity](reactivity.md).

| Class | Members |
| --- | --- |
| `Evaluation\Computed` (final readonly, `RuntimeValue`) | `__construct(Closure $closure, ?Closure $check = null)`, public `$closure`, `$check` (`Closure(mixed): mixed`, applied to a non-null result; returns the value to use or throws `InvalidDefinitionException`) |
| `Evaluation\EvaluationContext` (final readonly) | `__construct(array $parameters = [], array $typed = [], ?State $state = null, string $statePath = '')`; public `parameters`, `typed`, `state`, `statePath`; `static make(array $parameters = [])`, `static from(self\|array $context)`, `with(string $name, mixed $value)`, `withTyped(object ...$values)`, `withState(State\|array $state, string $statePath = '')`, `operation(): ?string` |
| `Evaluation\Evaluator` (final) | `__construct(Container $container)`, `evaluate(mixed $value, EvaluationContext $context, ?ComponentInterface $component = null, string $subject = 'a computed value'): mixed`. Parameters resolve by name (context parameters, `$component`, and with state `$get`, `$set`, `$state`), then by type (component, context objects, typed values), then from the container, then their default or `null`; otherwise `EvaluationException` with a suggestion. |
| `Evaluation\State` (final) | `__construct(array $data = [])`, `get(string $path, mixed $default = null)`, `set(string $path, mixed $value): void`, `has(string $path): bool`, `peek(string $path, mixed $default = null)` (unrecorded read), `all(): array`, `record(Closure $callback): array{result, reads, writes}` |
| `Evaluation\Get` (final readonly, invokable) | `__construct(State $state, string $containerPath = '')`, `__invoke(string $path = '', bool $isAbsolute = false): mixed`, `string(string $path, bool $isAbsolute = false): ?string`, `boolean(...)`, `filled(...)`, `blank(...)` |
| `Evaluation\Set` (final readonly, invokable) | `__construct(State $state, string $containerPath = '')`, `__invoke(string $path, mixed $value, bool $isAbsolute = false): void` |
| `Evaluation\StatePath` (final) | `static resolve(string $containerPath, string $path, bool $isAbsolute = false): string`; `../` moves up one container |

## Live

See [reactivity](reactivity.md).

| Class | Members |
| --- | --- |
| `Live\LiveSchema` (final) | `schema(): ComponentInterface`, `state(): State`, `root(): ResolvedComponent`, `at(string $componentPath): ?ResolvedComponent`, `field(string $statePath): ?ResolvedComponent`, `isLive(string $statePath): bool`, `livePaths(): list<string>`, `dependentsOf(string $statePath): list<string>`, `set(string $statePath, mixed $value): Changes`, `setMany(array $values): Changes`, `dehydrate(): array`, `toArray(): array` |
| `Live\ResolvedComponent` (final readonly) | public `component`, `path`, `containerPath`, `statePath`, `properties` (evaluated, without `default` and callbacks), `hidden`, `label`, `dependencies` (state paths read), `children`, `parentPath` (the parent's tree path; `null` for the root); `type()`, `name()`, `get(string $key, mixed $fallback = null)`, `holdsState()`, `isVisible()` |
| `Live\Changes` (final readonly) | public `statePaths`, `components` (tree paths, in order); `isEmpty()`, `touches(string $componentPath)`, `toArray()` |

`LiveSchema` is created by `Components::live()`; its constructor takes the
schema, `State`, `EvaluationContext`, `ComponentWalker` and `Evaluator`.
`set()` re-resolves only the components affected by the written paths.
`toArray()['state']` holds only the values of visible state holders; `state()`
holds everything. `ResolvedComponent::withChildren()` and `Live\Evaluated` are
internal.

## Patching

### `Patching\SchemaPatch` (final)

Created by `Components::patch()`; every method except `apply()` returns the patch.
See [patching](patching.md).

| Method | Description |
| --- | --- |
| `__construct(ComponentInterface $schema)` | |
| `modify(string $selector, Definition\|Closure $change, Definition ...$definitions)` | definitions for declarative components (applied with `withDefinitions()`); a `Closure(ComponentBuilder): ComponentBuilder` only for engine components |
| `remove(string $selector)` | |
| `insertBefore(string $selector, ComponentInterface\|Buildable ...$components)`, `insertAfter(...)` | siblings of each match |
| `append(string $selector, ComponentInterface\|Buildable ...$components)`, `prepend(...)` | children of each match |
| `apply(): ComponentInterface` | the patched copy; the original is unchanged |

Selectors are dot-separated name chains matched against the end of tree paths
(`email`, `account.email`, `name#1`). Unmatched selectors, selectors that name
only the root, refused children, child counts outside a container's limits and
declarative copies that fail the `make()` checks throw `PatchException`.

## Trees

| Class | Members |
| --- | --- |
| `Trees\ComponentTree` (final, `Countable`, `IteratorAggregate`) | `__construct(ComponentInterface $root, ComponentWalker $walker)`, `root()`, `rootNode()`, `depthFirst()`, `breadthFirst()`, `find(ComponentPath\|string $path): ?TreeNode`, `contains(ComponentPath\|string)`, `findById(string): ?TreeNode`, `findByType(string): list<TreeNode>`, `parentOf(ComponentPath\|TreeNode\|string)`, `childrenOf(ComponentPath\|TreeNode\|string)`, `descendantCount(ComponentPath\|TreeNode\|string\|null $node = null)`, `depth()`, `duplicateIds()`, `count()` |
| `Trees\ComponentPath` (final, `Stringable`) | `const Separator = '.'`, `static root(string)`, `static fromSegments(array)`, `static fromString(string)`, `child(string)`, `parent(): ?self`, `segments()`, `last()`, `depth()`, `isAncestorOf(self)`, `equals(self)`, `toString()` |
| `Trees\TreeNode` (final readonly) | public `component`, `path`, `depth`, `index`, `parentPath`; `isRoot()`, `identity(): ComponentIdentity` |

Relationship methods resolve `TreeNode` arguments by path and component identity;
nodes from unrelated trees return `null`, an empty list or zero.

## Traversal

| Class | Members |
| --- | --- |
| `Traversal\ComponentWalker` (final readonly) | `const DefaultMaxDepth = 256`, `__construct(int $maxDepth = 256)`, `traverse(ComponentInterface $root, TraversalOrder $order = PreOrder): Generator`, `preOrder()`, `postOrder()`, `breadthFirst()`, `walk(ComponentInterface $root, Closure $visitor, TraversalOrder $order = PreOrder): void` (visitor may return `Visit`), `ofType(ComponentInterface $root, string $type, TraversalOrder $order = PreOrder): Generator`, `rootNode(ComponentInterface)`, `childNodes(TreeNode)` |
| `Traversal\TraversalOrder` (enum) | `PreOrder`, `PostOrder`, `BreadthFirst` |
| `Traversal\Visit` (enum) | `Continue`, `SkipChildren`, `Stop` |
| `Traversal\AncestorChain` (final readonly) | `__construct(int $objectId, ?self $parent = null)`, `contains(int $objectId): bool`; cycle detection |

## Validation

| Class | Members |
| --- | --- |
| `Validation\ComponentValidator` (final readonly) | `validate(ComponentInterface $root): ValidationResult`, `assertValid(ComponentInterface $root): void` |
| `Validation\PropertyValidator` (final) | `validate(TreeNode, ComponentDefinition): list<ValidationError>`, `validateValue(TreeNode, PropertyDefinition, mixed): ?ValidationError` |
| `Validation\ChildValidator` (final) | `validate(TreeNode, ComponentDefinition, list<TreeNode>): list<ValidationError>` |
| `Validation\DefinitionValidator` (final) | see [declarative API](#validationdefinitionvalidator-final) |
| `Validation\ValidationResult` (final readonly, `Countable`, `IteratorAggregate`) | `isValid()`, `hasErrors()`, `errors()`, `first()`, `errorsAt(string $path)`, `errorsWithCode(ErrorCode)`, `merge(self)`, `throwIfInvalid()`, `format()`, `toArray()`, `count()` |
| `Validation\ValidationError` (final readonly, `Stringable`) | public `code`, `message`, `path`, `componentType`, `componentClass`, `subject`, `expected`, `received`, `hint`; `static at(TreeNode $node, ErrorCode $code, string $message, ...)`, `static displayName(TreeNode)`, `format()`, `toArray()` |
| `Validation\ErrorCode` (string enum) | `MissingProperty`, `InvalidType`, `InvalidValue`, `UnknownProperty`, `ChildrenNotAllowed`, `InvalidChild`, `TooFewChildren`, `TooManyChildren`, `InvalidConfiguration`, `DuplicateId`, `ConstraintFailed` ([validation](validation.md)) |

## Normalization

| Class | Members |
| --- | --- |
| `Normalization\ComponentNormalizer` (final readonly) | `__construct(ComponentWalker $walker, bool $includeSchema = false)`, `normalize(ComponentInterface $root): array`, `normalizeValue(mixed $value, string $location = 'value'): mixed`, `normalizeProperties(array $values, ComponentDefinition $definition, string $location = 'component'): array` |
| `Normalization\RuntimePlaceholder` (final readonly) | public `class`; replaces `RuntimeValue` instances such as `Computed` |

## Inspection

| Class | Members |
| --- | --- |
| `Inspection\ComponentInspector` (final readonly) | `inspect(ComponentInterface): ComponentInspection` (errors for the whole subtree), `inspectTree(ComponentInterface): list<ComponentInspection>` (each node's own errors) |
| `Inspection\ComponentInspection` (final readonly) | public `class`, `type`, `id`, `path`, `depth`, `supportedProperties`, `requiredProperties`, `allowsUnknownProperties`, `explicitProperties`, `effectiveProperties`, `defaults`, `childCount`, `childrenAllowed`, `allowedChildren`, `minChildren`, `maxChildren`, `configurationClasses`, `errors`; `isValid()`, `toArray()` |

## Contracts

| Interface | Members / purpose |
| --- | --- |
| `Contracts\ComponentInterface` | extends `PropertyAware`, `HasChildren`, `Validatable`, `Normalizable`; `static definition()`, `type()`, `id()`, `configurations()`, `normalize(): array` |
| `Contracts\DeclarativeComponent` | `static make(Definition ...)`, `withDefinitions(Definition ...)` |
| `Contracts\HoldsState` | marker for state holders (`FieldComponent`) used by the live engine |
| `Contracts\PropertyAware` | `properties()`, `get()`, `has()`, `effectiveProperties()` |
| `Contracts\HasChildren` | `children()`, `childCount()`, `hasChildren()` |
| `Contracts\Validatable` | `validate()`, `assertValid(): static` |
| `Contracts\Normalizable` | `normalize(): mixed` |
| `Contracts\Buildable` | `build(): ComponentInterface` (implemented by `ComponentBuilder`) |
| `Contracts\Definition` | `items()` |
| `Contracts\Concern` | `static properties()` |
| `Contracts\ConfigurationNode` | non-renderable item (extends `Normalizable`) |
| `Contracts\PlacementConfiguration` | configuration node every component accepts, read by its parent (extends `ConfigurationNode`) |
| `Contracts\ValueObject` | immutable object property value (extends `Normalizable`) |
| `Contracts\RuntimeValue` | opaque runtime-only property value (marker) |

## Support

Public helpers used by the package internals. They are stable but rarely needed
by applications.

| Class | Members |
| --- | --- |
| `Support\SafeUrl` (final) | `const Schemes = ['http', 'https', 'mailto', 'tel']`, `const ExecutableSchemes = ['javascript', 'vbscript']`, `const Description`; `static isSafe(string $url): bool` (relative URLs or those schemes; protocol-relative URLs are refused), `static accepts(mixed $value): bool` (schema rule form), `static hasExecutableScheme(string $value): bool` |
| `Support\SafeAttributes` (final) | `const NamePattern`, `ExecutablePattern`, `Description`, `UrlAttributes`, `SourceSetAttributes`; `static violation(array $attributes): ?array{expectation: string, value: mixed}`, `static isSafe(mixed $attributes): bool` (schema rule form). Shared by `Definitions\Attributes` and `HasExtraAttributes` |
| `Support\Dimension` (final) | `const Pattern`, `const Description`; `static accepts(mixed $value): bool` (a positive int or a length token such as `full`, `50%`, `24rem`) |
| `Support\Responsive` (final) | `const Breakpoints`, `static accepts(mixed $value, Closure $accepts): bool`, `static describe(string $item): string` |
| `Support\Presentation` (final) | `static isHidden(array $properties, ?string $operation): bool`, `static labelFromName(string $name): string`, `static label(mixed $label, ?string $name): ?string` |
| `Support\ComponentIdentity` (final readonly) | public `type`, `explicitId`, `path`; `occurrenceKey()`, `stableKey()` (`id:…` or `path:…`), `hasExplicitId()` |
| `Support\ConstructorArguments` (final) | `static parse(string $componentClass, array $shorthandKeys, array $arguments): ParsedArguments` |
| `Support\ParsedArguments` (final readonly) | public `properties`, `children`, `configurations` |
| `Support\ValueDescriber` (final) | `static describe(mixed)`, `static type(mixed)`, `static className(string)` for diagnostics |
| `Support\ValueSnapshot` (final) | `static copy(mixed $value): mixed` (detaches array references) |
| `Support\Absent` (enum) | `Value`: the "no default" marker for `PropertyDefinition` |

## Exceptions

Every exception implements `Exceptions\ComponentException` (extends `Throwable`)
and is created through static factories.

| Exception (parent) | Factories |
| --- | --- |
| `InvalidDefinitionException` (`InvalidArgumentException`) | `invalidValue(string $definition, string $expectation, mixed $value)`, `unsupported(string $component, string $definition, string $key, array $accepted, ?string $suggestion = null)` (lists accepted properties, "Did you mean"), `rejectedValue(...)`, `duplicate(string $component, string $key)`, `childNotAccepted(...)`, `configurationNotAccepted(...)`, `missing(string $component, array $keys)`, `notADefinition(string $component, mixed $value)`, `tooFewChildren(string $component, int $minimum, int $count)`, `tooManyChildren(string $component, int $maximum, int $count)`, `constraintFailed(string $component, string $message)`, `duplicateId(string $component, string $id)`, `requiredRemoved(string $component, array $keys)` |
| `InvalidConfigurationException` (`InvalidArgumentException`) | `inactiveManager()`, `managerNotSerializable()`, `providerNotRegistered()`, `namedArgument()`, `unsupportedArgument()`, `shorthandAfterItem()`, `invalidDefinition(string $reason)`, `notNormalizable(string $location, mixed $value)`, `declarativeOnly(string $componentClass, string $attempted)`, `invalidDefaults(string $componentClass, mixed $value)` |
| `InvalidBuilderException` (`BadMethodCallException`) | `notAComponent()`, `notInstantiableComponent()`, `invalidBuilder()`, `wrongComponent()`, `nestingTooDeep()`, `unknownMethod()`, `missingValue()`, `tooManyValues()`, `unsupportedItem()`, `invalidChild()`, `positionalAfterItem()`, `tooManyPositional()`, `invalidValue(string $componentClass, string $method, string $reason)` |
| `InvalidPropertyException` (`InvalidArgumentException`) | `invalidKey(string $key)`, `unsupportedValue(string $key, string $location, string $actualType)` |
| `DuplicatePropertyException` (`InvalidPropertyException`) | `forKey(string $key, ?string $componentClass = null, ?Throwable $previous = null)` |
| `MissingPropertyException` (`OutOfBoundsException`) | `forKey(string $key, string $componentType, string $componentClass)` |
| `InvalidChildException` (`InvalidArgumentException`) | `cycle(string $path, string $componentClass)`, `nestingTooDeep(string $path, int $maxDepth)` |
| `InvalidComponentException` (`DomainException`) | `fromResult(ValidationResult)`, `result(): ValidationResult` |
| `EvaluationException` (`RuntimeException`) | `unresolvableParameter(string $subject, string $parameter, array $available, ?string $suggestion, bool $needsState = false)`, `invalidResult(string $subject, string $expected, mixed $result)`, `refusedResult(string $subject, InvalidDefinitionException $refusal, mixed $result)` |
| `PatchException` (`InvalidArgumentException`) | `noMatch(string $selector, ?string $suggestion)`, `rootOperation(string $operation)`, `notAComponent(string $selector, string $operation)`, `invalidModification(string $selector)`, `childNotAccepted(string $path, string $childType)`, `childCount(string $path, int $count, int $minimum, ?int $maximum)`, `invalidResult(string $path, InvalidDefinitionException $previous)` |
