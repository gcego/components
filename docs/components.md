# Components

A component is an immutable object with a schema type, a set of properties,
ordered child components and configuration nodes. This page covers declaring,
reading, copying and evaluating components. The construction path for framework
package authors is described at the end.

## Declaring components

Every catalog and custom component (any class implementing
`Contracts\DeclarativeComponent`, which includes subclasses of
`Foundation\SchemaComponent` and the `Layout\*` classes) is declared one way:
its `make()` factory with typed definitions. `make()` returns the immutable
component.

```php
use Gcego\Components\Components\Display\Stat;
use Gcego\Components\Definitions\{Children, Label, Name, Value};
use Gcego\Components\Layout\{Group, Schema, Section};

$schema = Schema::make(Children::make([
    Section::make(Name::make('dashboard'), Label::make('Dashboard'), Children::make([
        Group::make(Name::make('metrics'), Children::make([
            Stat::make(Name::make('users'), Label::make('Users'), Value::make('128')),
        ])),
    ])),
]))->assertValid();
```

`make(Definition ...$definitions)` checks each definition against the
component's schema before the component exists:

- every property a definition sets must be declared by the schema, and its value
  must satisfy the declared type, allowed values and rule (an explicit `null`
  passes when the property is `nullable`; a runtime value is checked when
  evaluated, except that `name` and `id` refuse one);
- every child must be accepted (`withoutChildren()`, `withAllowedChildren()`)
  and every configuration node must be allowed;
- the same property may not be set twice;
- after presets and `configureUsing()` defaults are applied, every required
  property must be present, the child count must be within the schema's
  `min`/`max` bounds, every schema constraint must pass, and no explicit id may
  appear twice in the component's subtree.

Each failure throws `InvalidDefinitionException`:

```text
Gcego\Components\Components\Display\Text does not accept Gcego\Components\Definitions\Required (property "required"). Accepted properties: name, label, description, id, hidden, visible, hiddenOn, visibleOn, columnSpan, columnStart, extraAttributes, content, color.
Gcego\Components\Components\Display\Text received property "content" more than once. Pass each definition once.
Gcego\Components\Components\Display\Text does not accept Gcego\Components\Components\Display\Text as a child.
Gcego\Components\Components\Inputs\TextInput requires "name".
Gcego\Components\Components\Actions\SplitButton needs at least 1 child component(s), 0 given.
Gcego\Components\Components\Inputs\TextInput: minLength must not exceed maxLength.
Gcego\Components\Components\Layout\Stack contains id "save" more than once. Ids must be unique within a tree; give each copy its own Id::make().
```

The last four come from `DefinitionValidator::assertComplete()`, which checks
the finished component. `validate()` and `assertValid()` apply the same rules
to whole trees, including trees no `make()` checked (see
[validation](validation.md)).

`make()` accepts `Contracts\Definition` objects only. A positional value is a
`TypeError`. The constructor, `define()`, `macro()` and `Components::define()`
throw `InvalidConfigurationException` for declarative classes:

```text
Gcego\Components\Components\Display\Text is declared only through Gcego\Components\Components\Display\Text::make() with typed definitions, for example Gcego\Components\Components\Display\Text::make(Label::make('Save')); new Gcego\Components\Components\Display\Text(...) is not available.
```

Defaults come from three layers, lowest first: the schema's property defaults,
the class's `presets()`, and application `configureUsing()` callbacks. Explicit
definitions override all of them. See [definitions](definitions.md) for every
built-in definition, presets, `configureUsing()` and custom definitions, and the
[catalog](component-catalog.md) for the definitions each component accepts.

## Built-in properties

Every component has these base properties:

| Constant | Key | Type | Definition | Notes |
| --- | --- | --- | --- | --- |
| `Component::Name` | `name` | `string` | `Name` | non-empty; used for tree paths |
| `Component::Label` | `label` | `string` | `Label` | |
| `Component::Description` | `description` | `string` | `Description` | |
| `Component::Id` | `id` | `string` | `Id` | explicit stable id matching `Component::IdPattern` (`/\A[A-Za-z][A-Za-z0-9_.:\-]*\z/`) |

`SchemaComponent` subclasses (the catalog, `Section`, `Group`, `Item`) add the
`CanBeHidden` (`hidden`, `visible`, `hiddenOn`, `visibleOn`), `CanSpanColumns`
(`columnSpan`, `columnStart`) and `HasExtraAttributes` (`extraAttributes`)
concerns. `Schema` extends `Component` directly and has `columns` and `statePath`
instead. Each catalog component adds its own properties.

## Reading properties

```php
$component->type();                             // schema type identifier, e.g. 'display.text'
$component->name();                             // ?string
$component->id();                               // ?string, explicit id only, never generated
$component->get('label');                       // explicit value, else schema default, else null
$component->get('label', 'Untitled');           // ... else the given fallback
$component->require('label');                   // throws MissingPropertyException when absent
$component->has('label');                       // explicit value or schema default exists
$component->hasExplicit('label');               // explicitly declared
$component->properties();                       // PropertyCollection of explicit values
$component->defaults();                         // schema defaults
$component->effectiveProperties();              // defaults merged with explicit values, sorted by key
Text::definition();                             // ComponentDefinition, cached per provider scope
```

