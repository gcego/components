<?php

declare(strict_types=1);

namespace Gcego\Components\Catalog;

use Gcego\Components\Component;
use Gcego\Components\Components\Actions\Button;
use Gcego\Components\Components\Actions\ButtonGroup;
use Gcego\Components\Components\Actions\Dropdown;
use Gcego\Components\Components\Actions\DropdownItem;
use Gcego\Components\Components\Actions\IconButton;
use Gcego\Components\Components\Actions\SplitButton;
use Gcego\Components\Components\Display\Avatar;
use Gcego\Components\Components\Display\Badge;
use Gcego\Components\Components\Display\CodeBlock;
use Gcego\Components\Components\Display\DescriptionList;
use Gcego\Components\Components\Display\Heading;
use Gcego\Components\Components\Display\Icon;
use Gcego\Components\Components\Display\KeyValueList;
use Gcego\Components\Components\Display\ListItem;
use Gcego\Components\Components\Display\ListView;
use Gcego\Components\Components\Display\Progress;
use Gcego\Components\Components\Display\Separator;
use Gcego\Components\Components\Display\Stat;
use Gcego\Components\Components\Display\Table;
use Gcego\Components\Components\Display\TableColumn;
use Gcego\Components\Components\Display\Text;
use Gcego\Components\Components\Display\Timeline;
use Gcego\Components\Components\Display\TimelineItem;
use Gcego\Components\Components\Feedback\Alert;
use Gcego\Components\Components\Feedback\Banner;
use Gcego\Components\Components\Feedback\EmptyState;
use Gcego\Components\Components\Feedback\Skeleton;
use Gcego\Components\Components\Feedback\Spinner;
use Gcego\Components\Components\Feedback\Toast;
use Gcego\Components\Components\Inputs\Checkbox;
use Gcego\Components\Components\Inputs\CodeEditor;
use Gcego\Components\Components\Inputs\ColorPicker;
use Gcego\Components\Components\Inputs\DatePicker;
use Gcego\Components\Components\Inputs\DateTimePicker;
use Gcego\Components\Components\Inputs\EmailInput;
use Gcego\Components\Components\Inputs\FileUpload;
use Gcego\Components\Components\Inputs\Form;
use Gcego\Components\Components\Inputs\HiddenInput;
use Gcego\Components\Components\Inputs\MultiSelect;
use Gcego\Components\Components\Inputs\NumberInput;
use Gcego\Components\Components\Inputs\PasswordInput;
use Gcego\Components\Components\Inputs\Radio;
use Gcego\Components\Components\Inputs\RangeInput;
use Gcego\Components\Components\Inputs\RichEditor;
use Gcego\Components\Components\Inputs\SearchInput;
use Gcego\Components\Components\Inputs\Select;
use Gcego\Components\Components\Inputs\TelInput;
use Gcego\Components\Components\Inputs\Textarea;
use Gcego\Components\Components\Inputs\TextInput;
use Gcego\Components\Components\Inputs\TimePicker;
use Gcego\Components\Components\Inputs\Toggle;
use Gcego\Components\Components\Inputs\UrlInput;
use Gcego\Components\Components\Layout\Accordion;
use Gcego\Components\Components\Layout\AccordionItem;
use Gcego\Components\Components\Layout\Card;
use Gcego\Components\Components\Layout\Column;
use Gcego\Components\Components\Layout\Container;
use Gcego\Components\Components\Layout\Fieldset;
use Gcego\Components\Components\Layout\Grid;
use Gcego\Components\Components\Layout\Panel;
use Gcego\Components\Components\Layout\Row;
use Gcego\Components\Components\Layout\Spacer;
use Gcego\Components\Components\Layout\Splitter;
use Gcego\Components\Components\Layout\Stack;
use Gcego\Components\Components\Layout\Tab;
use Gcego\Components\Components\Layout\Tabs;
use Gcego\Components\Components\Layout\Wizard;
use Gcego\Components\Components\Layout\WizardStep;
use Gcego\Components\Components\Media\Audio;
use Gcego\Components\Components\Media\Calendar;
use Gcego\Components\Components\Media\Chart;
use Gcego\Components\Components\Media\Embed;
use Gcego\Components\Components\Media\Image;
use Gcego\Components\Components\Media\Map;
use Gcego\Components\Components\Media\Video;
use Gcego\Components\Components\Navigation\Breadcrumb;
use Gcego\Components\Components\Navigation\Breadcrumbs;
use Gcego\Components\Components\Navigation\Link;
use Gcego\Components\Components\Navigation\Menu;
use Gcego\Components\Components\Navigation\MenuItem;
use Gcego\Components\Components\Navigation\Pagination;
use Gcego\Components\Components\Overlays\Drawer;
use Gcego\Components\Components\Overlays\Modal;
use Gcego\Components\Components\Overlays\Popover;
use Gcego\Components\Components\Overlays\Tooltip;
use Gcego\Components\Exceptions\InvalidConfigurationException;
use Gcego\Components\Facades\Components;
use Gcego\Components\Layout\Group;
use Gcego\Components\Layout\Item;
use Gcego\Components\Layout\Section;
use ReflectionClass;

