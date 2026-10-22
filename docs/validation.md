# Validation

Mistakes are caught at two points. Definition-time checks run while a component
is made and throw immediately. Tree validation runs over a whole tree through
`validate()` or `assertValid()` and reports every error with its path.

Every package exception implements `Exceptions\ComponentException`, so one
`catch (ComponentException $e)` handles any of them.

## Definition-time errors

These throw before the component exists.

| Exception (base class) | Thrown when |
| --- | --- |
| `InvalidDefinitionException` (`InvalidArgumentException`) | a definition's own `make()` rejects its value; `make()`/`withDefinitions()` receive a definition the component does not declare, a value the property rejects, a computed `name` or `id`, a repeated property, a child or configuration node the component does not accept; or the result misses a required property, has too few or too many children, fails a schema constraint or repeats an id in its subtree. The engine copy methods of declarative components (`withProperty()`, `withChildren()`, `withAppendedChildren()`, `withConfigurations()`) run the same checks, and `withoutProperty()` refuses to remove a required property |
| `InvalidConfigurationException` (`InvalidArgumentException`) | a declarative class is constructed or used through `new`, `define()`, `macro()` or `Components::define()`; a `configureUsing()` callback returns something other than definitions; an engine constructor receives an unsupported item, a named argument, misplaced or excess shorthand; a schema declaration is inconsistent (`Invalid component definition: Property "size" cannot be required and declare a default at the same time.`); a value cannot be normalized; the service provider is missing or the manager is used outside its scope |
| `InvalidPropertyException` (`InvalidArgumentException`) | a `Property` key is invalid or its value is not a supported kind (closures, arbitrary objects, arrays nested deeper than 32 levels) |
| `DuplicatePropertyException` (extends `InvalidPropertyException`) | an engine constructor receives the same key twice |
| `InvalidBuilderException` (`BadMethodCallException`) | an engine builder is misused (unknown method, missing or extra value, invalid child, nesting deeper than 32 levels) or `Component::collect()` receives a non-component |

`EvaluationException` (a closure parameter cannot be injected or a computed
result is invalid) is covered in [reactivity](reactivity.md), and
`PatchException` in [patching](patching.md).

Definition-time messages name the component, the definition and the problem:

```text
Gcego\Components\Definitions\Label expects non-empty plain text, string "  " given.
Gcego\Components\Definitions\Href expects a relative URL or an http, https, mailto or tel URL, string "javascript:alert(1)" given.
Gcego\Components\Components\Display\Text does not accept Gcego\Components\Definitions\Variant (property "variant"). Accepted properties: name, label, description, id, hidden, visible, hiddenOn, visibleOn, columnSpan, columnStart, extraAttributes, content, color.
Gcego\Components\Components\Display\Stat rejects Gcego\Components\Definitions\Value: property "value" expects string|int|float, null given.
Gcego\Components\Components\Display\Text received property "content" more than once. Pass each definition once.
Gcego\Components\Components\Display\Text does not accept Gcego\Components\Components\Display\Text as a child.
Gcego\Components\Components\Inputs\TextInput requires "name".
Gcego\Components\Components\Inputs\TextInput cannot remove required "name".
Gcego\Components\Components\Actions\SplitButton needs at least 1 child component(s), 0 given.
Gcego\Components\Components\Inputs\TextInput: minLength must not exceed maxLength.
Gcego\Components\Components\Layout\Stack contains id "save" more than once. Ids must be unique within a tree; give each copy its own Id::make().
```

An unsupported definition whose property key is within three edits of a
declared key adds `Did you mean "..."?`. Runtime values such as
`VisibleWhen::make(fn ...)` are not type-checked at definition time; their
result is checked when they are evaluated, and an invalid result throws
`EvaluationException` (see [reactivity](reactivity.md)). The identity
properties `name` and `id` refuse runtime values
(`… property "name" expects string (identity properties cannot be computed), …`).
An explicit `null` is accepted for a nullable property, as `validate()`
accepts it.

