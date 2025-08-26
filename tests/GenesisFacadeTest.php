<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Streeboga\Genesis\GenesisClient;
use Streeboga\GenesisLaravel\Facades\Genesis;
use Streeboga\GenesisLaravel\GenesisServiceProvider;

it('facade proxies to sdk subclients via magic call', function () {
    $container = new Container();
    Container::setInstance($container);
    Facade::setFacadeApplication($container);
    $container->instance('config', new ConfigRepository([]));

    $provider = new GenesisServiceProvider($container);
    $provider->register();

    $client = $container->make(GenesisClient::class);

    expect($client->billing)->toBeObject();
    expect(Genesis::billing())->toBe($client->billing);
});


