# Changelog

All notable changes to `gcego/components` are documented in this file.
The project follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Add `Support\SafeAttributes` (shared by `Definitions\Attributes` and the `extraAttributes` schema rule), `Support\Dimension` (width and height: a positive pixel count or a length token), `SafeUrl::accepts()`, `SafeUrl::Description`, `SafeUrl::ExecutableSchemes` and `SafeUrl::hasExecutableScheme()`.
- Add `DefinitionValidator::check()`, `assertComplete()` and `IdentityProperties`, the `InvalidDefinitionException` factories `tooFewChildren()`, `tooManyChildren()`, `constraintFailed()`, `duplicateId()` and `requiredRemoved()`, `EvaluationException::refusedResult()`, and the `PatchException` factories `childNotAccepted()`, `childCount()` and `invalidResult()`.
- Add the optional `Computed::$check`, which `TextValue::using()` and `DefaultValue::using()` set so a computed result passes the same checks as `make()`, and `ResolvedComponent::$parentPath`.
- Add the single typed declaration API, `Component::make(Definition::make(...))`, for every catalog and `Layout` component (`Contracts\DeclarativeComponent`, `Foundation\DeclarativeFactory`). `make()` accepts definitions only, checks each against the schema (unsupported properties with a "Did you mean" hint, refused values, repeated and missing required properties, refused children and configuration nodes raise `InvalidDefinitionException`) and returns the immutable component. `withDefinitions()` returns checked copies, `presets()` and `configureUsing(fn (): array => [...])` supply defaults that explicit definitions override, and `SchemaPatch::modify()` takes definitions.
- Add `Contracts\Definition`, `Validation\DefinitionValidator`, `Exceptions\InvalidDefinitionException`, `Support\SafeUrl` and the `Definitions\` namespace, with a typed definition for every catalog property (about 90 classes, from `Label`, `Variant`, `Children` and `Configurations` to `MaxLength`, `Columns`, `ColumnSpan::full()`, `Options`, `Src` and `StatePath`), the reactive `VisibleWhen`, `HiddenWhen`, `DisabledWhen`, `RequiredWhen`, `ReadOnlyWhen`, `AfterStateUpdated`, `TextValue::using()` and `DefaultValue::using()`, and the `PropertyValue`, `TextValue`, `FlagValue`, `TokenValue` and `Condition` bases for custom definitions.
- Add `IconButton`, `SplitButton`, `Separator` and `Spacer` to the catalog, and a `size` property to actions.
- Add architecture tests for package, rendering and dependency boundaries, the shared factory contract, definition coverage of every catalog property and the absence of removed APIs across GCEGO packages, and a `declare` benchmark.
- Add the live engine: `Components::live()` returns a `Live\LiveSchema` that fills defaults, evaluates closures, discovers dependencies by recording state reads (fields become live without `live()` or `dependsOn`), runs `afterStateUpdated` hooks, diffs updates into `Changes`, dehydrates only visible fields and exports a JSON-safe snapshot.
- Add the state concerns `HasState` (`statePath`, `default`, `live`, `dehydrated`, `afterStateUpdated`) and `HasStatePath`, and the `Contracts\HoldsState` marker on form fields.
- Add `Patching\SchemaPatch` with `Components::patch()`, `extend()` and `extended()`, so modules can extend each other's schemas through selector-based, suggestion-checked edits.

- Add `presets()` and `configureUsing()` (with `during:` and `isImportant:`) defaults for declarative components, stored per application so tests stay isolated.
- Evaluate trusted callbacks stored as `Evaluation\Computed` values with `Component::evaluateProperty()` and `evaluatedProperties()`, with typed injection and "Did you mean" hints for misspelled parameters.
- Records (`QueueableEntity`, such as Eloquent models) are only injected from the evaluation context, never built by the container, so a closure asking for a record that is not there fails instead of receiving an empty one.
- Add `Evaluation\Get`, `Set`, `State` and `EvaluationContext`, with Filament-style relative (`../`) and absolute state paths.
- Add `Foundation\SchemaComponent` and the reusable `Concerns` (`CanBeHidden`, `CanSpanColumns`, `HasColumns`, `HasExtraAttributes`) through `ComponentDefinition::withConcerns()`. Add the builder methods `columnSpanFull()`, `hiddenOn()`, `visibleOn()` and `extraAttributes(merge:)`, plus `Component::isHiddenIn()`, `isVisibleIn()` and `displayLabel()`.

- Add complete executable layout and extension examples, a CLI runner, and a usage guide; align documentation with catalog scope, shared bases and builder operations.

- Expand the shared builder with bulk property operations, lazy child transformations, configuration replacement, conditional composition and runtime-service terminal methods.

- Add 88 catalog component types across eight categories, with a scoped `ComponentCatalog` alias registry for custom components.
- Add fluent `Schema\Field` builders and `withFields()` schema composition.

- Support extending catalog classes through `Foundation\SchemaComponent`.
- Add `composer check` as the complete local quality gate.

- Add `Layout\Schema`, `Section`, `Group` and `Item` page-schema containers and dynamic component collections.

### Changed

- `make()`, `withDefinitions()` and the engine copy methods of declarative components check the finished component: required properties, child-count bounds, schema constraints and duplicate ids in its subtree throw `InvalidDefinitionException` instead of surfacing only in `validate()`.
- `withProperty()`, `withChildren()`, `withAppendedChildren()` and `withConfigurations()` on declarative components run the same checks as `make()`, and `withoutProperty()` refuses to remove required properties. Engine components are unchanged.
- `make()` accepts an explicit `null` for a nullable property before the allowed-values and rule checks, as `validate()` does.
- `name` and `id` refuse runtime values, and `Value::make()` and `DefaultValue::make()` refuse runtime values such as `Computed`.
- `SafeUrl::isSafe()` refuses protocol-relative URLs (`//host`, `/\host`, `\\host`); write `https://` explicitly. URL messages read "expects a relative URL or an http, https, mailto or tel URL".
- `Definitions\Action` requires an action name or a safe URL. The catalog schemas carry a `SafeUrl` rule on `url`, `src`, `poster` and `action`, so `validate()` refuses unsafe URLs however they were set.
- `Attributes` and the `extraAttributes` schema also check `srcset` and `imagesrcset` candidates, treat `data` and `ping` as URL attributes and refuse any value that starts with an executable scheme (including `data-*` values); the schema now refuses `@`-prefixed names as the definition does.
- Every component with `width` and `height` (layout blocks, overlays, `Skeleton`, images, `Embed`) accepts both pixel counts and length tokens. `Stat` `value` accepts a string, int or float.
- `SchemaPatch` checks every container whose children change (refused child types, child counts) and wraps declarative copy failures in `PatchException` with the component's path. Targeting the root throws `PatchException::rootOperation()`, and "Did you mean" suggestions skip `#n` paths unless the selector contains `#`.
- `LiveSchema::set()` is incremental: it re-resolves only the components whose closures read a written path and the state holders of written paths (about 0.3 ms instead of 8 ms per update in the 201-component benchmark). `Changes::$components` is in tree order.
- `LiveSchema::toArray()['state']` contains only the values of visible state holders; hidden fields, fields in hidden containers and state no field holds are left out. `state()` still holds everything.
- Move the catalog to `Gcego\Components\Components\{Actions, Inputs, Display, Layout, Navigation, Feedback, Overlays, Media}`. `Forms` became `Inputs`, `DataDisplay` became `Display`, and Modal, Drawer, Popover and Tooltip moved to `Overlays`. `Layout\Schema`, `Section`, `Group` and `Item` stay in `Layout`. Type strings follow (`display.*`, `inputs.*`, `overlays.*`).
- Catalog `make()` returns the component instead of a builder. `Foundation\Makeable` keeps its builder-returning `make()` as engine API for framework packages with their own component families (gcego/forms, gcego/tables, gcego/infolists).
- Only inputs require a name; other catalog components may be anonymous. Text-bearing components take `Content::make(...)`.
- Numeric definitions keep integers as integers.
- Require `gcego/core` as the only GCEGO dependency and register `ComponentServiceProvider` as the `gcego/components` GCEGO package (`PackageInterface`, `requires: ['gcego/core']`, `components.*` capabilities). A test enforces that no other GCEGO package is required or referenced.
- `ComponentServiceProvider` is no longer deferred, because gcego/core only discovers loaded providers; registration still builds no services until they are resolved.
- Require Laravel 13 (Illuminate `^13.0`), matching gcego/core.
- Catalog components now extend `Foundation\SchemaComponent` (still a `NamedComponent`). `Grid::columns` and the other column layouts accept a positive integer or a breakpoint map.

