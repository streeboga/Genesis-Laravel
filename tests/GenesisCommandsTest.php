<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Streeboga\Genesis\GenesisClient;
use Streeboga\GenesisLaravel\Commands\GenesisSetupCommand;
use Streeboga\GenesisLaravel\Commands\GenesisTestConnectionCommand;
use Streeboga\GenesisLaravel\GenesisServiceProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

it('genesis:setup command exists and can be instantiated', function () {
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

    $command = new GenesisSetupCommand();
    
    expect($command)->toBeInstanceOf(GenesisSetupCommand::class);
    expect($command->getName())->toBe('genesis:setup');
});

it('genesis:test-connection command exists and can be instantiated', function () {
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

    $command = new GenesisTestConnectionCommand($container->make(GenesisClient::class));
    
    expect($command)->toBeInstanceOf(GenesisTestConnectionCommand::class);
    expect($command->getName())->toBe('genesis:test-connection');
});

it('genesis:test-connection command has correct signature and description', function () {
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

    $command = new GenesisTestConnectionCommand($container->make(GenesisClient::class));
    
    expect($command->getName())->toBe('genesis:test-connection');
    expect($command->getDescription())->toBe('Test connection to Genesis API');
});
