<?php

declare(strict_types=1);

use Gcego\Components\Component;
use Gcego\Components\Definitions\Children;
use Gcego\Components\Definitions\Label;
use Gcego\Components\Definitions\Name;
use Gcego\Components\Exceptions\InvalidBuilderException;
use Gcego\Components\Exceptions\InvalidDefinitionException;
use Gcego\Components\Facades\Components;
use Gcego\Components\Layout\Group;
use Gcego\Components\Layout\Item;
use Gcego\Components\Layout\Schema;
use Gcego\Components\Layout\Section;
use Gcego\Components\Properties\Property;
use Gcego\Components\Schema\ComponentDefinition;
use Gcego\Components\Schema\PropertyDefinition;
use Gcego\Components\Schema\Type;
use Gcego\Components\Tests\Fixtures\Collapsible;
use Gcego\Components\Tests\Fixtures\DashboardSection;
use Gcego\Components\Tests\Fixtures\ExampleComponent;
use Gcego\Components\Tests\Fixtures\Style;

it('builds the documented layout and normalizes its hierarchy', function (): void {
    $schema = Schema::make(Children::make([
        Section::make(Name::make('dashboard'), Label::make('Dashboard'), Children::make([
            Group::make(Name::make('metrics'), Children::make([
                Item::make(Name::make('statistics')),
                Item::make(Name::make('activity')),
            ])),
        ])),
    ]))->assertValid();

    $output = $schema->normalize();
    expect($output['type'])->toBe('schema');
    $section = $schema->children()->first();
    expect($section?->type())->toBe('section')
        ->and($section?->get(Component::Label))->toBe('Dashboard');
    $group = $section?->children()->first();
    expect($group?->type())->toBe('group')
        ->and($group?->children()->first()?->type())->toBe('item')
        ->and($output['children'][0]['children'][0]['children'][1]['properties']['name'])->toBe('activity');
});

it('collects dynamic components and skips conditional nulls without mutating the original', function (): void {
    $group = Group::make(Name::make('metrics'));
    $items = Component::collect((function (): Generator {
        yield Item::make(Name::make('statistics'));
        yield null;
        yield Item::make(Name::make('activity'));
    })());

    $schema = Schema::make(Children::make([
        $group->withDefinitions(Children::make([...$items->all(), Item::make(Name::make('extra'))])),
    ]));
    expect($items)->toHaveCount(2)
        ->and($group->childCount())->toBe(0)
        ->and($schema->children()->first()?->childCount())->toBe(3)
        ->and(Schema::make()->assertValid()->childCount())->toBe(0);
});

it('rejects empty names and children on leaf items', function (): void {
    expect(fn () => Section::make(Name::make('')))->toThrow(InvalidDefinitionException::class)
        ->and(fn () => Item::make(Name::make('statistics'), Children::make([Item::make(Name::make('nested'))])))
        ->toThrow(InvalidDefinitionException::class, 'does not accept');
});

it('rejects non-child values in child lists', function (string $entry, string $kind): void {
    $value = match ($kind) {
        'property' => new Property(Component::Label, 'injected'),
        'configuration' => new Style('hidden'),
        'scalar' => 'invalid',
    };

    expect(fn () => match ($entry) {
        'collect' => Component::collect([[$value]]),
        'children' => Children::make([$value]),
    })->toThrow(match ($entry) {
        'collect' => InvalidBuilderException::class,
        'children' => InvalidDefinitionException::class,
    });
})->with(['collect', 'children'])->with(['property', 'configuration', 'scalar']);

it('keeps engine components and all runtime services usable inside layouts', function (): void {
    $custom = ExampleComponent::define('custom')->label('Custom');
    $schema = Schema::make(Children::make([Section::make(Name::make('dashboard'), Children::make([$custom]))]))->assertValid();

    expect($schema)->toBeInstanceOf(Schema::class)
        ->and($schema->children()->first()?->children()->first())->toBeInstanceOf(ExampleComponent::class)
        ->and(Components::normalize($schema))->toBe($schema->normalize())
        ->and(Components::tree($schema)->root())->toBe($schema)
        ->and(Components::inspect($schema)->toArray())->toBeArray();
});

it('preserves custom label mapping and schema validation for engine components', function (): void {
    $component = new class extends Component
    {
        public const string Label = 'caption';

        protected static function defineSchema(): ComponentDefinition
        {
            return parent::defineSchema()->withProperties(new PropertyDefinition(self::Label, Type::string()));
        }
    };

    $built = $component::define()->label('Caption')->build()->assertValid();
    expect($built->get('caption'))->toBe('Caption');

    $withoutLabel = new class extends Component
    {
        protected static function defineSchema(): ComponentDefinition
        {
            return parent::defineSchema()->withoutProperties(self::Label);
        }
    };
    expect(fn () => $withoutLabel::define()->label('Label'))->toThrow(InvalidBuilderException::class);
});

it('bounds recursive child lists at the layout boundary', function (): void {
    $children = [];
    $children[] = &$children;

    expect(fn () => Component::collect($children))->toThrow(InvalidBuilderException::class, 'maximum nesting depth');
});

it('supports layout extensions with inherited naming and custom definitions', function (): void {
    $section = DashboardSection::make(Name::make('dashboard'), Collapsible::make(), Children::make([
        Group::make(Name::make('metrics'), Children::make([Item::make(Name::make('statistics'))])),
    ]));
    $schema = Schema::make(Children::make([$section]))->assertValid();
    $built = $schema->children()->first();

    expect($built)->toBeInstanceOf(DashboardSection::class)
        ->and($built->type())->toBe('dashboard.section')
        ->and($built->name())->toBe('dashboard')
        ->and($built->get('collapsible'))->toBeTrue()
        ->and(DashboardSection::make()->get('collapsible'))->toBeFalse();
});
