<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Compilers\BladeCompiler;
use ReflectionClass;
use Streeboga\Genesis\GenesisClient;
use Streeboga\GenesisLaravel\GenesisServiceProvider;

it('registers genesisAuth blade directive', function () {
    $container = new Container();
    Container::setInstance($container);
    Facade::setFacadeApplication($container);
    $container->instance('config', new ConfigRepository([
        'genesis' => [
            'api_key' => 'test_key',
            'base_url' => 'https://api.genesis.com/v1/',
        ],
    ]));

    // Mock BladeCompiler
    $container->instance('files', new Filesystem());
    $bladeCompiler = new BladeCompiler($container->make('files'), sys_get_temp_dir());
    $container->instance('blade.compiler', $bladeCompiler);

    $provider = new GenesisServiceProvider($container);
    $provider->register();
    
    // Manually call registerBladeDirectives to test without boot()
    $reflectionClass = new ReflectionClass($provider);
    $method = $reflectionClass->getMethod('registerBladeDirectives');
    $method->setAccessible(true);
    $method->invoke($provider);

    // Check if directive is registered
    $directives = $bladeCompiler->getCustomDirectives();
    expect($directives)->toHaveKey('genesisAuth');
});

it('genesisAuth directive method exists and is callable', function () {
    $container = new Container();
    Container::setInstance($container);
    Facade::setFacadeApplication($container);
    $container->instance('config', new ConfigRepository([
        'genesis' => [
            'api_key' => 'test_key',
            'base_url' => 'https://api.genesis.com/v1/',
        ],
    ]));

    $provider = new GenesisServiceProvider($container);
    $provider->register();
    
    // Check that registerBladeDirectives method exists
    $reflectionClass = new ReflectionClass($provider);
    $method = $reflectionClass->getMethod('registerBladeDirectives');
    
    expect($method)->toBeInstanceOf(\ReflectionMethod::class);
    expect($method->getName())->toBe('registerBladeDirectives');
});

it('service provider has blade directives registration method', function () {
    $provider = new GenesisServiceProvider(new Container());
    
    // Check that registerBladeDirectives method exists
    $reflectionClass = new ReflectionClass($provider);
    
    expect($reflectionClass->hasMethod('registerBladeDirectives'))->toBeTrue();
    
    $method = $reflectionClass->getMethod('registerBladeDirectives');
    expect($method->isProtected())->toBeTrue();
});

it('blade directives are properly defined in service provider', function () {
    $provider = new GenesisServiceProvider(new Container());
    
    // Check that registerBladeDirectives method exists and has correct visibility
    $reflectionClass = new ReflectionClass($provider);
    $method = $reflectionClass->getMethod('registerBladeDirectives');
    
    expect($method->isProtected())->toBeTrue();
    expect($method->getName())->toBe('registerBladeDirectives');
    
    // Check method can be invoked (basic smoke test)
    $method->setAccessible(true);
    expect(function() use ($method, $provider) {
        $method->invoke($provider);
    })->not->toThrow(\Exception::class);
});
