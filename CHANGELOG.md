# Changelog

All notable changes to `gcego/components` are documented in this file.
The project follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Support extending layout classes with shared name rules in `Layout\NamedComponent`.
- Add `composer check` as the complete local quality gate.

- Add facade layout schemas, named section/group/item factories, child/components nesting methods and dynamic component collections.

### Changed

- Keep `ComponentManager` and `ComponentServiceProvider` at the package root; group collections, schemas, properties and trees in responsibility folders. Update consumer imports as described in the namespace migration guide.
- Replace the manual `ComponentManager::instance()` resolver with the Laravel `Components` facade.

- Illuminate Container, Contracts and Support are required runtime dependencies.
- Component construction, schema access and runtime services require provider registration. Missing registration raises an actionable package exception.
- Component convenience methods resolve provider services. Service constructors require injected dependencies instead of constructing default services.

### Fixed

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
