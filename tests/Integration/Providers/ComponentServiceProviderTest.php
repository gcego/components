<?php

declare(strict_types=1);

use Gcego\Components\Building\ComponentBuilder;
use Gcego\Components\Catalog\ComponentCatalog;
use Gcego\Components\Component;
use Gcego\Components\ComponentManager;
use Gcego\Components\ComponentServiceProvider;
use Gcego\Components\Configuration\ComponentConfigurator;
use Gcego\Components\Evaluation\Evaluator;
use Gcego\Components\Exceptions\InvalidChildException;
use Gcego\Components\Exceptions\InvalidConfigurationException;
use Gcego\Components\Facades\Components;
use Gcego\Components\Inspection\ComponentInspector;
use Gcego\Components\Normalization\ComponentNormalizer;
use Gcego\Components\Properties\Property;
use Gcego\Components\Tests\Fixtures\ConstructorComponent;
use Gcego\Components\Tests\Fixtures\CyclicComponent;
use Gcego\Components\Traversal\ComponentWalker;
use Gcego\Components\Trees\ComponentTree;
use Gcego\Components\Validation\ChildValidator;
use Gcego\Components\Validation\ComponentValidator;
use Gcego\Components\Validation\PropertyValidator;
use Gcego\Core\Contracts\PackageInterface;
use Gcego\Core\Gcego;
use Gcego\Core\GcegoServiceProvider;
use Gcego\Core\Packages\PackageManager;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\Facades\Facade;

it('registers the scoped manager, shared runtime services and transient factories', function (): void {
    $container = Container::getInstance();
    $provider = new ComponentServiceProvider($container);
    $shared = [
        ComponentManager::class, ComponentWalker::class, PropertyValidator::class,
        ChildValidator::class, ComponentValidator::class, ComponentNormalizer::class, ComponentInspector::class,
        ComponentConfigurator::class, Evaluator::class,
    ];

    expect($provider)->toBeInstanceOf(PackageInterface::class)
        ->and($provider->isDeferred())->toBeFalse()
        ->and($provider->provides())->toBe([...$shared, ComponentBuilder::class, ComponentTree::class, ComponentCatalog::class]);

    foreach ($shared as $service) {
        expect($container->bound($service))->toBeTrue()
            ->and($container->make($service))->toBeInstanceOf($service)
            ->and($container->make($service))->toBe($container->make($service));
    }

    $manager = Components::getFacadeRoot();
    $root = $manager->make(items: [new Property(Component::Name, 'root')]);

    expect($manager->define(Component::class))->not->toBe($manager->define(Component::class))
        ->and($manager->tree($root))->not->toBe($manager->tree($root))
        ->and($manager->tree($root)->root())->toBe($root)
        ->and($manager->validate($root)->isValid())->toBeTrue()
        ->and($manager->normalize($root))->toBe($root->normalize())
        ->and($manager->inspect($root)->isValid())->toBeTrue()
        ->and($manager->make(ConstructorComponent::class, ['custom'])->name())->toBe('custom');
});

it('uses provider configuration in component convenience methods and trees', function (): void {
    $container = Container::getInstance();
    $container->instance(ComponentWalker::class, new ComponentWalker(maxDepth: 0));
    $root = new Component(new Component);

    expect(fn () => $root->validate())->toThrow(InvalidChildException::class)
        ->and(fn () => $root->normalize())->toThrow(InvalidChildException::class)
        ->and(fn () => $root->tree()->count())->toThrow(InvalidChildException::class)
        ->and(fn () => Components::getFacadeRoot()->inspect($root))->toThrow(InvalidChildException::class);
});

it('uses provider normalizer configuration for component output', function (): void {
    $container = Container::getInstance();
    $container->bind(ComponentNormalizer::class, static fn (Container $app): ComponentNormalizer => new ComponentNormalizer(
        $app->make(ComponentWalker::class), includeSchema: true,
    ));

    expect((new Component)->normalize())->toHaveKey('schema');
});

it('requires provider registration before component construction schema access and operations', function (): void {
    $root = new Component;
    $cached = (new Component)->assertValid();
    Component::definition();
    $container = new Application(dirname(__DIR__, 3));
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($container);

    foreach ([
        static fn () => new Component,
        static fn () => Component::define(),
        static fn () => Component::definition(),
        static fn () => Components::getFacadeRoot(),
        static fn () => $root->validate(),
        static fn () => $cached->validate(),
        static fn () => $root->normalize(),
        static fn () => $root->tree(),
        static fn () => $container->make(ComponentWalker::class),
        static fn () => $container->make(ComponentValidator::class),
        static fn () => $container->make(ComponentNormalizer::class),
        static fn () => $container->make(ComponentInspector::class),
        static fn () => new ComponentBuilder(Component::class),
    ] as $operation) {
        expect($operation)->toThrow(InvalidConfigurationException::class, 'ComponentServiceProvider must be registered');
    }

    (new ComponentServiceProvider($container))->register();

    expect((new Component)->validate()->isValid())->toBeTrue()
        ->and($root->validate()->isValid())->toBeTrue();
});

it('advertises the required service provider for Laravel package discovery', function (): void {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 3).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['extra']['laravel']['providers'])->toBe([ComponentServiceProvider::class]);
});

