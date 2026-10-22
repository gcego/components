# Architecture

`gcego/components` is the lowest layer of GCEGO Admin Studio. It describes *what*
an interface consists of and how its state reacts, as immutable, validated data.
It never renders, stores, submits or authorizes anything.

## Design principles

1. **One declaration API.** Catalog and custom components are declared only as
   `Component::make(Definition::make(...))`: typed, immutable definitions checked
   against the component's schema. Constructors, builders, macros, arrays and
   positional values are closed to declarative components.
2. **Complexity lives in the framework.** Schema, defaults, presets and
   validation are declared once per component class. Application code declares
   values only.
3. **Immutable values.** Definitions, schemas, collections and components are
   immutable; components change only through copy methods. The only mutable
   object is the live engine's `Evaluation\State`, owned by one `LiveSchema`.
4. **Explicit contracts.** Items are classified by interface (`Property`,
   `ComponentInterface`, `ConfigurationNode`, `Definition`), not by namespace or
   naming conventions. Constructors are never invoked during validation.
5. **Trusted callbacks only.** Closures enter a component only wrapped in
   `Evaluation\Computed` by a definition (`VisibleWhen`, `Label::using()`,
   `AfterStateUpdated`). They are never serialized and run only when evaluated.
6. **Provider-owned initialization.** `ComponentServiceProvider` registers every
   runtime service. The `Components` facade resolves the registered manager;
   Illuminate is a required runtime dependency.
7. **Lightweight boundary.** `gcego/core` is the only GCEGO package Components
   requires. Higher-level packages depend on Components, never the reverse.

## Class map

```text
Gcego\Components
├── Component                  engine base class: properties, children, configuration, evaluation
├── ComponentManager           scoped orchestration behind the facade
├── ComponentServiceProvider   required discovery and service registration
├── Facades\                   Components facade
├── Foundation\                SchemaComponent (catalog and custom base), DeclarativeFactory
│                              (make(), withDefinitions(), presets()), Makeable (engine builders)
├── Definitions\               95 typed, immutable definitions and their bases
├── Components\                the catalog: Actions, Display, Feedback, Inputs, Layout, Media,
│                              Navigation, Overlays (85 concrete classes, 15 family bases)
├── Layout\                    Schema, Section, Group, Item page-schema containers
├── Catalog\                   ComponentCatalog: scoped alias-to-class registry
├── Concerns\                  reusable property sets: CanBeHidden, CanSpanColumns, HasColumns,
│                              HasExtraAttributes, HasState, HasStatePath
├── Configuration\             ComponentConfigurator: configureUsing() defaults, engine macros,
│                              schema extensions
├── Evaluation\                Computed, EvaluationContext, Evaluator, State, Get, Set, StatePath
├── Live\                      LiveSchema, ResolvedComponent, Changes: state, dependencies,
│                              incremental updates, diffs
├── Patching\                  SchemaPatch: selector-based immutable schema edits
├── Schema\                    ComponentDefinition, PropertyDefinition, Field, Type, TypeKind
├── Properties\                immutable Property value
├── Collections\               PropertyCollection, ComponentCollection, ConfigurationCollection
├── Building\                  ComponentBuilder (engine builders for framework families)
├── Trees\                     ComponentTree, ComponentPath, TreeNode
├── Traversal\                 ComponentWalker, TraversalOrder, Visit, AncestorChain
├── Validation\                definition, property and child validators, results, error codes
├── Normalization\             ComponentNormalizer, RuntimePlaceholder
├── Inspection\                ComponentInspector, ComponentInspection
├── Contracts\                 component, definition, concern, configuration and value contracts
├── Support\                   argument parsing, identity, URL and attribute safety, dimensions,
│                              responsive values, subtree ids, diagnostics
└── Exceptions\                ComponentException and package exceptions
```

Every public class and signature is listed in the [API reference](api-reference.md).

## Responsibility ownership

