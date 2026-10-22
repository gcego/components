# Composition

Components compose into immutable trees: a root holds ordered children, each
child may hold its own, and configuration nodes ride alongside without becoming
children. This page covers layout schemas, children, configuration nodes, grid
columns, occurrence paths, `ComponentTree` and `ComponentWalker`.

## Layout schemas

```php
use Gcego\Components\Definitions\{Children, Columns, Label, Name};
use Gcego\Components\Layout\{Group, Item, Schema, Section};

$items = [
    Item::make(Name::make('statistics')),
    Item::make(Name::make('activity')),
];

$layout = Schema::make(Children::make([
    Section::make(Name::make('dashboard'), Label::make('Dashboard'), Children::make([
        Group::make(Name::make('metrics'), Columns::make(2), Children::make($items)),
    ])),
]))->assertValid();
```

The `Layout` namespace holds four generic declarative components:

| Class | Type | Children | Extra properties |
| --- | --- | --- | --- |
| `Layout\Schema` | `schema` | any | `columns`, `statePath` |
| `Layout\Section` | `section` | any | `columns`, `statePath`, plus visibility, column span and extra attributes |
| `Layout\Group` | `group` | any | `columns`, `statePath`, plus visibility, column span and extra attributes |
| `Layout\Item` | `item` | none | visibility, column span and extra attributes |

`Schema` is the usual root. It extends `Component` directly (not
`SchemaComponent`), so it has no visibility, column span or extra attribute
properties. Sections describe major areas, groups collect related components
and items are leaves. Catalog containers such as `Card`, `Stack` and `Grid`
compose the same way (see the [catalog](component-catalog.md)).

`Schema::make()` takes typed definitions like any other component; there is no
array or named-component syntax. `normalize()` and `tree()` work on any
component, usually the schema root.

## Children

```php
use Gcego\Components\Components\Display\{Stat, Text};
use Gcego\Components\Components\Layout\Card;
use Gcego\Components\Definitions\{Children, Content, Name};

$card = Card::make(
    Name::make('dashboard'),
    Children::make([
        Stat::make(Name::make('statistics')),
        Text::make(Name::make('activity'), Content::make('Recent activity')),
    ]),
);
```

`Children::make(iterable $children)` takes component instances (builders are
built immediately) and preserves declaration order. Anything else throws
`InvalidDefinitionException`:

```text
Gcego\Components\Definitions\Children expects component instances, string "x" given.
```

