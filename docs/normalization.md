# Normalization

`Normalization\ComponentNormalizer` turns a component tree into a canonical,
deterministic PHP array for registries, caches, renderers and tooling. Closures
and other runtime values never appear in the output.

```php
use Gcego\Components\Facades\Components;
use Gcego\Components\Normalization\ComponentNormalizer;

$normalized = $component->normalize();
$normalized = Components::normalize($component);
$withSchema = app()->make(ComponentNormalizer::class, ['includeSchema' => true])->normalize($component);
```

`normalize()` does not validate. Call `assertValid()` first when the output must
describe a valid tree.

## Shape

Every node, the root included, has the same keys in the same order. This is the
real output for a card with an id, columns, a configuration node and a child
whose visibility is computed:

```php
use Gcego\Components\Components\Inputs\TextInput;
use Gcego\Components\Components\Layout\Card;
use Gcego\Components\Definitions\{Children, Columns, Configurations, Id, Label, Name, VisibleWhen};
use Gcego\Components\Evaluation\Get;

$card = Card::make(
    Name::make('profile'),
    Id::make('profile-card'),
    Label::make('Profile'),
    Columns::make(2),
    Configurations::make(new Style('p-4')),
    Children::make([
        TextInput::make(Name::make('email'), Label::make('Email'), VisibleWhen::make(fn (Get $get): bool => $get('x') === 1)),
    ]),
);

$card->normalize();
```

```php
[
    'type' => 'layout.card',                 // schema type identifier
    'class' => Card::class,                  // concrete class label
    'id' => 'profile-card',                  // explicit id or null; never generated
    'path' => 'profile',                     // deterministic occurrence path
    'properties' => [                        // defaults merged with explicit values, sorted by key
        'columns' => 2,
        'id' => 'profile-card',
        'label' => 'Profile',
        'name' => 'profile',
    ],
    'configurations' => [                    // declaration order
        ['class' => 'Style', 'data' => ['classes' => 'p-4']],
    ],
    'children' => [                          // same shape, declaration order
        [
            'type' => 'inputs.textInput',
            'class' => TextInput::class,
            'id' => null,
            'path' => 'profile.email',
            'properties' => [
                'label' => 'Email',
                'name' => 'email',
                'visible' => new RuntimePlaceholder(Computed::class), // ->class === 'Gcego\Components\Evaluation\Computed'
            ],
            'configurations' => [],
            'children' => [],
        ],
    ],
    // 'schema' => [...], only with includeSchema: true
]
```

With `includeSchema: true`, each node gets a `schema` key holding
`ComponentDefinition::toArray()`: `type`, `properties` (each with `name`,
`types`, `required`, `nullable`, `hasDefault`, `default`, `allowedValues`,
`hasRule`, `ruleDescription`, `hasNormalizer`, `description`),
`allowsUnknownProperties`, `allowsChildren`, `allowedChildren`, `minChildren`,
`maxChildren`, `allowedConfigurations` and `constraintCount`. Rule and
normalizer closures are reported as flags only.

## Guarantees

- **Deterministic.** Properties are sorted by key; children and configuration
  nodes keep their declaration order. Equal declarations give equal output,
  whatever order their definitions were passed in.
- **Effective values.** Schema defaults are included alongside explicit values.
  A property definition's `normalizer` closure (`Field::normalizeUsing()`) runs
  on the value when the value matches the declared type and is not a runtime
  value, so invalid values remain inspectable without calling typed closures
  with incompatible input. Normalizers should be pure functions.
- **Iterative.** The tree is walked post-order with the provider's
  `ComponentWalker`, so normalization has the same cycle and depth protection
  as traversal.

## Values

| Value | Normalized as |
| --- | --- |
| `null`, scalars, enum cases | unchanged |
| arrays | converted recursively, keys preserved |
| `Contracts\ValueObject` | its `normalize()` result, converted recursively |
| `Contracts\RuntimeValue` (including `Evaluation\Computed`) | a `Normalization\RuntimePlaceholder` whose public `class` holds only the class name |
| configuration node | `['class' => <class label>, 'data' => <normalize() result, converted>]` |

`Computed` values, which hold the closures from `VisibleWhen::make()`,
`Label::using()`, `DefaultValue::using()`, `AfterStateUpdated::make()` and
engine builders, are runtime values. They become
`RuntimePlaceholder('Gcego\Components\Evaluation\Computed')`; the closure is
never called, serialized or exposed. Evaluate them with `evaluateProperty()` or
the live engine (see [reactivity](reactivity.md)).

