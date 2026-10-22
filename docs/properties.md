# Properties

Components store their configuration as `Property` values and describe what
they accept with a schema of `PropertyDefinition`s. Applications never create
properties directly: every typed [definition](definitions.md)
(`Name::make('subjects')`, `Label::make('Subjects')`, …) expands into the
properties it sets, and the component checks them against its schema. This page
describes that storage layer and the schema API used by custom components and
framework packages.

## `Property`

```php
use Gcego\Components\Properties\Property;

// What Name::make('subjects') expands into:
new Property('name', 'subjects');
```

`Properties\Property` is a `final readonly` value object with public `$key`
and `$value`. Arrays are copied by value when the property is made.

**Keys** must match `Property::KeyPattern`: a letter or underscore, followed by
letters, digits, `_`, `.` or `-`, at most 128 characters in total. Other keys
throw `InvalidPropertyException`:

```text
Invalid property key "1bad". Keys must match /\A[A-Za-z_][A-Za-z0-9_.\-]{0,127}\z/ (a letter or underscore followed by letters, digits, "_", "." or "-", at most 128 characters).
```

**Supported values:**

| Value | Notes |
| --- | --- |
| `null`, `bool`, `int`, `float`, `string` | class strings are plain strings |
| arrays | of supported values, nested at most 32 levels |
| enum cases | backed and pure enums |
| `Contracts\ValueObject` | immutable objects that can `normalize()` themselves |
| `Contracts\RuntimeValue` | opaque runtime-only values, such as `Evaluation\Computed` |

`Property::isSupportedValue(mixed $value): bool` reports whether a value is
storable; value definitions such as `Value`, `DefaultValue` and `Data` use it.
Anything else throws `InvalidPropertyException`, naming the offending location:

```php
new Property('options', ['nested' => [new stdClass]]);
// Unsupported value for property "options": value[nested][0] is stdClass. Use a scalar, array, enum,
// or an immutable object implementing Gcego\Components\Contracts\ValueObject. ...
```

Raw closures are refused (`Wrap runtime callables in an object implementing
Gcego\Components\Contracts\RuntimeValue.`). Reactive definitions
(`VisibleWhen::make(...)`, `Label::using(...)`, `AfterStateUpdated::make(...)`)
wrap their callbacks in the trusted `Evaluation\Computed` runtime value; see
[reactivity](reactivity.md). Schema rules and normalizers are separate trusted
APIs. A package that needs another kind of runtime value implements the marker
interface:

```php
use Closure;
use Gcego\Components\Contracts\RuntimeValue;

final readonly class Callback implements RuntimeValue
{
    public function __construct(public Closure $callback) {}
}
```

The package never inspects, serializes or invokes runtime values it does not
own. Normalization replaces them with a `RuntimePlaceholder` that carries only
the class name (see [normalization](normalization.md)).

## Reading properties

| Method | Returns |
| --- | --- |
| `get(string $key, mixed $fallback = null)` | the explicit value, else the schema default, else `$fallback` |
| `has(string $key)` | whether there is an explicit value or a schema default |
| `hasExplicit(string $key)` | whether the value was declared on the component |
| `require(string $key)` | like `get()`, but throws `MissingPropertyException` when there is neither a value nor a default |
| `properties()` | the explicit properties as a `PropertyCollection` |
| `defaults()` | the schema defaults |
| `effectiveProperties()` | defaults overlaid with explicit values, sorted by key |

Computed values are returned as their `Computed` wrapper; `evaluateProperty()`
runs them (see [reactivity](reactivity.md)). See [components](components.md)
for the rest of the component API.

## Absent vs. null

An absent property and a property explicitly set to `null` are different:

| Declaration | `get()` | `has()` | `hasExplicit()` | Validation |
| --- | --- | --- | --- | --- |
| not declared, no default | `$fallback` (`null`) | `false` | `false` | fails if `required` |
| not declared, default `'medium'` (`Spacer` `size`) | `'medium'` | `true` | `false` | passes |
| explicit `null` (`DefaultValue::make(null)`) | `null` | `true` | `true` | passes only if the definition is `nullable` |

An explicit `null` for a non-nullable property is refused by `make()`:
`TextInput::make(Name::make('n'), Value::make(null))` throws
`… TextInput rejects … Value: property "value" expects string, null given.`

Internally, `Support\Absent::Value` is the sentinel that means "no default
declared", so `null` can be a real default. It is not a usable default value.

## `PropertyDefinition`

```php
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;

new PropertyDefinition(
    name: 'perPage',
    type: Type::int(),                 // or a union: [Type::int(), Type::string()]
    required: false,
    nullable: true,                    // allow an explicit null
    default: 10,                       // omit for "no default"; null is a real default
    allowedValues: null,               // strict whitelist of scalars or enum cases
    rule: fn (mixed $value): bool => is_int($value) && $value > 0,
    ruleDescription: 'integer greater than zero',
    normalizer: null,                  // fn (mixed $value): mixed, applied during normalization only
    description: 'Rows per page',
);
```