| Responsibility | Owner | Related objects with distinct roles |
| --- | --- | --- |
| Registration and service lifetimes | `ComponentServiceProvider` | The facade exposes the manager; neither registers competing services. |
| Declarative construction | `Foundation\DeclarativeFactory` | `Validation\DefinitionValidator` checks each definition against the schema (`check()`) and the finished component (`assertComplete()`); the guarded constructor runs only inside `make()`. |
| Typed configuration values | `Definitions\*` | Each definition validates its value and expands into properties, children or configuration nodes. |
| Reusable property sets | `Concerns\*` | Merged into a schema with `ComponentDefinition::withConcerns()`. |
| Application defaults | `Configuration\ComponentConfigurator` | `configureUsing()` modifiers per class, engine macros, schema extensions. |
| Scoped schema cache and services | `ComponentManager` | `ComponentCatalog` maps aliases to classes. |
| Closure evaluation | `Evaluation\Evaluator` | `EvaluationContext` carries parameters, typed values and state; `Get`/`Set` resolve relative state paths. |
| Reactive state | `Live\LiveSchema` | `State::record()` tracks reads, from which dependencies and `Changes` are derived. |
| Schema extension | `Patching\SchemaPatch` | `Components::extend()` / `extended()` register and apply named patches. |
| Engine composition | `Building\ComponentBuilder` | Framework packages' builders; declarative components expose none. |
| Engine constructor parsing | `Support\ConstructorArguments` | Classifies items for engine classes and for `make()`'s internal construction. |
| Property value safety | `Properties\Property` | `ValueSnapshot` detaches array references after validation. |
| Property schema | `Schema\PropertyDefinition` | `Schema\Field` builds it; `Type` describes accepted types. |
| Runtime form control | `Components\Inputs\FieldComponent` | A state-holding component (`HoldsState`), distinct from the `Schema\Field` property builder. |
| Traversal and occurrence paths | `Traversal\ComponentWalker` | `Trees\ComponentTree` adds a lazy index and queries, not a second traversal engine. |
| Schema validation | `Validation\ComponentValidator` | Delegates to `PropertyValidator` and `ChildValidator`. |
| Exported values | `Normalization\ComponentNormalizer` | `ComponentInspector` reuses normalization and validation for diagnostic snapshots. |

Collections intentionally keep separate contracts: properties are keyed and
reject duplicate keys; children are ordered occurrences; configuration
collections select non-renderable nodes by class.

Shared catalog schemas live in abstract family bases (`LayoutBlock`,
`TitledBlock`, `Overlay`, `FieldComponent`, `TextField`, `NumericField`,
`ChoiceField`, `TemporalField`, `Message`, `DismissibleMessage`,
`NavigationItem`, `Action`, `TextualComponent`, `ImageComponent`,
`PlayableMedia`). Concrete classes declare a distinct renderer type and only
their additional properties.

## Lifecycle

```text
X::definition()        defineSchema() once per class, cached in the active scope
X::make(...)           definition checks → presets → configureUsing() → construction → completeness check
$x->withDefinitions()  the same checks, applied to an immutable copy (also run by declarative with*() copies)
$x->validate()         schema checks over the whole tree, every error with its path
$x->assertValid()      the same checks, throwing InvalidComponentException
$x->evaluateProperty() resolves a Computed value for one context
Components::live()     state, dependencies, visibility and diffs over a validated tree
$x->normalize()        canonical array for registries and renderers
```

### Declarative construction

`X::make(Definition ...$definitions)`:

1. **Definition validation.** `DefinitionValidator::expandDefinitions()` expands
   each explicit definition and refuses non-definitions, properties the schema
   does not declare (listing the accepted ones with a "Did you mean" hint),
   values whose type, allowed values or rule fail, callback properties without a
   `RuntimeValue`, children and configuration nodes the schema does not accept,
   and repeated property keys. `presets()` are expanded the same way.
2. **Presets.** `presets()` definitions form the base.
3. **`configureUsing()`.** When the configurator has modifiers for the class or a
   parent class, each callback's definitions are applied over the presets
   (important callbacks last), through an internal builder whose items are
   checked against the same schema. Without modifiers, no builder is created.
4. **Explicit definitions** override: properties by key, while explicit
   `Children` or `Configurations` replace the preset ones of the same kind.