It does not skip `null` or flatten nested lists; prepare dynamic lists with
`Component::collect()` (see [components](components.md#children-and-configuration-nodes)).

A schema restricts children with `withoutChildren()` and
`withAllowedChildren(?array $types, int $min = 0, ?int $max = null)`. Allowed
types match by `instanceof`, so interfaces and parent classes work. `make()`
and `withDefinitions()` refuse a child the component does not accept, for
example `... Text does not accept ... Text as a child.` They also refuse a
child count outside the `min` and `max` bounds
(`... SplitButton needs at least 1 child component(s), 0 given.`), which
`validate()` reports as `TooFewChildren` and `TooManyChildren` in trees no
`make()` checked (see [validation](validation.md)). Leaves such as `Item`,
`Text`, `Button` and the input fields refuse all children; `SplitButton`
requires at least one `DropdownItem`.

The `Contracts\HasChildren` interface (`children()`, `childCount()`,
`hasChildren()`) is part of `ComponentInterface`.

## Configuration nodes

A configuration node configures a component without being rendered as a child,
for example a styling modifier from another package. It implements
`Contracts\ConfigurationNode`, which extends `Normalizable`:

```php
use Gcego\Components\Contracts\ConfigurationNode;
use Gcego\Components\Definitions\Configurations;

final readonly class Style implements ConfigurationNode
{
    public function __construct(public string $classes) {}

    public function normalize(): array
    {
        return ['classes' => $this->classes];
    }
}

$card = Card::make(
    Name::make('card'),
    Configurations::make(new Style('rounded-lg shadow')),
    Children::make([Text::make(Name::make('body'), Content::make('Body'))]),
);

$card->childCount();                                    // 1
$card->configurations()->first(Style::class)->classes;  // 'rounded-lg shadow'
$card->configurations()->has(Style::class);             // true
$card->configurations()->ofClass(Style::class);         // list<Style>
```

Configuration nodes never appear in `children()`, child-count rules, trees or
traversal. They are classified by interface, never by namespace or naming, and
normalized as `['class' => ..., 'data' => $node->normalize()]`.

Every catalog and layout component accepts any configuration node. A custom
schema restricts them with `withAllowedConfigurations([Style::class])` or
forbids them all with `withAllowedConfigurations([])`. A node that is not
allowed is refused by `make()` (`... does not accept the Style configuration
node.`) and reported by `validate()` as `InvalidConfiguration`.

### Placement configuration

`Contracts\PlacementConfiguration` extends `ConfigurationNode` for nodes that
tell a component's parent how to place it, such as a grid span. Every component
accepts placement configuration, whatever its `withAllowedConfigurations()`
list, because it belongs to the parent's layout. Components never read it;
their parents (or renderers) do.

## Columns and responsive values

Components with the `Concerns\HasColumns` concern (`Schema`, `Section`, `Group`,
`Card`, `Grid`, `Panel`, `Fieldset`, `Tab`, `WizardStep` and `Form`) accept
`Columns`. Components with
`Concerns\CanSpanColumns` (every `SchemaComponent`) accept `ColumnSpan` and
`ColumnStart`:

```php
use Gcego\Components\Definitions\{ColumnSpan, ColumnStart, Columns};

Grid::make(Columns::make(['default' => 1, 'lg' => 3]), Children::make([
    Text::make(Name::make('wide'), ColumnSpan::make(['default' => 1, 'lg' => 2])),
    Text::make(Name::make('full'), ColumnSpan::full()),
    Text::make(Name::make('third'), ColumnStart::make(3)),
]));
```

| Definition | Property | Value |
| --- | --- | --- |
| `Columns::make(int\|array)` | `columns` | positive integer |
| `ColumnSpan::make(int\|string\|array)`, `ColumnSpan::full()` | `columnSpan` | positive integer or `'full'` |
| `ColumnStart::make(int\|array)` | `columnStart` | positive integer |

Each value may be given per breakpoint as a non-empty array keyed by
`Support\Responsive::Breakpoints`: `default`, `sm`, `md`, `lg`, `xl`, `2xl`.
Other keys throw `InvalidDefinitionException`:

```text
Gcego\Components\Definitions\Columns expects positive integer, or an array of them keyed by default, sm, md, lg, xl, 2xl, array(1) given.
```

The package stores and validates these values; it does not compute a layout.

## Paths

Every occurrence in a tree gets a deterministic `Trees\ComponentPath`. Each
segment is:

- the component's name, if it matches `[A-Za-z0-9_-]+` and no earlier sibling
  uses it;
- `name#index`, if an earlier sibling already uses that name;
- `#index`, if the name is missing or is not a simple identifier.

Segments are joined with `.`. In the layout above the unnamed schema root is
`#0` and the paths are `#0.dashboard`, `#0.dashboard.metrics` and
`#0.dashboard.metrics.statistics`. Name the root for readable paths.

`Name` accepts dotted names such as `billing.first_name`, but a dotted name is
not a simple segment, so that occurrence's path segment is `#index`.

`ComponentPath` offers `root()`, `fromSegments()`, `fromString()`, `child()`,
`parent()`, `segments()`, `last()`, `depth()` (root is 0), `isAncestorOf()`,
`equals()`, `toString()` and `__toString()`.

## `ComponentTree`

```php
$tree = $layout->tree();                         // or Components::tree($layout)

$tree->root();                                   // the root component
$tree->rootNode();                               // TreeNode of the root
$tree->count();                                  // 5 nodes, root included
$tree->descendantCount();                        // 4
$tree->descendantCount('#0.dashboard');          // 3
$tree->depth();                                  // 3 (a lone root has depth 0)
$tree->find('#0.dashboard.metrics.activity');    // ?TreeNode
$tree->contains('#0.dashboard.metrics');         // bool
$tree->findById('main-table');                   // first occurrence with that explicit id
$tree->findByType('item');                       // by type identifier...
$tree->findByType(Item::class);                  // ...or by class / interface
$tree->parentOf('#0.dashboard.metrics.activity'); // ?TreeNode
$tree->childrenOf('#0.dashboard.metrics');       // list<TreeNode>
$tree->depthFirst();                             // list<TreeNode>, pre-order
$tree->breadthFirst();                           // list<TreeNode>
$tree->duplicateIds();                           // ['dup' => ['root.s', 'root.s#1']]
foreach ($tree as $node) { /* pre-order */ }
```

The node index is built lazily, once, the first time it is needed. Path
arguments accept a string, a `ComponentPath` or (for `parentOf()`,
`childrenOf()` and `descendantCount()`) a `TreeNode`. A `TreeNode` is resolved
against the tree's own index and component identity, so a node from another
tree gives the same null, empty or zero result as a missing path.

A `TreeNode` exposes `component`, `path`, `depth`, `index` (its position among
its siblings), `parentPath`, `isRoot()` and `identity()` (see
[components](components.md#identity-and-equality)).

## Reuse

Components are immutable and hold no parent pointers, so one instance may
appear at several positions:

```php
$shared = Text::make(Name::make('shared'), Content::make('Shared'));
$root = Stack::make(Name::make('root'), Children::make([$shared, $shared]));

$root->tree()->find('root.shared');   // first occurrence
$root->tree()->find('root.shared#1'); // second occurrence
```

A reused instance that carries an explicit id is refused as soon as the parent
holding both occurrences is made
(`... Stack contains id "save" more than once. Ids must be unique within a tree; give each copy its own Id::make().`).
`validate()` reports it as `DuplicateId` at the second occurrence in trees no
`make()` checked, such as engine trees.

## `ComponentWalker`

```php
use Gcego\Components\Traversal\{ComponentWalker, TraversalOrder, Visit};
use Gcego\Components\Trees\TreeNode;

$walker = Components::walker(); // or app(ComponentWalker::class)

foreach ($walker->preOrder($root) as $node) {}
foreach ($walker->postOrder($root) as $node) {}
foreach ($walker->breadthFirst($root) as $node) {}
foreach ($walker->traverse($root, TraversalOrder::BreadthFirst) as $node) {}
foreach ($walker->ofType($root, Text::class) as $node) {}

$walker->walk($root, function (TreeNode $node): ?Visit {
    if ($node->component instanceof Text) {
        return Visit::SkipChildren;   // ignored in post-order
    }

    return null;                      // continue; return Visit::Stop to end early
});

$walker->rootNode($root);             // TreeNode for the root
$walker->childNodes($node);           // list<TreeNode>, with paths
```

`preOrder()` and `breadthFirst()` also accept `Visit::SkipChildren` or
`Visit::Stop` sent into the generator with `send()`. All traversals are lazy
generators, so breaking out of a `foreach` stops the walk. They are iterative,
so deep trees cannot overflow the call stack.

Traversal throws `InvalidChildException` when a component appears among its own
ancestors (possible only with a custom `children()` implementation) or when
nesting exceeds the walker's maximum depth:

```text
Component nesting exceeds the maximum depth of 256 at "<path>".
Circular component reference detected at "<path>": <class> contains itself as a descendant.
```

The provider registers the walker as a singleton with
`ComponentWalker::DefaultMaxDepth` (256). Trees, validation, normalization and
inspection all use it. To change the limit, bind your own instance:

```php
$this->app->singleton(ComponentWalker::class, fn (): ComponentWalker => new ComponentWalker(maxDepth: 512));
```

## Engine builder transformations

Framework packages that build engine component families with
`Building\ComponentBuilder` (declarative components have no public builder; use
`withDefinitions()` or [patching](patching.md)) have these child operations:

| Method | Effect |
| --- | --- |
| `schema()`, `components()`, `child()` | append children (component, builder or iterable; nulls skipped, lists flattened) |
| `prependChildren()` | put new children first |
| `replaceChildren()` | replace the list |
| `clearChildren()` | remove all children |
| `filterChildren(callable(child, int): bool)` | keep matching unbuilt children |
| `mapChildren(callable(child, int): ?child)` | replace each unbuilt child; `null` omits it |
| `configure(ConfigurationNode ...)` | append configuration nodes |
| `replaceConfigurations(...)`, `clearConfigurations()` | replace or remove them |

Child-only entry points reject properties and configuration nodes; use
`set()`/`with()` and `configure()` for those. One classifier owns flattening,
contract priority, null skipping and the 32-level list nesting limit. All
operations return new builders. See the
[builder API](api-reference.md#framework-engine).