it('registers eagerly without building any component service', function (): void {
    $app = Container::getInstance();

    expect($app)->toBeInstanceOf(Application::class)
        ->and($app->getLoadedProviders())->toHaveKey(ComponentServiceProvider::class)
        ->and($app->getDeferredServices())->toBe([]);

    foreach ((new ComponentServiceProvider($app))->provides() as $service) {
        expect($app->getBindings())->toHaveKey($service)
            ->and($app->resolved($service))->toBeFalse();
    }

    expect((new Component)->validate()->isValid())->toBeTrue()
        ->and($app->make(ComponentManager::class))->toBe($app->make(ComponentManager::class));
});

it('declares gcego/core as its only required GCEGO package', function (): void {
    $descriptor = (new ComponentServiceProvider(Container::getInstance()))->descriptor();

    expect($descriptor->id)->toBe('gcego/components')
        ->and($descriptor->requires)->toBe(['gcego/core'])
        ->and($descriptor->optional)->toBe([])
        ->and($descriptor->hasCapability('components'))->toBeTrue();
});

it('is discovered and booted by gcego/core without any other GCEGO package', function (): void {
    $app = new Application(dirname(__DIR__, 3));
    $app->instance('config', new Repository(['gcego' => ['auto_boot' => false]]));
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($app);
    $app->register(GcegoServiceProvider::class);
    $app->register(ComponentServiceProvider::class);
    $app->boot();

    $gcego = $app->make(Gcego::class);
    $gcego->boot();
    $packages = $app->make(PackageManager::class);

    expect($packages->has('gcego/components'))->toBeTrue()
        ->and($packages->isBooted('gcego/components'))->toBeTrue()
        ->and($packages->dependencies('gcego/components', transitive: true))->toBe(['gcego/core'])
        ->and($gcego->hasCapability('components'))->toBeTrue();
});

it('preserves instances transient factories and aliases supplied by the application', function (): void {
    $container = Container::getInstance();
    // Replace the existing provider binding with an application alias.
    $container->offsetUnset(ComponentWalker::class);
    $container->bind('configured.walker', static fn (): ComponentWalker => new ComponentWalker(maxDepth: 0));
    $container->alias('configured.walker', ComponentWalker::class);
    $container->bind(ComponentNormalizer::class, static fn (Container $app): ComponentNormalizer => new ComponentNormalizer(
        $app->make(ComponentWalker::class), includeSchema: true,
    ));
    (new ComponentServiceProvider($container))->register();

    expect($container->isAlias(ComponentWalker::class))->toBeTrue()
        ->and($container->make(ComponentNormalizer::class))->not->toBe($container->make(ComponentNormalizer::class))
        ->and((new Component)->normalize())->toHaveKey('schema')
        ->and(fn () => (new Component(new Component))->validate())->toThrow(InvalidChildException::class);
});

it('keeps roots and builder state isolated across factory calls', function (): void {
    $manager = Components::getFacadeRoot();
    $first = $manager->make(items: [new Property(Component::Name, 'first')]);
    $second = $manager->make(items: [new Property(Component::Name, 'second')]);

    expect($first->name())->toBe('first')
        ->and($second->name())->toBe('second')
        ->and($first->tree()->root())->toBe($first)
        ->and($second->tree()->root())->toBe($second)
        ->and($manager->define(Component::class)->build()->hasExplicit(Component::Name))->toBeFalse();
});

it('resolves every advertised service from the registered provider', function (string $service): void {
    $app = Container::getInstance();
    $parameters = match ($service) {
        ComponentBuilder::class => ['component' => Component::class],
        ComponentTree::class => ['root' => new CyclicComponent],
        default => [],
    };

    expect($app->make($service, $parameters))->toBeInstanceOf($service);
})->with([
    ComponentManager::class, ComponentWalker::class, PropertyValidator::class,
    ChildValidator::class, ComponentValidator::class, ComponentNormalizer::class,
    ComponentInspector::class, ComponentBuilder::class, ComponentTree::class, ComponentCatalog::class,
]);

it('discovers the provider from Composer package metadata through Laravel manifests', function (): void {
    $files = new Filesystem;
    $directory = sys_get_temp_dir().'/components-discovery-'.bin2hex(random_bytes(8));
    $files->makeDirectory($directory.'/vendor/composer', 0755, true);

    try {
        $package = json_decode($files->get(dirname(__DIR__, 3).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $files->put($directory.'/vendor/composer/installed.json', json_encode([
            'packages' => [['name' => $package['name'], 'extra' => $package['extra']]],
        ], JSON_THROW_ON_ERROR));
        $app = new Application($directory);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
        $manifest = new PackageManifest($files, $directory, $directory.'/packages.php');
        $repository = new ProviderRepository($app, $files, $directory.'/services.php');
        $repository->load($manifest->providers());

        expect($manifest->providers())->toBe([ComponentServiceProvider::class])
            ->and($manifest->aliases())->toBe(['Components' => Components::class])
            ->and($app->getLoadedProviders())->toHaveKey(ComponentServiceProvider::class)
            ->and($app->getDeferredServices())->toBe([])
            ->and(Components::getFacadeRoot()->make()->validate()->isValid())->toBeTrue();
    } finally {
        $files->deleteDirectory($directory);
    }
});
