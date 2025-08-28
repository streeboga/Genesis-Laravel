<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;
use Streeboga\Genesis\GenesisClient;
use Streeboga\GenesisLaravel\GenesisServiceProvider;
use Streeboga\GenesisLaravel\Middleware\GenesisAuthMiddleware;

it('allows request with valid genesis token', function () {
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

    $middleware = new GenesisAuthMiddleware($container->make(GenesisClient::class));
    
    $request = Request::create('/test', 'GET');
    $request->headers->set('Authorization', 'Bearer valid_token');
    
    $response = $middleware->handle($request, function ($req) {
        return new Response('OK', 200);
    });

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('OK');
});

it('rejects request without authorization header', function () {
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

    $middleware = new GenesisAuthMiddleware($container->make(GenesisClient::class));
    
    $request = Request::create('/test', 'GET');
    
    $response = $middleware->handle($request, function ($req) {
        return new Response('OK', 200);
    });

    expect($response->getStatusCode())->toBe(401);
});

it('rejects request with invalid token format', function () {
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

    $middleware = new GenesisAuthMiddleware($container->make(GenesisClient::class));
    
    $request = Request::create('/test', 'GET');
    $request->headers->set('Authorization', 'InvalidFormat');
    
    $response = $middleware->handle($request, function ($req) {
        return new Response('OK', 200);
    });

    expect($response->getStatusCode())->toBe(401);
});






