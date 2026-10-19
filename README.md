# gcego/components

The Laravel component foundation of **GCEGO Admin Studio**: a declarative,
strongly validated, immutable PHP component model.

Framework authors define a component once: its properties, defaults, allowed
children and shorthand. Application developers then declare only what they need:

```php
use Gcego\Components\Component;
use Gcego\Components\Facades\Components;
return Components::schema([
    Component::section('dashboard')
        ->label('Dashboard')
        ->components([
            Component::group('metrics')
                ->child([
                    Component::item('statistics'),
                    Component::item('activity'),
                ]),
        ]),
]);
```

| Layer | Method | Purpose |
| --- | --- | --- |
| Root | `Components::schema([...])` | Wraps the layout in an immutable schema component. |
| Section | `Component::section('dashboard')` | Declares a major screen area. |
| Group | `Component::group('metrics')` | Groups related components. |
| Nesting | `->components([...])` / `->child([...])` | Appends children in declaration order. |
| Item | `Component::item('statistics')` | Declares a leaf component; children fail validation. |
| Collection | `Component::collect($components)` | Builds an iterable of components or builders into an ordered collection; null entries are skipped. |

`schema()` builds the nested builders into components. Call `->assertValid()` to
check the entire layout, or `->normalize()` for its canonical array. Sections,
groups and items require non-empty names. Builders remain immutable, and nesting
methods append children. Collections can be passed to either nesting method.

`ComponentServiceProvider` is the required initialization and discovery point.
It registers the manager, factories, validation, normalization, traversal and
inspection services in Laravel's container. Component convenience methods use
those same services. Rendering and styling belong to the packages built on top.

## Requirements

- PHP 8.3 or later
- Laravel 12 or 13 with `ComponentServiceProvider` registered
- Illuminate Container, Contracts and Support (installed by Composer)

## Installation

```bash
composer require gcego/components
```

Inside the GCEGO monorepo, add a path repository to the consuming `composer.json`:

```json
{
    "repositories": [
        { "type": "path", "url": "packages/components" }
    ],
    "require": {
        "gcego/components": "*"
    }
}
```

## Laravel integration

Laravel discovers `Gcego\Components\ComponentServiceProvider` automatically.
The provider is mandatory. It registers a scoped manager, shared stateless
services and transient builders and trees, preserving application overrides.
Use `Gcego\Components\Facades\Components` for component creation and operations.
Laravel also discovers the `Components` facade alias. See
[Laravel integration](docs/laravel.md) for registration, facade usage, namespace
migration, customization and test setup.

## A concrete component in 20 lines

```php
use Gcego\Components\Component;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;

final class Text extends Component
{
    public const string Searchable = 'searchable';

    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()
            ->withType('tables.columns.text')
            ->withProperties(
                new PropertyDefinition(self::Name, Type::string(), required: true),
                new PropertyDefinition(self::Searchable, Type::bool(), default: false),
            )
            ->withoutChildren();
    }

    protected static function shorthand(): array
    {
        return [self::Name, self::Label];
    }
}
```

Both declarations below produce the same normalized definition:

```php
use Gcego\Components\Properties\Property;

new Text('name', 'Subject Name');

new Text(
    new Property(Text::Name, 'name'),
    new Property(Text::Label, 'Subject Name'),
);
```

Misspelled or mistyped configuration is reported precisely:

```php
(new Text('name', new Property('serchable', true)))->assertValid();
```

```text
Component validation failed with 1 error(s).

Unknown property "serchable" on component "tables.columns.text".

Expected: one of name, label, description, id, searchable
Received: "serchable"

Did you mean "searchable"?

Path: name
```

## Documentation

| Topic | |
| --- | --- |
| [Architecture](docs/architecture.md) | Design decisions, lifecycle, class map |
| [Components](docs/components.md) | Declaring, reading, copying, identity |
| [Properties](docs/properties.md) | Property values, types, definitions |
| [Composition](docs/composition.md) | Children, configuration nodes, trees, traversal |
| [Validation](docs/validation.md) | Collected and strict validation, error codes |
| [Normalization](docs/normalization.md) | The canonical output format |
| [Extension guide](docs/extension-guide.md) | Building packages on top of this one |
| [Security model](docs/security.md) | Input boundaries, limits and release checks |
| [Laravel integration](docs/laravel.md) | Discovery, container services and overrides |
| [API reference](docs/api-reference.md) | Every public class and method |

## Development

```bash
composer check          # Composer validation, formatting, static analysis and tests
composer test           # Pest test suite
composer analyse        # PHPStan, level max
composer format         # Laravel Pint
composer benchmark      # construction / lookup / traversal / validation / normalization timings
composer test-coverage  # requires Xdebug or PCOV
```

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