5. **Construction** inside a guarded scope. The constructor is `final` and throws
   outside `make()`.
6. **Completeness check.** `DefinitionValidator::assertComplete()` checks the
   finished component: required properties without a value or schema default
   (`InvalidDefinitionException::missing()`), child counts outside the schema's
   bounds (`tooFewChildren()`, `tooManyChildren()`), failed schema constraints
   (`constraintFailed()`) and an explicit id repeated in the subtree
   (`duplicateId()`). Subtree ids are collected by the internal
   `Support\SubtreeIds`, which remembers each immutable subtree's ids, so a
   parent merges its children's sets instead of walking the tree again.

Schema defaults are not copied into the component; `get()` and
`effectiveProperties()` resolve them on read. `define()` and `macro()` return
`never`, and `Components::define()` / `Components::make()` refuse declarative
classes, so no other construction route exists.

### Why tree validation is separate

`make()` checks one component and its subtree and throws at the first
problem. Tree validation (`validate()` / `assertValid()`) remains a separate
step because:

- only a tree pass can report `admin.dashboard.users.name`-style paths for
  every error;
- developers get every error at once;
- engine components (which skip definition checks and schema rules in their
  `with*()` copies), builder values and trees edited by patches are validated
  by the same pass.

Runtimes and registries call `assertValid()` (or `validate()`) before using a
schema. `Component::validate()` memoizes its result per instance and manager
scope; a weak reference keeps the cache from retaining a previous manager.

### Evaluation and live state

Computed values are resolved on demand, never during construction or
validation. `Component::evaluateProperty()` resolves one property;
`Components::live()` builds a `LiveSchema` that fills missing defaults, records
which state paths each closure reads, runs `afterStateUpdated` hooks and
reports exactly the components a `set()` changes. `set()` re-resolves only the
components that read a written path and the state holders of written paths;
everything else is reused. Hidden containers are not resolved. See
[reactivity](reactivity.md).

## Engine constructor item classification

For engine classes (framework component families that do not implement
`Contracts\DeclarativeComponent`), `ConstructorArguments::parse()` applies these
rules to `new X(...$items)`:

| Item | Becomes |
| --- | --- |
| `Property` | an explicit property |
| `Definition` | its schema-checked properties, children and configuration nodes |
| `ComponentInterface` | a renderable child, in order |
| `ConfigurationNode` | a configuration node, in order |
| any other leading value | a shorthand `Property` for the next key in `X::shorthand()` |

A named argument, a shorthand value after the first item, or more shorthand
values than declared keys throw `InvalidConfigurationException`. A duplicate key
throws `DuplicatePropertyException`.

## Initialization and scope

Laravel discovers `ComponentServiceProvider` through Composer metadata. Its
`ComponentManager` binding is required before component construction, schema
access or runtime operations; no component constructs independent services.

| Service | Lifetime |
| --- | --- |
| `ComponentManager` (schema cache), `ComponentCatalog` (custom aliases) | scoped: reset per request or queued job |
| `ComponentWalker`, validators, `ComponentNormalizer`, `ComponentInspector`, `Evaluator`, `ComponentConfigurator` | application singletons |
| `ComponentBuilder`, `ComponentTree` | transient |
| `LiveSchema`, `SchemaPatch` | created per call, owned by the caller |

`configureUsing()` modifiers, engine macros and schema extensions therefore
belong to the application that registered them and survive scope resets;
catalog registrations do not and are re-applied with an `afterResolving`
callback ([Laravel integration](laravel.md)). Tests clear resolved facade
instances when switching application containers.

## Immutability policy

- `Property`, `PropertyDefinition`, `ComponentDefinition`, `Field`, `Type`, every
  definition, every collection, `TreeNode`, `ValidationError`,
  `ValidationResult`, `ComponentIdentity`, `Computed`, `EvaluationContext`,
  `ResolvedComponent` and `Changes` are `readonly`.