`require()` fails with:

```text
Component "display.text" (Gcego\Components\Components\Display\Text) has no value for property "color" and its definition declares no default.
```

`PropertyCollection` offers `has()`, `get()` (`?Property`), `value($key,
$fallback)`, `keys()`, `all()`, `toArray()`, `count()` and iteration. A
`Property` has public `key` and `value`.

An explicit `null` is not the same as leaving the property out: it wins over the
default, and it is valid only when the property definition is `nullable`.

## Children and configuration nodes

```php
$component->children();        // ComponentCollection, declaration order
$component->childCount();      // int
$component->hasChildren();     // bool
$component->configurations();  // ConfigurationCollection, declaration order
$component->tree();            // ComponentTree rooted at this component
```

`ComponentCollection` offers `get(int)`, `has(int)`, `first()`, `all()`,
`toArray()`, `filter(Closure(ComponentInterface, int): bool)`, `ofType(string)`
(type identifier or class/interface), `isEmpty()`, `count()` and iteration.
`ConfigurationCollection` offers `has(class)`, `first(class)`, `ofClass(class)`,
`all()`, `isEmpty()`, `count()` and iteration. See [composition](composition.md).

`Component::collect()` (also `Components::collect()`) turns a dynamic iterable
into an immutable `ComponentCollection`. It builds builders, skips `null`
entries and flattens nested iterables up to 32 levels; anything else throws
`InvalidBuilderException`. Use it to prepare a list for `Children::make()`:

```php
use Gcego\Components\Component;

$children = Component::collect([$header, $showStats ? $stats : null, $rows]);
Card::make(Children::make($children));
```

## Copies

Components are immutable. Declarative components are copied with typed
definitions, under the same checks as `make()`:

```php
$updated = $button->withDefinitions(Label::make('Updated'));
$restyled = $card->withDefinitions(Children::make([$heading]), Configurations::make($style));
```

Properties are added or replaced. `Children` replaces all children and
`Configurations` replaces all configuration nodes; `Children::make([])` removes
them. Passing no definitions returns the same instance. The copy is checked as
a whole, like the result of `make()`: `Children::make([])` on a `SplitButton`
throws `… needs at least 1 child component(s), 0 given.`

`Component` also exposes engine copy methods, which every component inherits:

```php
$updated = $component->withProperty(new Property('label', 'Updated'));
$fewer = $component->withoutProperty('label');
$replaced = $component->withChildren($a, $b);
$appended = $component->withAppendedChildren($c);
$styled = $component->withConfigurations($style);
```

On declarative components these run the checks `make()` applies
(`DefinitionValidator::check()` on the new items, then
`DefinitionValidator::assertComplete()` on the copy), so a copy can never hold
a value `make()` would refuse, and `withoutProperty()` refuses to remove a
required property (`… TextInput cannot remove required "name".`). On engine
components they check only supported value kinds; schema rules are left to
`validate()`. Each copy is a new instance of the same concrete class, and its
validation result is computed independently of the original.

## Identity and equality

| Concept | API |
| --- | --- |
| Component type | `$component->type()`, set by `ComponentDefinition::$type` |
| Explicit id | `Id::make('main-table')`, read with `$component->id()` |
| Occurrence | `TreeNode::$path`, `TreeNode::identity()` |

`TreeNode::identity()` returns a `Support\ComponentIdentity` with `type`,
`explicitId` and `path`. `occurrenceKey()` is the path string, unique within
one tree and deterministic for an unchanged tree. `stableKey()` is `id:<id>`
when an explicit id is declared, otherwise `path:<path>`. `hasExplicitId()`
tells them apart.

Ids are optional. Declare one only when a component needs stable identity across
requests or stateful interactions. Ids must be unique within a tree:
`make()` refuses a duplicate as soon as the parent holding both copies is
made, and `validate()` reports one in any other tree as `DuplicateId`.

Components have no `equals()` method. `===` compares instances. Compare
`normalize()` output for structural equality: it is deterministic, with
properties sorted by key (see [normalization](normalization.md)).

## Validation and normalization

```php
$component->validate();     // ValidationResult for the whole subtree, memoized in the active scope
$component->assertValid();  // returns $component or throws InvalidComponentException
$component->normalize();    // canonical array
```

See [validation](validation.md) and [normalization](normalization.md).

## Evaluation

Definitions such as `VisibleWhen::make(fn ...)`, `Label::using(fn ...)` and
`DefaultValue::using(fn ...)` store an `Evaluation\Computed` value. `get()`
returns the `Computed` object itself; nothing is called until evaluation:

