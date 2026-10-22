# Security model

Component definitions are application code. This package checks the values
that reach a component, keeps closures out of exported data and bounds tree
walks. It does not authorize requests, escape output or sandbox PHP code.
Applications authorize access to their interfaces and data before serving them,
and renderers escape for their output context.

## Typed definitions

Typed definitions are the input boundary for catalog and custom components:

- **Plain text.** `Label`, `Title`, `Description`, `Content`, `Placeholder`,
  `HelpText`, `Message`, `Alt`, `Action` and the other `TextValue` definitions
  hold non-empty plain text. `Label::make('<b>x</b>')` is stored as is; nothing
  in this package marks text as trusted HTML, so renderers must escape it.
- **URLs.** `Href` (`url`), `Src` (`src`) and `Poster` (`poster`) accept only
  what `Support\SafeUrl::isSafe()` accepts (see [URLs](#urls)) and throw
  `InvalidDefinitionException` otherwise:

  ```text
  Gcego\Components\Definitions\Href expects a relative URL or an http, https, mailto or tel URL, string "javascript:alert(1)" given.
  ```

- **Actions.** `Action` (the `action` of `Button` and `Form`, which a form
  renders as its action URL) takes an action name such as `save` or a safe URL;
  `Action::make('javascript:alert(1)')` throws `…\Action expects an action name
  or a relative URL or an http, https, mailto or tel URL, string
  "javascript:alert(1)" given.`
- **Attributes.** `Attributes` (`extraAttributes`) follows
  `Support\SafeAttributes`. Names must match
  `/\A[A-Za-z_][A-Za-z0-9_:.\-]*\z/` (so `@click` and `:href` are refused by
  the name rule), and event handlers (`on*`), framework directives (`@*`, `:*`,
  `x-*`, `wire:*`, `hx-*`, `v-*`), `style` and `srcdoc` are refused,
  case-insensitively. Values must be scalars or null. The URL attributes
  `href`, `src`, `action`, `formaction`, `poster`, `xlink:href`, `cite`,
  `background`, `data` and `ping` must pass `SafeUrl`, as must every candidate
  of `srcset` and `imagesrcset`. No value may start with an executable scheme
  (`javascript:`, `vbscript:`; whitespace and control characters are ignored
  while reading it), which also covers `data-*` attributes that scripts copy
  into URLs.
- **Values.** `Value` and `DefaultValue` accept only supported property values
  (null, scalars, enums, value objects, arrays of them); a closure or a runtime
  value such as `Evaluation\Computed` throws.
- **Schema.** Every definition is checked against the receiving component's
  schema, so a definition cannot set a property the component does not declare
  or a value of the wrong type. The finished component is checked too:
  required properties, child counts, schema constraints and unique ids.

Catalog and custom components are declared only through `make()`: their
constructor runs only inside `make()`, and `define()`, `macro()` and
`Components::define()` throw.

### Component schemas

The URL and attribute rules are also part of the catalog schemas, so they
apply however a value reaches a component:

| Property | Schema rule | Components |
| --- | --- | --- |
| `url` | `SafeUrl::accepts()` | the action family (`Button`, `IconButton`, `SplitButton`, `DropdownItem`) and navigation items (`Link`, `Breadcrumb`, `MenuItem`) |
| `src` | `SafeUrl::accepts()` | `Image`, `Avatar`, `Audio`, `Video`, `Embed` |
| `poster` | `SafeUrl::accepts()` | `Video` |
| `action` | `SafeUrl::accepts()` | `Button`, `IconButton`, `Form` |
| `extraAttributes` | `SafeAttributes::isSafe()` | every `SchemaComponent` (`HasExtraAttributes`) |

The engine copy methods of declarative components (`withProperty()`,
`withChildren()`, `withAppendedChildren()`, `withConfigurations()`) run the
same checks as `make()`, so
`$link->withProperty(new Property('url', 'javascript:alert(1)'))` throws
`…\Link rejects Gcego\Components\Properties\Property: property "url" expects
a relative URL or an http, https, mailto or tel URL, string
"javascript:alert(1)" given.` A custom `PropertyValue` definition whose
`key()` is `url` is checked against the same rule. Engine components of
framework packages check schema rules only in `validate()`, so values they
receive through `new Property(...)`, their copy methods or builders
(`->set('url', ...)`) are refused when the tree is validated; validate every
schema before rendering it.

These checks refuse executable schemes; they do not escape output. Renderers
still escape every value for their output context.

## URLs

`SafeUrl::isSafe(string $url)` accepts relative URLs and the `http`, `https`,
`mailto` and `tel` schemes (`SafeUrl::Schemes`), case-insensitively:

| Input | Result | Why |
| --- | --- | --- |
| `/x`, `#frag`, `?q=1`, `foo/bar:baz` | safe | relative |
| `https://a`, `HTTPS://a`, `mailto:a@b`, `tel:1` | safe | allowed scheme |
| `javascript:alert(1)`, `vbscript:x`, `data:text/html,x`, `ftp://x` | unsafe | scheme not allowed |
| `java\tscript:x`, ` java script:x` | unsafe | control characters are refused; whitespace is ignored while reading the scheme, as browsers do |
| `//evil.example`, `/\evil.example`, `\\evil.example` | unsafe | protocol-relative URLs leave the current origin; write `https://` explicitly |
| `''`, `'   '`, `"a\nb"` | unsafe | empty, or contains control characters |

Other members:

| Member | Purpose |
| --- | --- |
| `SafeUrl::accepts(mixed $value): bool` | the schema rule form: a string that `isSafe()` |
| `SafeUrl::Description` | `a relative URL or an http, https, mailto or tel URL`, used in messages |
| `SafeUrl::hasExecutableScheme(string $value): bool` | whether a value starts with a scheme in `SafeUrl::ExecutableSchemes` (`javascript`, `vbscript`), ignoring whitespace and control characters |

`SafeUrl` blocks executable schemes and protocol-relative URLs. It does not
restrict hosts of absolute URLs, so open redirects and off-site links remain
the application's concern.

## Closures and evaluation

Closures are never accepted as plain property values. `new Property('x', fn ()
=> 1)` throws `InvalidPropertyException` (`... value is Closure. Wrap runtime
callables in an object implementing Gcego\Components\Contracts\RuntimeValue.`),
and `Value::make(fn () => 1)` throws `InvalidDefinitionException`.

Closures enter only through explicit trusted-callback APIs:
`VisibleWhen`/`HiddenWhen`/`DisabledWhen`/`ReadOnlyWhen`/`RequiredWhen::make()`
(the `Condition` definitions), `TextValue::using()` (for example
`Label::using()`), `DefaultValue::using()`, `AfterStateUpdated::make()` and
engine builder methods. Each wraps the closure in `Evaluation\Computed`, a
`RuntimeValue`.

- Making, validating, normalizing or inspecting a component never calls a
  `Computed` closure. Tree validation skips computed properties, and
  normalization replaces them with a `RuntimePlaceholder` that carries only the
  class name (see [normalization](normalization.md)).
- A closure runs only when `evaluateProperty()`, `evaluatedProperties()`,
  `isHiddenIn()`, `isVisibleIn()`, `displayLabel()` or the live engine
  evaluates it. The evaluator injects parameters from the context and may
  resolve typed parameters from the Laravel container; records
  (`QueueableEntity`, such as models) are injected only from the context. See
  [reactivity](reactivity.md).
- A computed result created by `TextValue::using()` or `DefaultValue::using()`
  first passes the definition's own `make()` checks (`Computed::$check`), so
  `Label::using(fn () => '  ')` fails at evaluation like `Label::make('  ')`.
  Every result is then checked against the property definition's type,
  allowed values and rule. An invalid result throws `EvaluationException`.
- The identity properties `name` and `id`
  (`DefinitionValidator::IdentityProperties`) refuse computed values, because
  tree paths, patches and lookups read them before anything is evaluated.

These closures, like custom rules, normalizers, constraints and component
classes, are trusted application code.

## Values and limits

Properties accept only supported values (null, scalars, enums, `ValueObject`,
`RuntimeValue` and arrays of them). Array references are detached when stored,
and property arrays are limited to 32 nesting levels. Builder item lists and
`Component::collect()` have the same 32-level limit. Tree traversal rejects
ancestor cycles and, by default, depths beyond 256
(`ComponentWalker::DefaultMaxDepth`). Normalization has a separate 64-level
value-depth limit. These limits do not cap total tree or collection size.

Value objects normalize recursively and unsupported output is rejected.
Normalization is not HTML escaping or output sanitization. Ordinary strings and
records remain present in normalized output and in bounded diagnostics
(`ValueDescriber` truncates strings to 40 characters but does not redact them);
avoid embedding secrets in layout properties.

## Classes and scopes

Component and builder class names come from trusted application or package
code; this package does not map request-provided class names into an
authorization policy. Before building an engine component, the manager checks
that the class is a concrete `Component`, that its builder is a concrete
`ComponentBuilder` for that class, and that the built result has the requested
type. These checks catch configuration mistakes; they do not sandbox custom
code. Catalog aliases resolve registered application classes only, and the
catalog is scoped, so it resets with each request or job scope; register
extensions from provider callbacks so they are restored in later scopes.

The required service provider owns service registration and preserves existing
application bindings. The `ComponentManager` is scoped: schema caches are not
process-static, so defaults and closures do not carry a previous application's
context forward, and validation caches are tied to the scope through weak
references. A retained manager cannot operate on an inactive application or
scope, cannot be serialized and redacts container contents from debug output.

Anonymous PHP class names contain source filenames internally. Exported class
metadata and diagnostics remove that suffix. Raw property values and schema
accessors used at runtime are unchanged; use normalization and schema exports
when producing metadata for transport.

Form settings, URLs, rich text, table records and button action names are
configuration data. They do not implement authorization, file handling, request
validation or safe HTML rendering. Example records and flags are demonstration
data.

## Releases

Release archives exclude local environment files, development directories,
dependencies and test artifacts; documentation is included. Before publishing:

```bash
composer check          # validate --strict, strict PSR-4 autoload, pint --test, phpstan, tests
composer audit --locked
composer archive
```

A passing dependency audit reports only the advisories known at the time of the
check. The [runnable examples](usage.md) validate declarations before export
and are exercised by integration tests.