When an object implements both `RuntimeValue` and `ValueObject`, the runtime
contract wins and its `normalize()` is never called. Runtime values also bypass
property normalizer closures. Schema defaults (in `includeSchema` output and in
direct `ComponentDefinition::toArray()` / `PropertyDefinition::toArray()`
exports) pass through the same value conversion.

Nesting deeper than 64 levels, or any other value (a closure passed to
`normalizeValue()`, or a `ValueObject` whose `normalize()` returns a service
object), throws `InvalidConfigurationException` naming the location:

```text
Value at value[a] (stdClass) cannot be normalized. Only null, scalars, arrays, enums, ValueObject and RuntimeValue instances are supported.
```

Inside a tree the location reads like `profile property "amount"` or
`profile configuration #0`.

`Contracts\Normalizable` (`normalize(): mixed`) is the shared contract of
`ValueObject`, `ConfigurationNode` and components. Implementations must return
only null, scalars, arrays, enums or other normalizable values.

## Single values

`normalizeValue()` converts one value with the same rules:

```php
Components::normalizeValue(['price' => new Money(500, 'XAF'), 'status' => Status::Draft]);
// ['price' => ['amount' => 500, 'currency' => 'XAF'], 'status' => Status::Draft]
```

`ComponentNormalizer::normalizeProperties(array $values, ComponentDefinition
$definition, string $location = 'component')` applies schema normalizers and
sorts by key, as the tree normalizer does for each node.

## Not JSON

The output keeps enum cases and `RuntimePlaceholder` objects, because those are
unambiguous in PHP. Encoding them for a transport (JSON, a Livewire snapshot, a
cache) is the transport's job. With plain `json_encode()`, a backed enum
becomes its value, a pure enum fails, a placeholder becomes
`{"class": "Gcego\\Components\\Evaluation\\Computed"}`, and an empty
`properties` map becomes `[]` rather than `{}`.

## Inspection

`Components::inspect($component)` returns an `Inspection\ComponentInspection`
for developer tooling. It runs validation and passes every property map
through the normalizer, so it never exposes closures or runtime objects:

| Field | Content |
| --- | --- |
| `class`, `type`, `id`, `path`, `depth` | identity of the inspected occurrence |
| `supportedProperties`, `requiredProperties`, `allowsUnknownProperties` | schema property metadata |
| `explicitProperties`, `effectiveProperties`, `defaults` | normalized property maps, sorted by key |
| `childCount`, `childrenAllowed`, `allowedChildren` (null when any child is accepted), `minChildren`, `maxChildren` | child metadata |
| `configurationClasses` | class labels of attached configuration nodes |
| `errors` | `list<ValidationError>` for the component and all its descendants |

`isValid()` and `toArray()` (which adds `valid` and converts errors with
`ValidationError::toArray()`) are available. `app(ComponentInspector::class)->inspectTree($root)`
returns one inspection per occurrence in document order, each listing only its
own errors.

## Class metadata

Named classes keep their fully qualified name. Anonymous class labels omit
PHP's NUL-delimited source path and line suffix in normalized output,
inspections, schema exports and validation diagnostics
(`Support\ValueDescriber::className()`). Such labels are diagnostic
identifiers, not autoloadable class strings or identity keys. Internal type
checks still use the real classes.

Scalar values are preserved as declared. The normalizer does not redact
application strings or sanitize data for an HTML context (see
[security](security.md)).

## Values as stored

`Properties\Property` stores only supported values: null, scalars, enums,
`ValueObject`, `RuntimeValue` and arrays of them nested at most 32 levels.
`Support\ValueSnapshot::copy()` detaches PHP array references when a value is
stored, without cloning objects, so later changes to the caller's array do not
reach the component. Value objects are trusted to be immutable.

## Exporting a schema

```php
$schema = require app_path('Ui/admin.php');
$schema->assertValid();
$definition = Components::normalize($schema);
```

`php examples/run.php --normalized` prints the full [usage](usage.md) example as
JSON, and `--custom --normalized` prints the custom component example, whose
`Money` value object becomes `{"minorUnits": 250000, "currency": "XAF"}`. The
normalized root is a node with the same shape as every child; factory syntax
and normalized output serve different roles.