- Consolidate shared catalog schemas into family bases; move the domain-neutral named base to `Foundation\NamedComponent` and distinguish form controls as `Forms\FieldComponent`. Update custom base imports as described in the architecture guide.
- Use one builder item-classification pipeline and route named layout factories through the catalog.
- Check optimized PSR-4 autoloading and ambiguous classes in `composer check`; restrict dev autoloading to namespaced fixtures.

- Keep `ComponentManager` and `ComponentServiceProvider` at the package root; group collections, schemas, properties and trees in responsibility folders. Update consumer imports as described in the namespace migration guide.
- Replace the manual `ComponentManager::instance()` resolver with the Laravel `Components` facade.

- Illuminate Container, Contracts and Support are required runtime dependencies.
- Component construction, schema access and runtime services require provider registration. Missing registration raises an actionable package exception.
- Component convenience methods resolve provider services. Service constructors require injected dependencies instead of constructing default services.

### Removed

- Remove every competing way to declare catalog and `Layout` components before release: `define()` and `macro()` now throw (`never`), the constructor only runs inside `make()`, `Components::define()` and `Components::make()` refuse them, positional shorthand, `Property` wrappers and configuration arrays are rejected by `make()`'s typed signature, and the `Component::textInput()`-style alias factories, `Component::section()`, `group()` and `item()`, `Components::component()` and `Components::schema()` are gone. `Foundation\NamedComponent` and the builder-based `setUp()` hook for catalog classes are replaced by `SchemaComponent` and `presets()`.

