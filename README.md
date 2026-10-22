# gcego/components

A lightweight, strongly typed, declarative UI component-definition library for
**GCEGO Studio** applications. Components describe *what* a screen contains:
immutable, validated, renderer-neutral PHP objects. `gcego/rendering` turns
them into HTML and `gcego/runtime` executes them; this package does neither.
`gcego/core` is its only GCEGO dependency.

What the package provides:

- **85 catalog components** (actions, inputs, display, layout, navigation,
  feedback, overlays, media) plus the `Schema`, `Section`, `Group` and `Item`
  page-schema containers.
- **One declaration API**: `Component::make(Definition::make(...))`, with a
  typed definition class for every property, checked as soon as `make()` runs.
- **Reactive definitions and a live engine** that fills defaults, discovers
  dependencies, runs hooks and reports which components changed.
- **Schema patching**, so one module can extend another module's schema.
- **Trees, validation, normalization and inspection** for renderers, tooling and
  transport.
- **Custom components and definitions** without a central registry.

## Requirements

- PHP 8.3 or later
- Laravel 13 (Illuminate Container, Contracts and Support `^13.0`)
- `gcego/core` `^1.2`

## Installation

```bash
composer require gcego/components
```

Laravel discovers `Gcego\Components\ComponentServiceProvider` and the
`Components` facade alias automatically. The provider is required: component
construction, schemas and services all resolve through it. If package discovery
is disabled, register the provider in `bootstrap/providers.php`.

Inside the GCEGO monorepo, use path repositories. A path repository has no Git
tag to read, so declare each package's version explicitly: released packages at
their latest tag, unreleased ones at `0.1.0`. Otherwise Composer sees
`gcego/core` as an unversioned development copy that does not satisfy `^1.2`.

```json
{
    "repositories": [
        { "type": "path", "url": "packages/core", "options": { "versions": { "gcego/core": "1.2.0" } } },
        { "type": "path", "url": "packages/components", "options": { "versions": { "gcego/components": "0.1.0" } } }
    ],
    "require": {
        "gcego/components": "^0.1"
    }
}
```

See [Laravel integration](docs/laravel.md) for container bindings, scopes,
overrides and test setup.

## Quick start

Import a component and configure it with typed definitions. `make()` returns the
immutable, validated component, never a builder:

```php
use Gcego\Components\Components\Actions\Button;
use Gcego\Components\Components\Display\{Heading, Text};
use Gcego\Components\Components\Inputs\TextInput;
use Gcego\Components\Components\Layout\Card;
use Gcego\Components\Definitions\{Children, Content, Icon, Label, Name, Placeholder, Required, Title, Variant};

$save = Button::make(Label::make('Save'), Variant::make('primary'), Icon::make('save'));

$card = Card::make(
    Title::make('Account Settings'),
    Children::make([
        Heading::make(Content::make('Student Information')),
        Text::make(Content::make('Manage your account information.')),
        TextInput::make(
            Name::make('email'),
            Label::make('Email Address'),
            Placeholder::make('Enter email address'),
            Required::make(),
        ),
        $save,
    ]),
);
```

There are no fluent setters, configuration arrays or positional shortcuts:
`Heading::make(Content::make('Title'))` declares text the same way
`Button::make(Label::make('Save'))` declares a label. Children keep their
declaration order, and because components are immutable the same `$save` can
appear in any number of parents. `withDefinitions()` returns a changed copy
under the same checks:

```php
$saveDraft = $save->withDefinitions(Label::make('Save draft'), Variant::make('secondary'));
```

Mistakes fail immediately, with messages that name the component, the
definition and the expectation:

```php
Card::make(Required::make());               // InvalidDefinitionException: …\Card does not accept …\Required (property "required"). …
TextInput::make(Label::make('Email'));      // InvalidDefinitionException: …\TextInput requires "name".
SplitButton::make(Label::make('Save'));     // InvalidDefinitionException: …\SplitButton needs at least 1 child component(s), 0 given.
Button::make('Save');                       // TypeError: only definitions are accepted
```

Read the [usage guide](docs/usage.md) for an end-to-end walkthrough.

## Definitions

Every definition lives in `Gcego\Components\Definitions`, validates its value
when it is made, and sets one component property (`Children` sets the
children, `Configurations` the configuration nodes). Examples:

| Kind                                    | Definitions                                                                             |
| --------------------------------------- | --------------------------------------------------------------------------------------- |
| Identity                                | `Name`, `Id`, `Field`, `StatePath`                                                      |
| Plain text                              | `Label`, `Title`, `Description`, `Content`, `Placeholder`, `HelpText`, `Message`, …     |
| Flags (`make()` alone means `true`)     | `Disabled`, `Required`, `ReadOnlyField`, `Hidden`, `Loading`, `Multiple`, …             |
| Tokens (strings or string-backed enums) | `Variant`, `Size`, `Icon`, `Color`, `Alignment`, `Orientation`, …                       |
| Numbers                                 | `MaxLength`, `Rows`, `Min`, `Max`, `Step`, `PageSize`, …                                |
| Layout                                  | `Columns`, `ColumnSpan` (`ColumnSpan::full()`), `ColumnStart`, `Gap`, `Width`, `Height` |
| URLs (unsafe schemes refused)           | `Href` (property `url`), `Src`, `Poster`                                                |
| Data                                    | `Value`, `DefaultValue`, `Options`, `Data`, `Events`, `Attributes`                      |
| Presentation                            | `HiddenOn`, `VisibleOn`                                                                 |

Defaults come from the schema, the class's `presets()` and the application's
`configureUsing()`, in that order; explicit definitions always win:

```php
// In a service provider's boot(): every Button defaults to the secondary variant.
Button::configureUsing(fn (): array => [Variant::make('secondary')]);
```

[Definitions](docs/definitions.md) lists every definition with its property key
and accepted values, and documents every check `make()` performs.

## Component catalog

| Namespace `Gcego\Components\Components\…` | Components                                                                                                                                                                                                                                                         |
| ----------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `Actions`                                 | Button, IconButton, SplitButton, ButtonGroup, Dropdown, DropdownItem                                                                                                                                                                                               |
| `Inputs`                                  | TextInput, Textarea, Select, MultiSelect, Checkbox, Radio, Toggle, NumberInput, RangeInput, DatePicker, DateTimePicker, TimePicker, FileUpload, HiddenInput, EmailInput, PasswordInput, SearchInput, TelInput, UrlInput, ColorPicker, RichEditor, CodeEditor, Form |
| `Display`                                 | Text, Heading, Badge, Avatar, Icon, Progress, Stat, Separator, CodeBlock, DescriptionList, KeyValueList, ListView, ListItem, Table, TableColumn, Timeline, TimelineItem                                                                                            |
| `Layout`                                  | Card, Container, Stack, Grid, Row, Column, Panel, Spacer, Fieldset, Accordion, AccordionItem, Tabs, Tab, Splitter, Wizard, WizardStep                                                                                                                              |
| `Navigation`                              | Breadcrumbs, Breadcrumb, Menu, MenuItem, NavigationItem, Link, Pagination                                                                                                                                                                                          |
| `Feedback`                                | Alert, Banner, Toast, EmptyState, Skeleton, Spinner                                                                                                                                                                                                                |
| `Overlays`                                | Modal, Drawer, Popover, Tooltip                                                                                                                                                                                                                                    |
| `Media`                                   | Image, Video, Audio, Embed, Map, Chart, Calendar                                                                                                                                                                                                                   |

The page-schema containers `Gcego\Components\Layout\{Schema, Section, Group, Item}`
follow the same contract:

```php
use Gcego\Components\Layout\{Schema, Section};
use Gcego\Components\Definitions\{Children, Columns, Name};

$schema = Schema::make(Columns::make(2), Children::make([
    Section::make(Name::make('profile'), Children::make([/* ... */])),
]));
```

Inputs are UI primitives only: validation of submitted data, submission and
persistence belong to `gcego/forms`; notification delivery belongs to
`gcego/notifications`. The [component catalog](docs/component-catalog.md)
documents every component's type, properties and children rules, and
[composition](docs/composition.md) covers layouts, columns, paths and trees.

## Reactivity and the live engine

Reactive definitions wrap trusted callbacks in the `Evaluation\Computed` runtime
value. Callbacks receive `$get`, `$set`, `$state`, `$old`, `$operation`, context
values and typed injection. They never run during `make()`, validation or
normalization, and never appear in normalized output:

```php
use Gcego\Components\Definitions\{AfterStateUpdated, DefaultValue, Label, Name, RequiredWhen, VisibleWhen};
use Gcego\Components\Evaluation\{Get, Set};

TextInput::make(
    Name::make('company'),
    Label::using(fn (Get $get): string => $get('type') === 'business' ? 'Company' : 'Employer'),
    VisibleWhen::make(fn (Get $get): bool => $get('type') === 'business'),
    RequiredWhen::make(fn (string $operation): bool => $operation === 'create'),
    DefaultValue::using(fn (?Customer $record): ?string => $record?->company),
    AfterStateUpdated::make(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
);
```

`Components::live()` brings a schema to life over state:

```php
use Gcego\Components\Facades\Components;

$live = Components::live($schema, $record->toArray(), ['operation' => 'edit', 'record' => $record]);

$live->isLive('type');               // true: a condition reads $get('type'); no Live::make() needed
$changes = $live->set('type', 'business');
$changes->components;                // ['#0.account.type', '#0.account.company']: redraw only these
$changes->statePaths;                // state paths written, including writes made by hooks
$live->dehydrate();                  // visible, dehydrated fields only
$live->toArray();                    // JSON-safe snapshot of visible fields' state and the resolved tree
```

The engine fills missing defaults, nests state under `StatePath` containers,
records which state paths each callback reads, runs `AfterStateUpdated` hooks and
diffs every update. Updates are incremental: `set()` re-resolves only the
components whose callbacks read a written path and the fields at written
paths. Hidden components' children are never evaluated or dehydrated, and
`toArray()['state']` holds only the values of visible fields, so hidden values
and extra record attributes never reach the client; `state()` keeps
everything for the server. See [reactivity](docs/reactivity.md).

## Extending other modules' schemas

Patches edit built schemas immutably:

```php
// In the loyalty module's service provider:
Components::extend('customers.form', fn (SchemaPatch $patch) => $patch
    ->insertAfter('account.email', TextInput::make(Name::make('loyalty_number'), Label::make('Loyalty number')))
    ->modify('email', Required::make(), HelpText::make('Used for loyalty statements'))
    ->remove('legacy_code'));

// Where the customers module publishes its form:
return Components::extended('customers.form', $schema);
```

Selectors are name chains matched against the end of tree paths (`email`,
`account.email`, `email#1`). A selector that matches nothing fails with a
`Did you mean "account.email"?` hint instead of silently doing nothing, and a
patch that leaves a container with children it refuses, or a component that
`make()` would refuse, fails with the component's path. See
[patching](docs/patching.md).

## Trees, validation and normalization

```php
$schema->assertValid();                        // whole-tree checks, with paths and hints

$errors = Components::validate($schema);       // collected ValidationResult
$tree = Components::tree($schema);             // indexed lookup: findById(), paths, parents
$table = $tree->findById('users-table');
$array = Components::normalize($schema);       // canonical, renderer-neutral array
$inspection = Components::inspect($schema);    // class, type, path and property contract for tooling
```

`make()` checks each component's definitions and the finished component:
required properties, child counts, schema constraints and duplicate ids in its
subtree. `validate()` and `assertValid()` apply the schema rules to whole
trees, including trees no `make()` checked (engine components, builder values).
Traversal detects cycles and limits nesting depth. See
[validation](docs/validation.md), [normalization](docs/normalization.md) and
[composition](docs/composition.md).

## Custom components and definitions

Extend `Foundation\SchemaComponent` and describe the schema. The class gets the
declarative `make()`, `withDefinitions()`, `presets()` and `configureUsing()`;
no central registry or switch statement changes:

```php
use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\{ComponentDefinition, PropertyDefinition, Type};

final class ColorSwatch extends SchemaComponent
{
    public const string Hex = 'hex';

    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()
            ->withType('acme.colorSwatch')
            ->withProperties(new PropertyDefinition(
                self::Hex,
                Type::string(),
                required: true,
                rule: static fn (mixed $value): bool => is_string($value) && preg_match('/\A#[0-9a-f]{6}\z/', $value) === 1,
                ruleDescription: 'a lowercase #rrggbb color',
            ))
            ->withoutChildren();
    }
}
```

A custom definition extends `Definitions\PropertyValue` (or `TextValue`,
`FlagValue`, `TokenValue`) and names the property it sets:

