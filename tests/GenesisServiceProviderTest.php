<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Streeboga\Genesis\GenesisClient;
use Streeboga\GenesisLaravel\GenesisServiceProvider;

it('registers GenesisClient as singleton and can be resolved', function () {
    $container = new Container();
    Container::setInstance($container);
    Facade::setFacadeApplication($container);
    $container->instance('config', new ConfigRepository([]));

    $provider = new GenesisServiceProvider($container);
    $provider->register();

    $client = $container->make(GenesisClient::class);
    expect($client)->toBeInstanceOf(GenesisClient::class);
});

it('automatically registers genesis.auth middleware', function () {
    $container = new Container();
    Container::setInstance($container);
    Facade::setFacadeApplication($container);
    
    // Создаём mock Router с поддержкой aliasMiddleware
    $router = new class {
        public array $middlewareAliases = [];
        
        public function aliasMiddleware(string $name, string $class): void
        {
            $this->middlewareAliases[$name] = $class;
        }
    };
    
    $container->instance('router', $router);
    $container->instance('config', new ConfigRepository([]));

    $provider = new GenesisServiceProvider($container);
    $provider->register();
    $provider->boot();

    // Проверяем что genesis.auth middleware зарегистрирован автоматически
    expect($router->middlewareAliases)
        ->toHaveKey('genesis.auth')
        ->and($router->middlewareAliases['genesis.auth'])
        ->toBe(\Streeboga\GenesisLaravel\Middleware\GenesisAuthMiddleware::class);
});