/** Provider-scoped registry of trusted, named component factories. */
final class ComponentCatalog
{
    /** @var array<string, class-string<Component>> */
    private array $components = [
        'section' => Section::class,
        'group' => Group::class,
        'item' => Item::class,
        'container' => Container::class,
        'grid' => Grid::class,
        'stack' => Stack::class,
        'spacer' => Spacer::class,
        'row' => Row::class,
        'column' => Column::class,
        'card' => Card::class,
        'panel' => Panel::class,
        'tabs' => Tabs::class,
        'tab' => Tab::class,
        'accordion' => Accordion::class,
        'accordionItem' => AccordionItem::class,
        'fieldset' => Fieldset::class,
        'modal' => Modal::class,
        'drawer' => Drawer::class,
        'splitter' => Splitter::class,
        'wizard' => Wizard::class,
        'wizardStep' => WizardStep::class,
        'form' => Form::class,
        'textInput' => TextInput::class,
        'textarea' => Textarea::class,
        'numberInput' => NumberInput::class,
        'emailInput' => EmailInput::class,
        'passwordInput' => PasswordInput::class,
        'searchInput' => SearchInput::class,
        'urlInput' => UrlInput::class,
        'telInput' => TelInput::class,
        'select' => Select::class,
        'multiSelect' => MultiSelect::class,
        'checkbox' => Checkbox::class,
        'radio' => Radio::class,
        'toggle' => Toggle::class,
        'datePicker' => DatePicker::class,
        'dateTimePicker' => DateTimePicker::class,
        'timePicker' => TimePicker::class,
        'fileUpload' => FileUpload::class,
        'hiddenInput' => HiddenInput::class,
        'colorPicker' => ColorPicker::class,
        'rangeInput' => RangeInput::class,
        'richEditor' => RichEditor::class,
        'codeEditor' => CodeEditor::class,
        'table' => Table::class,
        'tableColumn' => TableColumn::class,
        'text' => Text::class,
        'heading' => Heading::class,
        'badge' => Badge::class,
        'avatar' => Avatar::class,
        'icon' => Icon::class,
        'separator' => Separator::class,
        'stat' => Stat::class,
        'keyValueList' => KeyValueList::class,
        'descriptionList' => DescriptionList::class,
        'listView' => ListView::class,
        'listItem' => ListItem::class,
        'progress' => Progress::class,
        'timeline' => Timeline::class,
        'timelineItem' => TimelineItem::class,
        'codeBlock' => CodeBlock::class,
        'menu' => Menu::class,
        'menuItem' => MenuItem::class,
        'breadcrumbs' => Breadcrumbs::class,
        'breadcrumb' => Breadcrumb::class,
        'pagination' => Pagination::class,
        'link' => Link::class,
        'alert' => Alert::class,
        'toast' => Toast::class,
        'banner' => Banner::class,
        'emptyState' => EmptyState::class,
        'skeleton' => Skeleton::class,
        'spinner' => Spinner::class,
        'tooltip' => Tooltip::class,
        'popover' => Popover::class,
        'button' => Button::class,
        'iconButton' => IconButton::class,
        'splitButton' => SplitButton::class,
        'buttonGroup' => ButtonGroup::class,
        'dropdown' => Dropdown::class,
        'dropdownItem' => DropdownItem::class,
        'image' => Image::class,
        'video' => Video::class,
        'audio' => Audio::class,
        'embed' => Embed::class,
        'map' => Map::class,
        'chart' => Chart::class,
        'calendar' => Calendar::class,
    ];

    public function __construct()
    {
        Components::getFacadeRoot();
    }

    /** @param class-string<Component> $component */
    public function register(string $alias, string $component): void
    {
        if (preg_match('/\A[a-z][a-zA-Z0-9]*\z/', $alias) !== 1 || method_exists(Component::class, $alias)) {
            throw InvalidConfigurationException::invalidDefinition('Catalog aliases must be camelCase identifiers without a reserved Component method name.');
        }

        foreach ($this->components as $registered => $class) {
            if (strcasecmp($registered, $alias) === 0) {
                throw InvalidConfigurationException::invalidDefinition('A component with this catalog alias is already registered.');
            }
        }

        if (! is_a($component, Component::class, true) || ! (new ReflectionClass($component))->isInstantiable()) {
            throw InvalidConfigurationException::invalidDefinition('Catalog entries must be concrete component classes with public constructors.');
        }

        $this->components[$alias] = $component;
    }

    /** @return class-string<Component> */
    public function resolve(string $alias): string
    {
        return $this->components[$alias] ?? throw InvalidConfigurationException::invalidDefinition(sprintf('Unknown component catalog alias "%s".', $alias));
    }

    /** @return array<string, class-string<Component>> */
    public function all(): array
    {
        return $this->components;
    }
}
