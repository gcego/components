# Laravel integration

Components runs inside a Laravel 13 application. `ComponentServiceProvider`
registers every service, and the `Components` facade is the package entry
point. Both are discovered automatically from `composer.json`.

## Registration

Composer's `extra.laravel` metadata lists the provider and the facade alias:

```json
"extra": {
    "laravel": {
        "providers": ["Gcego\\Components\\ComponentServiceProvider"],
        "aliases": { "Components": "Gcego\\Components\\Facades\\Components" }
    }
}
```

If package discovery is disabled, register the provider in
`bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    Gcego\Components\ComponentServiceProvider::class,
];
```

The provider is mandatory. Declaring a component, reading a schema or calling a
package service without it throws `InvalidConfigurationException`:

```text
ComponentServiceProvider must be registered before using gcego/components. Enable Laravel package discovery or register Gcego\Components\ComponentServiceProvider in bootstrap/providers.php and boot Laravel facades.
```

There is no configuration file, migration, route or view to publish.

### GCEGO package

`gcego/core` is the only GCEGO package Components requires; any GCEGO Studio
application can install it without forms, tables or panels, and the test suite
enforces that boundary. The provider also implements
`Gcego\Core\Contracts\PackageInterface`, so gcego/core discovers and boots it as
the `gcego/components` package with `requires: ['gcego/core']` and the
capabilities `components`, `components.schema`, `components.validation`,
`components.normalization` and `components.traversal`. The provider is eager
because gcego/core only discovers loaded providers; registration only declares
bindings and builds no service.

## Container bindings

All services live under `Gcego\Components`:

| Service | Lifetime | Purpose |
| --- | --- | --- |
| `ComponentManager` | Scoped | Facade root: schema definition cache and every operation below |
| `Catalog\ComponentCatalog` | Scoped | Alias-to-class registry of catalog and custom components |
| `Configuration\ComponentConfigurator` | Singleton | `configureUsing()` defaults, engine builder macros and schema extensions |
| `Evaluation\Evaluator` | Singleton | Closure parameter injection for computed values |
| `Traversal\ComponentWalker` | Singleton | Path-aware traversal with cycle and depth checks |
| `Validation\PropertyValidator` | Singleton | Property schema checks |
| `Validation\ChildValidator` | Singleton | Child and configuration rules |
| `Validation\ComponentValidator` | Singleton | Validation of whole trees |
| `Normalization\ComponentNormalizer` | Singleton | Canonical component output |
| `Inspection\ComponentInspector` | Singleton | Inspection snapshots for tooling |
| `Building\ComponentBuilder` | Transient | Engine builders for framework component families |
| `Trees\ComponentTree` | Transient | An independent tree for each root |

Properties, schemas, collections, paths, live schemas, patches and validation
results are data objects; they have no bindings.

## The `Components` facade

`Gcego\Components\Facades\Components` resolves the scoped `ComponentManager`.
Components themselves are declared with their own `make()` and typed
definitions; the facade operates on declared components.

| Method | Purpose |
| --- | --- |
| `validate(ComponentInterface $root): ValidationResult` | Validate a whole tree. See [validation](validation.md). |
| `normalize(ComponentInterface $root): array` | Canonical output of a tree. See [normalization](normalization.md). |
| `normalizeValue(mixed $value, string $location = 'value'): mixed` | Normalize one value; `$location` names it in errors. |
| `inspect(ComponentInterface $root): ComponentInspection` | Inspection snapshot for tooling. |
| `tree(ComponentInterface $root): ComponentTree` | A new tree for the root. See [composition](composition.md). |
| `walker(): ComponentWalker` | The configured walker. |
| `collect(iterable $components): ComponentCollection` | Flatten components, `Buildable`s and nested iterables (up to 32 levels), skipping `null`. |
| `catalog(): ComponentCatalog` | The scoped catalog. See [component catalog](component-catalog.md). |
| `live(ComponentInterface\|Buildable $schema, State\|array $state = [], EvaluationContext\|array $context = []): LiveSchema` | Resolve a schema over state. See [reactivity](reactivity.md). |
| `evaluator(): Evaluator` | The closure evaluator. |
| `patch(ComponentInterface\|Buildable $schema): SchemaPatch` | Start an immutable patch. See [patching](patching.md). |
| `extend(string $schema, Closure $extension): void` | Register a patch for a published schema key. |
| `extended(string $schema, ComponentInterface\|Buildable $component): ComponentInterface` | Publish a schema with its registered extensions applied. |
| `configurator(): ComponentConfigurator` | The configurator behind `configureUsing()`. |
| `define(string $component, array $items = []): ComponentBuilder` | Engine builder for a framework component class. |
| `make(string $component = Component::class, array $items = []): Component` | Build an engine component through `define()`. |

```php
use Gcego\Components\Component;
use Gcego\Components\Definitions\{Children, Name};
use Gcego\Components\Facades\Components;
use Gcego\Components\Layout\{Item, Schema, Section};

$root = Schema::make(Children::make([
    Section::make(Name::make('dashboard'), Children::make([
        Item::make(Name::make('statistics')),
    ])),
]));

Components::validate($root)->throwIfInvalid();
$output = Components::normalize($root);
$tree = Components::tree($root);
$inspection = Components::inspect($root);
```

