<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Facade;
use Streeboga\Genesis\GenesisClient;
use Streeboga\GenesisLaravel\GenesisServiceProvider;
use Streeboga\GenesisLaravel\Jobs\ProcessGenesisWebhook;
use Streeboga\GenesisLaravel\Jobs\SyncGenesisData;

it('genesis webhook job can be instantiated', function () {
    $job = new ProcessGenesisWebhook(['event' => 'payment.completed', 'data' => []]);
    
    expect($job)->toBeInstanceOf(ProcessGenesisWebhook::class);
});

it('genesis sync job can be instantiated', function () {
    $job = new SyncGenesisData('project_123', 'users');
    
    expect($job)->toBeInstanceOf(SyncGenesisData::class);
    expect($job->getProjectId())->toBe('project_123');
    expect($job->getDataType())->toBe('users');
});

it('genesis jobs are properly configured for queues', function () {
    $webhookJob = new ProcessGenesisWebhook(['event' => 'test']);
    $syncJob = new SyncGenesisData('project_123', 'billing');
    
    // Check that jobs implement ShouldQueue
    expect($webhookJob)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    expect($syncJob)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('genesis webhook job processes webhook data', function () {
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

    $webhookData = [
        'event' => 'payment.completed',
        'data' => ['payment_id' => 'pay_123', 'amount' => 1000]
    ];
    
    $job = new ProcessGenesisWebhook($webhookData);
    
    // Test that job can access webhook data
    expect($job->getWebhookData())->toBe($webhookData);
    expect($job->getEvent())->toBe('payment.completed');
});






