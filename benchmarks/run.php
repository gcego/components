<?php

declare(strict_types=1);

/**
 * Repeatable micro-benchmark for construction, lookup, validation, traversal
 * and normalization of a page-sized component tree, plus live resolution and
 * updates of a reactive form of the same size.
 *
 * Usage: composer benchmark  (or: php benchmarks/run.php [sections] [fieldsPerSection] [iterations])
 */

use Gcego\Components\Component;
use Gcego\Components\Components\Actions\Button;
use Gcego\Components\Components\Display\Heading;
use Gcego\Components\Components\Inputs\TextInput;
use Gcego\Components\Components\Layout\Card;
use Gcego\Components\ComponentServiceProvider;
use Gcego\Components\Definitions\Children;
use Gcego\Components\Definitions\Content;
use Gcego\Components\Definitions\Disabled;
use Gcego\Components\Definitions\Icon;
use Gcego\Components\Definitions\Label;
use Gcego\Components\Definitions\Name;
use Gcego\Components\Definitions\Placeholder;
use Gcego\Components\Definitions\Required;
use Gcego\Components\Definitions\Size;
use Gcego\Components\Definitions\Title;
use Gcego\Components\Definitions\Variant;
use Gcego\Components\Definitions\VisibleWhen;
use Gcego\Components\Evaluation\Get;
use Gcego\Components\Facades\Components;
use Gcego\Components\Layout\Schema;
use Gcego\Components\Layout\Section;
use Gcego\Components\Properties\Property;
use Gcego\Components\Tests\Fixtures\ExampleComponent;
use Gcego\Components\Tests\Fixtures\FakeModel;
use Gcego\Components\Tests\Fixtures\ParentComponent;
use Gcego\Components\Traversal\ComponentWalker;
use Gcego\Components\Validation\ComponentValidator;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;

require __DIR__.'/../vendor/autoload.php';

$app = new Application(dirname(__DIR__));
Facade::setFacadeApplication($app);
$app->register(ComponentServiceProvider::class);

$sections = max(1, (int) ($argv[1] ?? 50));
$fieldsPerSection = max(1, min(3, (int) ($argv[2] ?? 3)));
$iterations = max(1, (int) ($argv[3] ?? 20));

$build = static function () use ($sections, $fieldsPerSection): Component {
    $children = [];

    for ($section = 0; $section < $sections; $section++) {
        $fields = [];

        for ($field = 0; $field < $fieldsPerSection; $field++) {
            $fields[] = new ExampleComponent('field'.$field, 'Field '.$field, new Property(ExampleComponent::Searchable, true));
        }

        $children[] = new ParentComponent(FakeModel::class, new Property(Component::Name, 'section'.$section), ...$fields);
    }

    return new Component(new Property(Component::Name, 'page'), ...$children);
};

/**
 * @param  Closure(): mixed  $operation
 */
$measure = static function (string $label, Closure $operation) use ($iterations): void {
    $operation();
    $start = hrtime(true);

    for ($iteration = 0; $iteration < $iterations; $iteration++) {
        $operation();
    }

    printf("%-14s %9.3f ms/op\n", $label, (hrtime(true) - $start) / 1e6 / $iterations);
};

$declare = static function () use ($sections): Card {
    $cards = [];

    for ($section = 0; $section < $sections; $section++) {
        $cards[] = Card::make(
            Name::make('card'.$section),
            Title::make('Section '.$section),
            Children::make([
                Heading::make(Content::make('Section '.$section)),
                TextInput::make(Name::make('email'.$section), Label::make('Email'), Placeholder::make('you@example.com'), Required::make()),
                Button::make(Label::make('Save'), Variant::make('primary'), Size::make('medium'), Icon::make('save'), Disabled::make(false)),
            ]),
        );
    }

    return Card::make(Name::make('page'), Children::make($cards));
};

$tree = $build();
$nodeCount = $tree->tree()->count();
$walker = Container::getInstance()->make(ComponentWalker::class);
$validator = Container::getInstance()->make(ComponentValidator::class);

printf("Tree: %d components, %d iterations, PHP %s\n\n", $nodeCount, $iterations, PHP_VERSION);

$measure('construct', $build);
$measure('declare', $declare);
$measure('lookup', static function () use ($tree): void {
    foreach ($tree->children() as $section) {
        foreach ($section->children() as $field) {
            $field->get(ExampleComponent::Searchable);
            $field->get(ExampleComponent::PerPage);
        }
    }
});
$measure('traverse', static function () use ($walker, $tree): void {
    foreach ($walker->preOrder($tree) as $node) {
        $node->path->toString();
    }
});
$measure('validate', static fn () => $validator->validate($tree));
$measure('normalize', static fn () => $tree->normalize());

$form = Schema::make(Children::make(array_map(
    static fn (int $section): Section => Section::make(Name::make('section'.$section), Children::make([
        TextInput::make(Name::make('mode'.$section)),
        TextInput::make(Name::make('title'.$section), VisibleWhen::make(fn (Get $get): bool => $get('mode'.$section) !== 'off')),
        TextInput::make(Name::make('notes'.$section), Placeholder::using(fn (Get $get): string => 'About '.$get->string('title'.$section))),
    ])),
    range(0, $sections - 1),
)));
$live = Components::live($form);

$measure('live:create', static fn () => Components::live($form));
$measure('live:set', static fn () => $live->set('mode0', $live->state()->get('mode0') === 'off' ? 'on' : 'off'));

printf("\nPeak memory: %.1f MB\n", memory_get_peak_usage(true) / 1048576);