```php
use Gcego\Components\Evaluation\EvaluationContext;

$text = Text::make(Name::make('billing.first_name'), Label::using(fn (string $prefix): string => $prefix.' name'));

$text->isComputed('label');                            // true
$text->evaluateProperty('label', ['prefix' => 'Full']); // 'Full name'
$text->evaluatedProperties(['prefix' => 'Full']);       // every effective property, evaluated
$text->isHiddenIn(['operation' => 'edit']);             // hidden/visible/hiddenOn/visibleOn
$text->isVisibleIn(EvaluationContext::from([])->withState(['show' => true]));
$text->displayLabel(['prefix' => 'Full']);              // evaluated label, else derived from name
```

`evaluateProperty()` injects closure parameters by name and type from the
context, plus `$component`, and `$get`, `$set` and `$state` when the context
carries state. A `null` result falls back to the property default; any other
result passes the creating definition's own checks (`Label::using()` refuses
empty text like `Label::make()`), then the property definition, and an invalid
result throws `EvaluationException`. Callback properties such as `afterStateUpdated` are
returned uncalled. `displayLabel()` derives a label from the last name segment
when no label is set: `billing.first_name` reads `First name`. See
[reactivity](reactivity.md) for the evaluator, contexts and the live engine.

## Engine construction (package authors)

This section is for GCEGO framework packages that build their own component
families on the engine. Application code uses `make()` with definitions.

The base `Component` class is the engine. Engine classes, which extend
`Component` without implementing `DeclarativeComponent`, are constructed from
items:

```php
use Gcego\Components\Component;
use Gcego\Components\Properties\Property;
use Gcego\Components\Schema\{ComponentDefinition, PropertyDefinition, Type};

final class SearchColumn extends Component
{
    public const string Searchable = 'searchable';

    protected static function shorthand(): array
    {
        return [self::Name, self::Label];
    }

    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()
            ->withType('tables.column.search')
            ->withProperties(new PropertyDefinition(self::Searchable, Type::bool(), default: false))
            ->withoutChildren();
    }
}

$column = new SearchColumn('email', 'Email', new Property(SearchColumn::Searchable, true));
```

The constructor accepts `Property` objects, child components
(`ComponentInterface`), builders (`Contracts\Buildable`, built immediately),
configuration nodes (`ConfigurationNode`) and typed definitions, in any order.
`Support\ConstructorArguments` classifies them by contract only and returns a
`Support\ParsedArguments` (`properties`, `children`, `configurations`). Typed
definitions are checked against the schema as in `make()`. `Property` values
are only checked for supported value kinds at construction (see
[properties](properties.md)); schema rules are checked by `validate()`.
Children and configuration nodes keep their declaration order.

Structural errors throw immediately:

| Problem | Exception and message |
| --- | --- |
| positional value with no shorthand | `InvalidConfigurationException`: `Unsupported constructor argument #1 (string "example") for Gcego\Components\Component.` followed by `Component accepts no positional shorthand values. ...` |
| more positional values than shorthand keys | `InvalidConfigurationException`: `... SearchColumn accepts 2 positional shorthand value(s): name, label. ...` |
| positional value after an item | `InvalidConfigurationException`: `Positional shorthand values must precede every Property, child component and configuration node.` |
| named argument | `InvalidConfigurationException`: `Named argument "name" is not supported by SearchColumn. ...` |
| same key twice | `DuplicatePropertyException`: `Property "name" is declared more than once on SearchColumn. ...` |

### Shorthand

`shorthand()` lists, in order, the property keys that leading positional values
are assigned to. `new SearchColumn('email', 'Email')` is exactly
`new SearchColumn(new Property(SearchColumn::Name, 'email'), new Property(SearchColumn::Label, 'Email'))`.
Shorthand values must come first, and you may pass fewer values than keys but
not more. `Component::shorthandKeys()` reads the list. Declarative components
never accept shorthand.

### Builders and `Makeable`

`define()` (or `Components::define($class, $items)`) returns a
`Building\ComponentBuilder`. Unknown methods set the property they map to
(`searchable()` sets `SearchColumn::Searchable`; a call without a value turns a
bool property on), closures become `Computed` values, and builders are built
when placed in a parent or when `build()` is called. `Components::make($class,
$items)` builds one directly. Builders are immutable; see
[composition](composition.md#engine-builder-transformations) and the
[API reference](api-reference.md#framework-engine).

The `Foundation\Makeable` trait gives an engine class a Filament-style
`make(mixed ...$items): ComponentBuilder` entry point. It runs `define()`, then
the class's `setUp(ComponentBuilder)` hook, then application `configureUsing()`
modifiers:

```php
use Gcego\Components\Foundation\Makeable;

final class SearchColumn extends Component
{
    use Makeable;
    // ...
}

$column = SearchColumn::make('email', 'Email')->searchable()->build();
```

Engine classes also support `configureUsing(Closure(ComponentBuilder): ComponentBuilder)`,
`macro()`/`hasMacro()` and `builderClass()` for a custom builder. Declarative
components close `define()` and `macro()`, make `setUp()` final, and take
`configureUsing()` callbacks that return definitions instead.