- `ComponentPath` is immutable; its only internal state is a lazy string cache.
- `Component` has no public properties. Its internals change only on clones
  inside the `final` copy methods `withProperty()`, `withoutProperty()`,
  `withChildren()`, `withAppendedChildren()` and `withConfigurations()`, which
  declarative components wrap in `withDefinitions()`. On declarative
  components they run `DefinitionValidator::check()` and `assertComplete()`,
  and `withoutProperty()` refuses required properties. Copies
  keep the concrete subclass. `__clone()` is `final` and resets the memoized
  validation result.
- Shallow cloning is safe because everything a component holds is immutable:
  validated arrays are recursively copied to detach references; enum cases are
  singletons; object values must implement `ValueObject` (immutable) or
  `RuntimeValue` (opaque, never inspected).
- `SchemaPatch::apply()` and `Components::extended()` return new trees; the
  original schema is never changed.

## Identity

| Identity | Source | Scope |
| --- | --- | --- |
| type | `ComponentDefinition::$type`, defaulting to the class name | every instance of a class |
| explicit id | the optional `id` property (`Id::make()`) | stable across requests |
| occurrence | the deterministic `ComponentPath` | one position in one tree |

`TreeNode::identity()` returns a `ComponentIdentity` holding all three. Its
`stableKey()` returns `id:<explicit-id>` when an id is present, otherwise
`path:<occurrence-path>`. Paths are built from component names (or `#index`), so
`LiveSchema`, `SchemaPatch` selectors and `ComponentTree` share one addressing
scheme. No random ids are generated.

## Reuse and cycles

One immutable component may appear at several positions. Each occurrence gets
its own `TreeNode` and path, for example `root.shared` and `root.shared#1`. A
reused instance carrying an explicit id is refused by the `make()` of the
declarative parent that holds both copies, and reported as `DuplicateId` by
`validate()` in other trees.

The base `Component` cannot form cycles, because children exist before their
parent. Third-party `ComponentInterface` implementations could still return
themselves as children, so `ComponentWalker` tracks ancestors and throws
`InvalidChildException`. Traversal is iterative, and nesting deeper than
`ComponentWalker::DefaultMaxDepth` (256) is rejected.

## Performance

- Schemas are built once per class within the active scope.
- Property lookup is a single array access; defaults are resolved on read.
- `make()` constructs directly and uses an internal builder only when
  `configureUsing()` modifiers apply.
- Traversal is lazy and iterative. Paths and ancestor chains share their
  parents, so memory per node is constant.
- `make()` keeps duplicate-id checks linear: each immutable subtree's ids are
  computed once and remembered per instance. Declaring a tree with `make()`
  costs roughly three to four times a raw constructor call (the `declare` and
  `construct` benchmarks).
- `Components::live()` resolves the visible tree once. Each
  `LiveSchema::set()` evaluates again only the affected components (readers of
  a written path and the fields at written paths) and compares their
  fingerprints; their ancestors are copied with the new children and every
  other resolved component is reused. Its cost grows with the number of
  affected components rather than the size of the tree. Hidden containers are
  skipped.

`composer benchmark` builds a 201-component page and times construction, typed
declaration, lookup, traversal, validation, normalization, and live creation and
updates.

## Layout and catalog

`Layout\Section`, `Group` and `Item` extend `SchemaComponent`; `Layout\Schema`
extends `Component` directly and uses `DeclarativeFactory`, so the root carries
`columns` and `statePath` but no visibility or column-span properties. All share
the component contracts, validators, normalizer and traversal services. Sections
and groups accept any component class as children.

`Catalog\ComponentCatalog` maps 88 aliases (the 85 concrete catalog classes plus
`section`, `group` and `item`) and any custom registrations to classes, for
tooling and renderer adapters. It never constructs components. See the
[component catalog](component-catalog.md).

## Quality gates

`composer check` validates `composer.json`, runs strict optimized autoload checks
(PSR-4 violations and ambiguous classes), Pint, PHPStan and the Pest suite.
Architecture tests (`tests/Architecture`) keep the declarative API closed and
the layer boundaries intact. Integration tests execute the files in `examples/`
against the provider, so the documented declarations stay valid.