`define()` and `make()` are for framework packages that build engine component
families (see [components](components.md#engine-construction-package-authors)). Declarative
components refuse them with `InvalidConfigurationException`:
`… Button is declared only through … Button::make() with typed definitions, …; Components::define() is not available.`

Component convenience methods (`validate()`, `assertValid()`, `normalize()`,
`tree()`) delegate to the same services, so they use the same configuration as
the facade and constructor injection.

### Manager lifetime

The facade does not cache its root (`$cached = false`): every call resolves the
manager from the container. `ComponentManager` can be constructor-injected, but
an instance retained past its application or scope is rejected:

```text
ComponentManager belongs to an inactive application or scope. Resolve it again through the Components facade or service container.
```

Managers cannot be serialized (`ComponentManager cannot be serialized. …`), so
never store one in a session, cache or queued job. Their debug output redacts
the container.

## Overriding services

Bind replacements in an application service provider's `register()` method,
before anything resolves the package services:

```php
use Gcego\Components\Normalization\ComponentNormalizer;
use Gcego\Components\Traversal\ComponentWalker;
use Illuminate\Contracts\Container\Container;

public function register(): void
{
    $this->app->singleton(ComponentWalker::class, static fn (): ComponentWalker => new ComponentWalker(maxDepth: 64));
    $this->app->singleton(ComponentNormalizer::class, static fn (Container $app): ComponentNormalizer => new ComponentNormalizer(
        $app->make(ComponentWalker::class), includeSchema: true,
    ));
}
```

The package provider skips any service that already has a binding, shared
instance or alias, so overrides registered first are kept; bindings registered
later replace the package's. The configured walker is used by validation,
normalization, inspection, trees, patches and the live engine, including the
component convenience methods.

## Scopes, Octane and queues

Scoped services are released whenever Laravel forgets scoped instances: at the
start of each Octane request and each queue job. Applications with their own
worker lifecycle must call `$app->forgetScopedInstances()` at the boundary.

| Released at scope reset | Kept for the application's lifetime |
| --- | --- |
| `ComponentManager` and its cached schema definitions | `ComponentConfigurator`: `configureUsing()` defaults, macros and `extend()` patches |
| `ComponentCatalog` and aliases registered on it | The evaluator, walker, validators, normalizer and inspector |

Validation results cached on components hold only a weak reference to their
manager, so a component reused across scopes is validated again by the active
manager rather than retaining an earlier scope.

## Where to register configuration

Register application-wide configuration once, in a service provider:

```php
use App\Components\RatingInput;
use Gcego\Components\Catalog\ComponentCatalog;
use Gcego\Components\Components\Actions\Button;
use Gcego\Components\Components\Inputs\TextInput;
use Gcego\Components\Definitions\{Label, Name, Variant};
use Gcego\Components\Facades\Components;
use Gcego\Components\Patching\SchemaPatch;

public function register(): void
{
    // The catalog is scoped: reapply aliases whenever it is resolved again.
    $this->app->afterResolving(ComponentCatalog::class, static function (ComponentCatalog $catalog): void {
        $catalog->register('ratingInput', RatingInput::class);
    });
}

public function boot(): void
{
    // Kept for the application's lifetime.
    Button::configureUsing(fn (): array => [Variant::make('secondary')]);

    Components::extend('customers.form', fn (SchemaPatch $patch): SchemaPatch => $patch
        ->insertAfter('account.email', TextInput::make(Name::make('loyalty_number'), Label::make('Loyalty number'))));
}
```

- `configureUsing()` and `extend()` belong in `boot()`. They are stored on the
  application singleton configurator, so calling them on every request under
  Octane (from middleware or controllers) would register duplicates.
- Catalog aliases belong in an `afterResolving()` callback. Calling
  `Components::catalog()->register()` directly lasts only for the current
  scope.

See [definitions](definitions.md) for `configureUsing()` and `presets()`, and
[patching](patching.md) for extensions.

## Testing

In a Laravel application, the test application loads the discovered provider,
and each test boots a fresh application, so configurator state (defaults,
extensions) never leaks between tests.

Outside a full application (package tests, scripts, benchmarks), create an
application, point facades at it and register the provider. This package's
`tests/Pest.php` does it for every test:

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
})->in('Unit', 'Integration', 'Architecture');
```

To test scope behavior, call `$app->forgetScopedInstances()` and resolve the
facade again. To test an override, bind it on the container before the first
component is declared. `laravel/framework` is a development dependency so the
suite exercises a real application, including Composer package discovery and
gcego/core package registration.

`examples/run.php` uses the same bootstrap for a standalone script; see the
[usage guide](usage.md).

## Upgrading

The base `Component`, `ComponentManager` and `ComponentServiceProvider` live in
the `Gcego\Components` root namespace. Earlier releases also had these
locations:

| Previous class under `Gcego\Components` | Current location under `Gcego\Components` |
| --- | --- |
| `Services\ComponentManager`, `Providers\ComponentServiceProvider` | `ComponentManager`, `ComponentServiceProvider` |
| `Property` | `Properties\Property` |
| `ComponentCollection`, `PropertyCollection`, `ConfigurationCollection` | `Collections\…` |
| `ComponentDefinition`, `PropertyDefinition`, `Type`, `TypeKind` | `Schema\…` |
| `ComponentTree`, `ComponentPath`, `TreeNode` | `Trees\…` |
| `Layout\NamedComponent`, `Foundation\NamedComponent` | removed; extend `Foundation\SchemaComponent` |
| `Forms\Field` | `Components\Inputs\FieldComponent` |

Replace `ComponentManager::instance()->…` with `Components::…`, or inject the
manager. After updating, regenerate Composer autoloading and Laravel's package
discovery cache (`php artisan package:discover`). The catalog namespaces and the
declaration API changed too; see the [changelog](../CHANGELOG.md).

## Distribution

The Composer archive excludes dependencies, tests, benchmarks, build artifacts,
local environment files and editor directories, and keeps source code,
documentation and license files. Validate a release with
`composer validate --strict`, `composer audit --locked`, `composer check` and
`composer archive`.