Definitions are checked when they are built. Each violation throws
`InvalidConfigurationException` with an `Invalid component definition: …`
message, so schema mistakes surface the first time the schema is built:

| Mistake | Message |
| --- | --- |
| invalid name | `"bad name" is not a valid property name.` |
| no types | `Property "x" must declare at least one type.` |
| non-scalar allowed value | `Allowed values of property "x" must be scalars or enum cases.` |
| required with a default | `Property "x" cannot be required and declare a default at the same time.` |
| default of the wrong type | `Default string "a" of property "x" does not match int.` |
| `null` default without `nullable` | `Default null of property "x" does not match int.` |

Defaults must also satisfy the allowed values and rule, and be supported
property values.

Public members: `$name`, `$types`, `$required`, `$nullable`, `$hasDefault`,
`$default`, `$allowedValues`, `$rule`, `$ruleDescription`, `$normalizer`,
`$description`, plus:

| Method | Purpose |
| --- | --- |
| `acceptsType($value)` | type check; `null` passes only when nullable |
| `allowsValue($value)` | strict whitelist check |
| `satisfiesRule($value)` | custom rule check |
| `expectedType()`, `expectedValues()`, `expectedRule()` | the descriptions used in error messages, such as `int\|string\|null` |
| `isCallback()` | whether the only type is `Type::instanceOf(Computed::class)`, as for `afterStateUpdated` |
| `toArray()` | metadata for tooling; closures are reported as `hasRule` / `hasNormalizer` flags |

`make()` and `validate()` check each property in this order and report at most
one error per property:

1. presence (`required`)
2. type (`nullable` allows `null`)
3. allowed values
4. custom rule (failures use `ruleDescription`)

An accepted `null` skips the allowed-values and rule checks, in `make()` and
`validate()` alike, so a nullable property that declares allowed values or a
rule still accepts an explicit `null`.

Computed values skip these checks until they are evaluated, except on the
identity properties `name` and `id`, which refuse them. A property whose
definition `isCallback()` accepts only a trusted callback.

## `Type`

`Schema\Type` checks are strict, with one exception: `float` also accepts
`int`.

| Factory | Accepts | `describe()` |
| --- | --- | --- |
| `Type::string()` | strings | `string` |
| `Type::int()` | integers (no numeric strings, no floats) | `int` |
| `Type::float()` | floats and integers | `float` |
| `Type::bool()` | booleans | `bool` |
| `Type::array()` | any array | `array` |
| `Type::list()` | arrays with sequential keys from zero | `list` |
| `Type::mixed()` | any supported property value | `mixed` |
| `Type::enum(Status::class)` | cases of that enum, not their backing values | `enum<…\Status>` |
| `Type::classString(Model::class)` | names of existing classes or interfaces that are `Model` or a subtype; the parent is optional | `class-string<…\Model>` |
| `Type::instanceOf(Money::class)` | instances of the class or interface, typically a `ValueObject` | `…\Money` |

`Type::enum()` throws `InvalidConfigurationException` for a non-enum, and
`classString()` and `instanceOf()` for a class or interface that does not exist.
Class-string checks may autoload the named class but never instantiate it.
Each `Type` exposes `$kind`, a `Schema\TypeKind` case (`String`, `Int`,
`Float`, `Bool`, `Array`, `List`, `Mixed`, `Enum`, `ClassString`, `Instance`),
and `$class` for the enum, class-string and instance kinds.

## `Field`

`Schema\Field` is an immutable builder for `PropertyDefinition`s, distinct from
the `Components\Inputs\FieldComponent` UI control. Every modifier returns a
new builder.

| Factory | Type |
| --- | --- |
| `Field::string($name)` | `Type::string()` |
| `Field::boolean($name)` | `Type::bool()` |
| `Field::integer($name)` | `Type::int()` |
| `Field::number($name)` | `Type::float()` |
| `Field::array($name)` | `Type::array()` |
| `Field::list($name)` | `Type::list()` |
| `Field::mixed($name)` | `Type::mixed()` |
| `Field::of($name, Type\|list<Type>)` | any type or union, such as enum, class-string and instance types |

| Modifier | Sets |
| --- | --- |
| `mustExist(bool $required = true)` | `required` |
| `nullable(bool $nullable = true)` | `nullable` |
| `defaultsTo(mixed $value)` | `default` |
| `allowedValues(array $values)` | `allowedValues` |
| `rule(Closure $rule, ?string $description = null)` | `rule`, `ruleDescription` |
| `normalizeUsing(Closure $normalizer)` | `normalizer` |
| `description(string $description)` | `description` |

`build()` returns the `PropertyDefinition`. The same build-time checks apply
at every step: `Field::string('name')->mustExist()->defaultsTo('x')` throws
because a required property cannot declare a default, and a `null` default
needs `nullable()` first.

## `ComponentDefinition`

