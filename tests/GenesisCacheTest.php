<?php

declare(strict_types=1);

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Streeboga\Genesis\GenesisClient;
use Streeboga\GenesisLaravel\GenesisServiceProvider;
use Streeboga\GenesisLaravel\Services\GenesisCacheService;

it('genesis cache service can be resolved from container', function () {
    $container = new Container();
    Container::setInstance($container);
    Facade::setFacadeApplication($container);
    
    // Mock cache repository
    $cacheRepository = Mockery::mock(\Illuminate\Contracts\Cache\Repository::class);
    $container->instance('cache', $cacheRepository);
    
    $container->instance('config', new ConfigRepository([
        'genesis' => [
            'api_key' => 'test_key',
            'base_url' => 'https://api.genesis.com/v1/',
            'cache' => [
                'enabled' => true,
                'ttl' => 3600,
                'prefix' => 'genesis:',
            ],
        ],
    ]));

    $provider = new GenesisServiceProvider($container);
    $provider->register();

    $cacheService = $container->make(GenesisCacheService::class);
    expect($cacheService)->toBeInstanceOf(GenesisCacheService::class);
});

it('genesis cache service caches api responses', function () {
    $container = new Container();
    Container::setInstance($container);
    Facade::setFacadeApplication($container);
    
    // Mock cache repository
    $cacheRepository = Mockery::mock(\Illuminate\Contracts\Cache\Repository::class);
    $container->instance('cache', $cacheRepository);
    
    $container->instance('config', new ConfigRepository([
        'genesis' => [
            'api_key' => 'test_key',
            'base_url' => 'https://api.genesis.com/v1/',
            'cache' => [
                'enabled' => true,
                'ttl' => 3600,
                'prefix' => 'genesis:',
            ],
        ],
    ]));

    $provider = new GenesisServiceProvider($container);
    $provider->register();

    $cacheService = $container->make(GenesisCacheService::class);
    
    // Test caching functionality
    $key = 'test_key';
    $data = ['test' => 'data'];
    
    $cacheRepository->shouldReceive('put')
        ->with('genesis:test_key', $data, 3600)
        ->once()
        ->andReturn(true);
        
    $cacheRepository->shouldReceive('get')
        ->with('genesis:test_key')
        ->once()
        ->andReturn($data);

    $cacheService->put($key, $data);
    $result = $cacheService->get($key);
    
    expect($result)->toBe($data);
});

it('genesis cache service respects cache configuration', function () {
    $container = new Container();
    Container::setInstance($container);
    Facade::setFacadeApplication($container);
    
    // Mock cache repository
    $cacheRepository = Mockery::mock(\Illuminate\Contracts\Cache\Repository::class);
    $container->instance('cache', $cacheRepository);
    
    $container->instance('config', new ConfigRepository([
        'genesis' => [
            'api_key' => 'test_key',
            'base_url' => 'https://api.genesis.com/v1/',
            'cache' => [
                'enabled' => false,
                'ttl' => 1800,
                'prefix' => 'custom:',
            ],
        ],
    ]));

    $provider = new GenesisServiceProvider($container);
    $provider->register();

    $cacheService = $container->make(GenesisCacheService::class);
    
    expect($cacheService->isEnabled())->toBeFalse();
    expect($cacheService->getTtl())->toBe(1800);
    expect($cacheService->getPrefix())->toBe('custom:');
});