`make()` checks the component it returns as a whole
(`DefinitionValidator::assertComplete()`): required properties, child-count
bounds, schema constraints and unique ids across its subtree, so a duplicate id
is refused as soon as the parent holding both copies is made. Tree validation
still matters for trees no `make()` checked: patched trees, engine components
of framework packages, values set through builders, and subtrees deeper than
the walker limit.

## Tree validation

### Collected mode

```php
use Gcego\Components\Validation\ErrorCode;

$result = $component->validate();   // or Components::validate($component)

$result->isValid();
$result->hasErrors();
$result->errors();                                // list<ValidationError>
$result->first();                                 // ?ValidationError
$result->errorsAt('subjects');                    // errors at one path
$result->errorsWithCode(ErrorCode::UnknownProperty);
$result->merge($other);                           // new combined result
count($result);
foreach ($result as $error) {}
$result->toArray();                               // list of ValidationError::toArray()
echo $result->format();                           // every error, blank-line separated
```

`validate()` walks the component and all its descendants. The result is
memoized on the component within the active provider scope; copies start
without a cached result.

### Strict mode

```php
$component->assertValid();   // returns $component when valid
app(ComponentValidator::class)->assertValid($component);   // returns void
$result->throwIfInvalid();
```

When validation fails, these throw `InvalidComponentException`. Its message
starts with `Component validation failed with N error(s).`, lists up to ten
formatted errors and ends with `... and N more error(s).` when there are more.
`$exception->result()` returns the full `ValidationResult`.