A component class describes its schema in `defineSchema()`, starting from
`parent::defineSchema()`. `Component::definition()` builds the
`Schema\ComponentDefinition` once per class in the active Laravel scope and
shares it within that scope. Modifiers return new definitions, so a subclass
extends its parent's schema without affecting it.

```php
use Gcego\Components\Components\Display\Text;
use Gcego\Components\Concerns\HasColumns;
use Gcego\Components\Contracts\ComponentInterface;
use Gcego\Components\Foundation\SchemaComponent;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\Field;

final class ResultsPanel extends SchemaComponent
{
    protected static function defineSchema(): ComponentDefinition
    {
        return parent::defineSchema()
            ->withType('acme.resultsPanel')
            ->withConcerns(HasColumns::class)
            ->withFields([
                Field::string('subtitle')->nullable()->defaultsTo(null),
                Field::integer('perPage')->defaultsTo(10)->rule(
                    static fn (mixed $value): bool => is_int($value) && $value > 0,
                    'integer greater than zero',
                ),
                Field::string('tone')->allowedValues(['calm', 'loud']),
            ])
            ->withAllowedChildren([Text::class], min: 1, max: 3)
            ->withConstraint(static fn (ComponentInterface $panel): ?string => $panel->get('perPage') > 50
                ? 'perPage must not exceed 50.'
                : null);
    }
}
```

Each new property also needs a [custom definition](definitions.md#custom-definitions)
before it can be declared through `make()`.

| Modifier | Effect |
| --- | --- |
| `withType(string $type)` | the type identifier, matching `ComponentDefinition::TypePattern` (letters, digits, `_`, `.`, `:`, `\` and `-`) |
| `withProperties(PropertyDefinition ...)` | adds properties, replacing existing ones with the same name |
| `withFields(iterable<Field\|PropertyDefinition>)` | the same, accepting `Field` builders |
| `withConcerns(class-string<Concern> ...)` | adds the properties of reusable concerns, later ones replacing earlier ones |
| `withoutProperties(string ...$names)` | removes properties |
| `withUnknownProperties(bool $allowed = true)` | accepts undeclared property keys |
| `withAllowedChildren(?array $types, int $min = 0, ?int $max = null)` | allows children of the listed classes or interfaces (`null` allows any), with count bounds |
| `withoutChildren()` | refuses children |
| `withAllowedConfigurations(?array $types)` | restricts configuration node classes (`null` allows any; placement configuration is always accepted) |
| `withConstraint(Closure $constraint)` | adds a whole-component rule that returns error message(s), or `null` when satisfied |

Readers: `$type`, `$allowsUnknownProperties`, `$allowsChildren`,
`$allowedChildren`, `$minChildren`, `$maxChildren`, `$allowedConfigurations`,
`hasProperty()`, `property()`, `properties()`, `propertyNames()`,
`requiredProperties()`, `defaults()`, `constraints()`, `allowsChild()`,
`allowsConfiguration()` and `toArray()` (metadata for tooling; closures are
reported as counts).

`make()` checks properties, accepted child classes and, on the finished
component, required properties, child counts, constraints and unique ids.
`validate()` and `assertValid()` check the same rules across whole trees (see
[validation](validation.md)). Invalid schemas throw
`InvalidConfigurationException`, for example
`Child count bounds of "x" are invalid (min 3, max 1).`,
`"x" cannot require children while disallowing them.` or
`Property "a" is defined more than once on "x".`

The base `Component` accepts unknown properties; every subclass refuses them
unless it calls `withUnknownProperties()`. Its schema declares `name`
(non-empty string), `label`, `description` and `id` (matching
`Component::IdPattern`). `Foundation\SchemaComponent` adds the `CanBeHidden`,
`CanSpanColumns` and `HasExtraAttributes` concerns.

### Concerns

A concern implements `Contracts\Concern` and returns a list of property
definitions from `properties()`. The package ships:

| Concern | Properties |
| --- | --- |
| `Concerns\CanBeHidden` | `hidden`, `visible` (bool), `hiddenOn`, `visibleOn` (non-empty lists of operation names) |
| `Concerns\CanSpanColumns` | `columnSpan` (positive integer or `'full'`, or a breakpoint map), `columnStart` (positive integer or a breakpoint map) |
| `Concerns\HasExtraAttributes` | `extraAttributes` (attribute names mapped to scalar or `null` values, checked by `Support\SafeAttributes`: no event handlers, framework directives, `srcdoc`, `style` or unsafe URLs) |
| `Concerns\HasColumns` | `columns` (positive integer or a breakpoint map) |
| `Concerns\HasStatePath` | `statePath` (dot-separated path segments) |
| `Concerns\HasState` | `HasStatePath` plus `default` (mixed, nullable), `live`, `dehydrated` (bool), `afterStateUpdated` (trusted callback) |

An input's `Required` definition describes UI behavior; `mustExist()` (or
`required: true`) requires configuration on the component itself.