### Fixed

- `Label::using(fn () => '  ')` and other computed text no longer evaluate to values the definition's `make()` refuses.
- Engine copy methods no longer let declarative components hold values `make()` refuses, such as a `javascript:` URL set with `withProperty()`.
- Resolve tree-node relationship queries against the owning tree and publish lazy indexes only after successful traversal.

- Isolate schema definitions and validation caches by Laravel request/job scope; release captured request context and reject inactive managers.
- Keep runtime property values opaque even when property normalizers are configured, and normalize defaults in direct schema array exports.
- Remove anonymous-class source paths from exported class metadata, type descriptions and validation diagnostics.
- Redact manager container contents in debug output and reject manager serialization.

- Reject invalid, abstract or mismatched component builders before returning them, and verify the resulting component class.
- Bound recursive builder item nesting and stream iterable items without losing repeated keys.
- Resolve facade managers from the active application container rather than retaining a second application-level cache.
- Exclude local environment and developer artifacts from release archives while retaining documentation.

- Detach PHP references from property arrays, schema defaults, type lists, constraints and allowed values.
- Namespace stable state keys as `id:…` and `path:…` to prevent collisions. Consumers persisting the old keys must migrate or clear that state.
- Normalize object defaults in included schemas and always treat runtime values as opaque, including objects implementing both value contracts.
- Reject trailing newlines in identifiers and traversal path segments.
- Remove the implicit mbstring dependency from diagnostic formatting.
- Preserve iterable components, builders and configuration nodes before flattening ordinary iterables.
- Apply property normalizers consistently in inspection snapshots and normalized output.

### Added

- `Facades\Components`, with package-discovered `Components` alias, typed facade root and provider registration checks.

- Required deferred `ComponentServiceProvider` with Laravel package discovery, shared runtime services, transient builders/trees and support for application overrides.
- `ComponentManager` as the provider-owned entry point for construction, validation, normalization, inspection and trees.
- `ComponentBuilder::instantiate()` and `buildItems()` hooks for explicit custom constructor adapters, with documentation and regression coverage.
- `ComponentNormalizer::normalizeProperties()` as the shared property normalization pipeline.

- A string-backed enum case given to a property that takes strings, but not that enum, is stored as its value (`new Property(Resource::Icon, Heroicon::Home)` holds `"heroicon:home"`). Properties typed for the enum keep the case, and unit enums are unchanged.
- `Contracts\PlacementConfiguration`: configuration nodes that tell a component's parent how to place it (a grid column span). Every component accepts them, whatever its allowed configuration nodes.
- `Component` base class with variadic declarative constructor, class-constant property keys and immutable `with*()` copies that preserve the concrete subtype.
- `Property` immutable value object with key validation and safe value checks.
- `PropertyDefinition`, `Type` and `ComponentDefinition` schemas with required properties, defaults, unions, enums, class strings, allowed values, custom rules, child rules and constraints.
- Shorthand constructors via `Component::shorthand()`.
- `ConfigurationNode` contract for non-renderable configuration objects.
- `ValueObject` and `RuntimeValue` contracts for object property values.
- `PropertyCollection`, `ComponentCollection` and `ConfigurationCollection`.
- `ComponentTree`, `ComponentPath`, `TreeNode` and `ComponentIdentity` for occurrence-aware trees.
- `ComponentWalker` with iterative pre-order, post-order and breadth-first traversal, cycle detection and depth limits.
- `ComponentValidator`, `PropertyValidator`, `ChildValidator`, `ValidationResult` and `ValidationError` with collected and strict modes.
- `ComponentNormalizer` with deterministic, transport-independent output.
- `ComponentInspector` for developer tooling.
- Exception hierarchy under `Gcego\Components\Exceptions`.