`validate()` collects schema errors, but it can still throw:
`InvalidChildException` for a cycle or a tree deeper than the walker's maximum
depth (see [composition](composition.md#componentwalker)), and any exception
thrown by an application constraint closure.

## Errors

```text
Invalid property "perPage" on component "fixtures.table".

Expected: integer greater than zero
Received: int 0

Path: subjects
```

A `ValidationError` has public `code` (`ErrorCode`), `message`, `path`,
`componentType`, `componentClass`, `subject`, `expected`, `received` and `hint`,
plus `format()` (also `__toString()`) and `toArray()`:

```php
[
    'code' => 'invalid_value',
    'message' => 'Invalid property "perPage" on component "fixtures.table".',
    'path' => 'subjects',
    'componentType' => 'fixtures.table',
    'componentClass' => 'Table',
    'subject' => 'perPage',
    'expected' => 'integer greater than zero',
    'received' => 'int 0',
    'hint' => null,
]
```

`received` is produced by `Support\ValueDescriber`: strings are truncated to 40
characters, arrays are described as `array(N)` and objects by type only.
Ordinary strings remain visible in diagnostics; do not assume error messages
redact secrets.

Each property reports at most one error: the first failing check out of
presence, type, allowed values and rule. Unknown-property errors come first,
then property errors in schema order, then child, configuration and constraint
errors, then duplicate ids.

## Error codes

Every `ErrorCode` case is emitted by the validator:

| `ErrorCode` | Value | Message | Raised when |
| --- | --- | --- | --- |
| `MissingProperty` | `missing_property` | `Missing required property "x" on component "t".` | a required property has no explicit value |
| `InvalidType` | `invalid_type` | `Invalid property "x" on component "t".` | a value does not match the property types, including an enum backing value (`'draft'`) given to a property typed for the enum case |
| `InvalidValue` | `invalid_value` | `Invalid property "x" on component "t".` | a value is not in `allowedValues` or fails the property `rule`, including an id that does not match `Component::IdPattern` |
| `UnknownProperty` | `unknown_property` | `Unknown property "x" on component "t".` | the schema does not declare the key and does not allow unknown properties; `hint` is `Did you mean "..."?` when a declared key is within two edits |
| `ChildrenNotAllowed` | `children_not_allowed` | `Component "t" does not accept child components.` | a leaf component has children |
| `InvalidChild` | `invalid_child` | `Component "t" does not accept a child of type "c".` | a child is not an instance of an allowed class; reported at the child's path |
| `TooFewChildren` | `too_few_children` | `Component "t" has too few children.` | the child count is below `minChildren` |
| `TooManyChildren` | `too_many_children` | `Component "t" has too many children.` | the child count is above `maxChildren` |
| `InvalidConfiguration` | `invalid_configuration` | `Component "t" does not accept configuration "C".` | a configuration node is not an allowed class (placement configuration is always allowed) |
| `DuplicateId` | `duplicate_id` | `Duplicate component id "x" on component "t".` | an explicit id is already used earlier in the tree; reported at the later occurrence, with `received` naming the first path |
| `ConstraintFailed` | `constraint_failed` | the constraint's message | a schema constraint returned a message |

`Computed` values are skipped by tree validation; they are checked when
evaluated. An explicit `null` passes when the property is `nullable`.

Catalog constraints:

| Components | Message |
| --- | --- |
| text fields (`TextInput`, `Textarea`, `EmailInput`, `PasswordInput`, `SearchInput`, `TelInput`, `UrlInput`, `CodeEditor`, `RichEditor`) | `minLength must not exceed maxLength.` |
| numeric fields (`NumberInput`, `RangeInput`) | `min must not exceed max.` |

## Schema rules

```php
use Gcego\Components\Contracts\ComponentInterface;
use Gcego\Components\Schema\{ComponentDefinition, PropertyDefinition, Type};

protected static function defineSchema(): ComponentDefinition
{
    return parent::defineSchema()
        ->withType('tables.table')
        ->withProperties(
            new PropertyDefinition('perPage', Type::int(), required: true,
                rule: fn (mixed $value): bool => is_int($value) && $value > 0,
                ruleDescription: 'integer greater than zero'),
            new PropertyDefinition('mode', Type::string(), allowedValues: ['simple', 'full']),
        )
        ->withAllowedChildren([Column::class], min: 1, max: 50)
        ->withAllowedConfigurations([Style::class])
        ->withConstraint(function (ComponentInterface $table): ?string {
            return $table->childCount() > 10 && $table->get('paginated') !== true
                ? 'Tables with more than 10 columns must be paginated.'
                : null;
        });
}
```

- **Unknown properties** are rejected by every subclass. Only the generic base
  `Component` accepts them. Opt in with `->withUnknownProperties()`.
- **Allowed children** and **allowed configurations** match by `instanceof`, so
  interfaces and parent classes work.
- **Constraints** receive the component and return `null`, a message string, or
  an iterable of messages (`null` entries are skipped). Each message becomes one
  `ConstraintFailed` error at the component's path.

See [properties](properties.md) for `PropertyDefinition`, `Type` and `Field`.

## Testing custom components

Declarative components reject a misspelled or unsupported definition as soon as
they are made:

```php
it('rejects a definition the component does not declare', function (): void {
    expect(fn () => Card::make(Required::make()))
        ->toThrow(InvalidDefinitionException::class, 'does not accept');
});
```

Child counts, constraints and duplicate ids are refused by `make()` as well:

```php
it('requires at least one dropdown item', function (): void {
    expect(fn () => SplitButton::make(Label::make('Save')))
        ->toThrow(InvalidDefinitionException::class, 'needs at least 1 child component(s)');
});
```

Engine components of framework packages collect property mistakes in
`validate()`:

```php
it('rejects a misspelled option', function (): void {
    $result = (new SearchColumn('email', new Property('serchable', true)))->validate();

    expect($result->first()?->code)->toBe(ErrorCode::UnknownProperty)
        ->and($result->first()?->hint)->toBe('Did you mean "searchable"?');
});
```

## Scope

`make()` checks every definition and the finished component (required
properties, child counts, constraints, unique ids). `assertValid()` checks a whole tree and `validate()` returns the collected
errors; `normalize()` does not validate implicitly. Engine builders offer
`buildValidated()` and `validate()`.

Catalog controls validate configuration types, supported properties and
configured bounds: text length bounds must be ordered, numeric metadata must be
finite and steps positive. An input's `Required` definition marks the field as
required; it does not validate incoming request data. Perform server-side
request validation in the consuming application.
