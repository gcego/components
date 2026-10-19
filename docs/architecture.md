# Architecture

`gcego/components` is the lowest layer of GCEGO Admin Studio. It describes
*what* an interface consists of. It never renders, stores or authorizes anything.

## Design principles

1. **Declarative objects, not arrays.** Components are declared with
   `new Component(...)` and typed constructor items.
2. **Complexity lives in the framework.** Schema, defaults, shorthand and
   validation are declared once per component class. Application code declares
   values only.
3. **Immutable definitions.** Every value object is `readonly`, collections are
   immutable, and components change only through copy methods.
4. **Explicit contracts.** Items are classified by interface, not by namespace
   or naming conventions. Constructors are never invoked during validation.
5. **Provider-owned initialization.** `ComponentServiceProvider` registers every
   runtime service and factory. The `Components` facade resolves the registered manager for
   package operations; Illuminate is a required runtime dependency.

## Class map

```text
Gcego\Components
├── Component                  base declaration type
├── Facades\                   Components facade
├── ComponentServiceProvider   required discovery and service registration
├── ComponentManager           facade-backed orchestration and factories
├── Properties\                immutable Property value
├── Schema\                    ComponentDefinition, PropertyDefinition, Type, TypeKind
├── Collections\               PropertyCollection, ComponentCollection, ConfigurationCollection
├── Trees\                     ComponentTree, ComponentPath, TreeNode
├── Contracts\                 component, configuration, builder and value contracts
├── Building\                  ComponentBuilder
├── Validation\                validators, results, errors and error codes
├── Normalization\             ComponentNormalizer, RuntimePlaceholder
├── Inspection\                ComponentInspector, ComponentInspection
├── Traversal\                 ComponentWalker, TraversalOrder, Visit, AncestorChain
├── Support\                   argument parsing, identity, diagnostics and snapshots
└── Exceptions\                ComponentException and package exceptions
```

### Deviations from the original package plan

- **Construction uses `ComponentManager`.** The `Components` facade
  exposes the provider-owned manager. Builders and trees resolve through transient
  container bindings; immutable values and schemas use ordinary constructors.
  Shorthand parsing lives in `Support\ConstructorArguments`.
- **Added `Type`/`TypeKind`.** Union, enum and class-string types need a typed
  descriptor. Parsing type strings such as `"int|string"` would be error-prone.
- **Added `ConfigurationCollection` and the `ConfigurationNode` contract.** These
  keep non-renderable configuration (a future Tailwind modifier, for example) out
  of `children()`.
- **Added `ComponentPath` and `TreeNode`.** Paths are per-occurrence values, so
  components never need parent pointers.
- **Added `ComponentInspection`.** This is the structured result of the inspector.
- `phpunit.xml.dist` and `phpstan.neon.dist` follow the `.dist` convention, so
  developers can override them locally with untracked `phpunit.xml` and
  `phpstan.neon` files.

## Constructor item classification

`ConstructorArguments::parse()` applies these rules to `new X(...$items)`:

| Item | Becomes |
| --- | --- |
| `Property` | an explicit property |
| `ComponentInterface` | a renderable child, in order |
| `ConfigurationNode` | a configuration node, in order |
| any other leading value | a shorthand `Property` for the next key in `X::shorthand()` |

Additional rules:

- A named argument (`new X(name: 'a')`) throws `InvalidConfigurationException`.
- A shorthand value after the first item throws `InvalidConfigurationException`.
- More shorthand values than declared keys throw `InvalidConfigurationException`.
- A duplicate key, including a shorthand value that collides with an explicit
  `Property`, throws `DuplicatePropertyException`.

## Initialization

Laravel discovers `ComponentServiceProvider` through Composer metadata. Its
`ComponentManager` binding is required before component construction, schema
access or runtime operations. No component method constructs independent runtime
services. Validation, normalization, inspection and trees resolve the provider's
services through the `Components` facade, and their dependency constructors have
no service defaults. The facade application is initialized by Laravel; tests clear
resolved facade instances when switching application containers.

## Lifecycle

```text
new X(...)          structural checks: item kinds, key syntax, value safety, duplicates
X::definition()     schema built per class and cached within the active Laravel scope
$x->validate()      schema checks over the whole tree, all errors collected with paths
$x->assertValid()   the same checks, throwing InvalidComponentException
$x->normalize()     canonical array for registries and runtimes
```

Schema validation is deliberately **not** run in constructors:

- Children are built before their parent exists. A constructor cannot know the
  path of a child, but tree validation can report `subjects.table.name`.
- Developers get every error at once instead of fixing them one exception at a time.
- `with*()` copies would otherwise have to re-validate at every step.

Runtimes and registries must call `assertValid()` (or `validate()`) before
using a definition. `Component::validate()` memoizes its result per instance and
active manager scope. A weak reference prevents the validation cache from keeping
a previous manager and its request context alive.

## Immutability policy

- `Property`, `PropertyDefinition`, `ComponentDefinition`, `Type`, every
  collection, `TreeNode`, `ValidationError`, `ValidationResult` and
  `ComponentIdentity` are `readonly`.
- `ComponentPath` is immutable. Its only internal state is a lazily computed
  string cache.
- `Component` has no public properties. Its internals change only on clones
  inside the `final` copy methods `withProperty()`, `withoutProperty()`,
  `withChildren()`, `withAppendedChildren()` and `withConfigurations()`. These
  return `static`, so copies keep the concrete subclass. `__clone()` is `final`
  and resets the memoized validation result and its weak scope reference.
- Shallow cloning is safe because everything a component holds is immutable:
  - validated arrays are recursively copied to detach PHP references;
  - enum cases are singletons;
  - object property values must implement `ValueObject` (an immutable value) or
    `RuntimeValue` (opaque, never inspected).
- Accessors return arrays (copied on write) or immutable collections, so callers
  cannot mutate a component indirectly.

## Identity

Three identities are kept apart:

| Identity | Source | Scope |
| --- | --- | --- |
| type | `ComponentDefinition::$type`, which defaults to the class name | every instance of a class |
| explicit id | the optional `Component::Id` property | stable across requests |
| occurrence | the deterministic `ComponentPath` | one position in one tree |

`TreeNode::identity()` returns a `ComponentIdentity` holding all three. Its
`stableKey()` returns `id:<explicit-id>` when an id is present, otherwise
`path:<occurrence-path>`. These namespaces prevent ids from colliding with paths.
No random ids are generated.

## Reuse and cycles

One immutable component instance may appear at several positions. Each
occurrence gets its own `TreeNode` and path, for example `root.shared` and
`root.shared#1`. A reused instance that carries an explicit id is reported as
`DuplicateId`, because ids must be unique within a tree.

The base `Component` cannot form cycles, because children must exist before
their parent. Third-party `ComponentInterface` implementations could still
return themselves as children. To guard against that, `ComponentWalker`
tracks ancestors and throws `InvalidChildException`. Traversal is iterative,
and nesting deeper than `ComponentWalker::DefaultMaxDepth` (256) is rejected.

## Performance

- Definitions are built once per class within the active Laravel scope.
- Property lookup is a single array access.
- Traversal is lazy and iterative. Paths and ancestor chains share their parents,
  so memory per node is constant.
- Laravel discovers the provider once; the manager and schema cache are scoped
  to the current request or job. Stateless runtime services are shared, while
  builders and root-specific trees are created afresh.

`composer benchmark` builds a 201-component page and times construction,
lookup, traversal, validation and normalization.
