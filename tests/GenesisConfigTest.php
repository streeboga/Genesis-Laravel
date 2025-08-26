<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Streeboga\Genesis\GenesisClient;
use Streeboga\GenesisLaravel\GenesisServiceProvider;

it('merges default config and binds client with defaults', function () {
    $container = new Container();
    Container::setInstance($container);
    Facade::setFacadeApplication($container);
    $container->instance('config', new ConfigRepository([]));

    $provider = new GenesisServiceProvider($container);
    $provider->register();

    expect(config('genesis.base_url'))
        ->toBe('https://api.genesis.com/v1/');

    $client = $container->make(GenesisClient::class);
    expect($client)->toBeInstanceOf(GenesisClient::class);
});

it('uses env-configured api key to build client', function () {
    $container = new Container();
    Container::setInstance($container);
    Facade::setFacadeApplication($container);
    $container->instance('config', new ConfigRepository([
        'genesis' => [
            'api_key' => 'test_key_123',
            'base_url' => 'https://api.genesis.com/v1/',
        ],
    ]));

    $provider = new GenesisServiceProvider($container);
    $provider->register();

    $client = $container->make(GenesisClient::class);
    expect($client->getApiKey())->toBe('test_key_123');
});