```php
use Gcego\Components\Definitions\PropertyValue;
use Gcego\Components\Exceptions\InvalidDefinitionException;

final readonly class Hex extends PropertyValue
{
    public static function make(string $color): self
    {
        if (preg_match('/\A#[0-9A-Fa-f]{6}\z/', $color) !== 1) {
            throw InvalidDefinitionException::invalidValue(self::class, 'a #rrggbb color', $color);
        }

        return new self(strtolower($color));
    }

    public static function key(): string
    {
        return ColorSwatch::Hex;
    }
}

ColorSwatch::make(Hex::make('#0EA5E9'));
```

Whatever a custom definition produces is still checked against the receiving
component's schema: `Button::make(Hex::make('#0ea5e9'))` fails because Button
declares no `hex` property. Register the class with
`ComponentCatalog::register('colorSwatch', ColorSwatch::class)` so tooling and
renderer adapters can enumerate it. See the [extension guide](docs/extension-guide.md).

## Rendering integration

Components never render. `gcego/rendering` maps a component to a renderer by PHP
class (exact class, then registered parents, then interfaces), so any catalog or
custom component can be rendered by registering a renderer for it:

```php
app(RendererRegistry::class)->register(Button::class, ButtonRenderer::class);

app(RenderEngine::class)->render(Button::make(Label::make('Save')))->toHtml();
```

Renderers read values through `ComponentInterface` (`type()`, `name()`, `id()`,
`get()`, `children()`). `normalize()` produces a JSON-safe array (`type`,
`class`, `id`, `path`, sorted `properties`, `configurations`, `children`) for
other clients.

## Security

- Text definitions hold plain text, never trusted HTML; renderers must escape them.
- `Href`, `Src`, `Poster` and `Action` accept relative URLs and `http`, `https`,
  `mailto` and `tel` (`Action` also takes a plain action name).
  `javascript:`, `data:`, `vbscript:` and protocol-relative URLs (`//host`) are
  refused, including obfuscated forms.
- `Attributes` refuses event handlers, framework directives (`@*`, `:*`, `x-*`,
  `wire:*`, `hx-*`, `v-*`), `style`, `srcdoc`, unsafe URLs in URL attributes and
  `srcset`, and any value starting with an executable scheme.
- Definitions refuse bare closures and runtime values; `name` and `id` cannot be
  computed. Reactive callbacks run only when the live engine or
  `evaluateProperty()` evaluates them, and `::using()` results pass the same
  checks as `make()`.

The URL and attribute rules are also part of the catalog schemas, and the
engine copy methods of declarative components (`withProperty()`,
`withChildren()`, …) run the same checks as `make()`, so `validate()` and every
copy refuse unsafe values however they arrive. Renderers still escape every
value. See the [security model](docs/security.md) for the full boundary.

## Package boundaries

| Rule                                                                          | Enforced by                                           |
| ----------------------------------------------------------------------------- | ----------------------------------------------------- |
| `gcego/core` is the only required GCEGO package                               | `tests/Integration/CrossPackageCompatibilityTest.php` |
| No use of other GCEGO packages (`Rendering`, `Runtime`, `Forms`, `Tables`, …) | `tests/Architecture/BoundariesTest.php`               |
| No Livewire, views, Blade, HTML, HTTP or database code                        | `tests/Architecture/BoundariesTest.php`               |
| One declaration API; every catalog property has a definition                  | `tests/Architecture/DeclarativeApiTest.php`           |

GCEGO framework packages (forms, tables, infolists, panels) build their own
component families on the engine layer: the base `Component`,
`Properties\Property`, `Building\ComponentBuilder` and `Foundation\Makeable`.
That layer is for package authors and is documented in the
[extension guide](docs/extension-guide.md); catalog components refuse its
constructor, builder and macro entry points.

## Examples

[examples/full-usage.php](examples/full-usage.php) declares a complete
dashboard schema. Run the examples from the package directory:

```bash
php examples/run.php                       # load, validate, inspect and query the dashboard
php examples/run.php --normalized          # print its normalized array as JSON
php examples/run.php --custom              # custom component and definitions
php examples/run.php --custom --normalized
```

See [examples/README.md](examples/README.md).

## Upgrading from the pre-release API

The catalog was unreleased and had no consumers outside this package, so the
old APIs were removed rather than deprecated:

| Before                                                                           | Now                                                                                            |
| -------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| `Gcego\Components\Actions\Button`                                                | `Gcego\Components\Components\Actions\Button`                                                   |
| `Gcego\Components\Forms\*`, `DataDisplay\*`                                      | `Gcego\Components\Components\Inputs\*`, `Display\*`                                            |
| `Gcego\Components\Layout\{Card, Grid, Stack, …}`                                 | `Gcego\Components\Components\Layout\*` (`Schema`, `Section`, `Group`, `Item` stay in `Layout`) |
| `Layout\{Modal, Drawer}`, `Feedback\{Popover, Tooltip}`                          | `Gcego\Components\Components\Overlays\*`                                                       |
| `Button::make('save')->label('Save')`, `Button::define(...)->build()`            | `Button::make(Name::make('save'), Label::make('Save'))`                                        |
| `Component::textInput('email')`, `Components::component(...)`                    | `TextInput::make(Name::make('email'))`                                                         |
| `Components::schema([...])`, `Schema::make()->components([...])`                 | `Schema::make(Children::make([...]))`                                                          |
| `new Button(new Property('label', 'Save'))`, `Button::make(['label' => 'Save'])` | `Button::make(Label::make('Save'))`                                                            |
| `->visible(fn ...)`, `->label(fn ...)`, `->default(fn ...)`                      | `VisibleWhen::make(fn ...)`, `Label::using(fn ...)`, `DefaultValue::using(fn ...)`             |
| `->columnSpanFull()`, `->hiddenOn('view')`, `->extraAttributes([...])`           | `ColumnSpan::full()`, `HiddenOn::make('view')`, `Attributes::make([...])`                      |
| `setUp(ComponentBuilder $builder)`                                               | `presets(): list<Definition>`                                                                  |
| `configureUsing(fn (ComponentBuilder $b) => ...)`                                | `configureUsing(fn (): array => [Definition, ...])`                                            |
| `Component::macro(...)` on catalog classes                                       | a custom `Definition` class                                                                    |
| `->modify('email', fn (ComponentBuilder $b) => $b->required())`                  | `->modify('email', Required::make())`                                                          |
| `Heading::make('Title')` set the name                                            | `Heading::make(Content::make('Title'))`; use `Name::make()` for a name                         |
| Type strings `dataDisplay.*`, `forms.*`                                          | `display.*`, `inputs.*`, `overlays.*`                                                          |
| Every catalog component required a name                                          | Only inputs require a `Name`                                                                   |

## Documentation

| Guide                                          | Covers                                                                |
| ---------------------------------------------- | --------------------------------------------------------------------- |
| [Usage](docs/usage.md)                         | End-to-end walkthrough and application integration                    |
| [Definitions](docs/definitions.md)             | Every typed definition, `make()` checks, defaults, custom definitions |
| [Component catalog](docs/component-catalog.md) | Every component's type, properties and children; catalog registration |
| [Components](docs/components.md)               | Reading, copying and identifying components                           |
| [Composition](docs/composition.md)             | Layout schemas, children, columns, paths, trees, traversal            |
| [Reactivity](docs/reactivity.md)               | Computed values, evaluation, state, the live engine                   |
| [Patching](docs/patching.md)                   | `SchemaPatch`, selectors, `extend()` and `extended()`                 |
| [Properties](docs/properties.md)               | Property values, `PropertyDefinition`, types, schema fields           |
| [Validation](docs/validation.md)               | Definition errors, collected and strict tree validation, error codes  |
| [Normalization](docs/normalization.md)         | The canonical output format and inspection                            |
| [Security model](docs/security.md)             | Input boundaries, URL and attribute rules, limits                     |
| [Laravel integration](docs/laravel.md)         | Provider, facade, bindings, scopes, testing                           |
| [Extension guide](docs/extension-guide.md)     | Custom components and the framework engine                            |
| [Architecture](docs/architecture.md)           | Design decisions, lifecycle, class map                                |
| [API reference](docs/api-reference.md)         | Every public class and method                                         |

## Development

```bash
composer check          # Composer validation, strict autoload, Pint, PHPStan and tests
composer test           # Pest test suite
composer analyse        # PHPStan, level max
composer format         # Laravel Pint
composer benchmark      # declaration, construction, traversal, validation, normalization and live timings
composer test-coverage  # requires Xdebug or PCOV
```

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
