<?php

declare(strict_types=1);

it('migration files exist and are readable', function () {
    $migrationsPath = __DIR__.'/../database/migrations';
    
    expect(file_exists($migrationsPath))->toBeTrue();
    expect(is_dir($migrationsPath))->toBeTrue();
    
    $migrationFiles = [
        '2024_01_01_000001_create_genesis_webhook_logs_table.php',
        '2024_01_01_000002_create_genesis_sync_logs_table.php',
        '2024_01_01_000003_create_genesis_api_tokens_table.php',
    ];
    
    foreach ($migrationFiles as $file) {
        $filePath = $migrationsPath . '/' . $file;
        expect(file_exists($filePath))->toBeTrue();
        expect(is_readable($filePath))->toBeTrue();
    }
});

it('webhook logs migration class can be instantiated', function () {
    $migrationPath = __DIR__.'/../database/migrations/2024_01_01_000001_create_genesis_webhook_logs_table.php';
    
    expect(file_exists($migrationPath))->toBeTrue();
    
    $migration = include $migrationPath;
    expect($migration)->toBeInstanceOf(\Illuminate\Database\Migrations\Migration::class);
});

it('sync logs migration class can be instantiated', function () {
    $migrationPath = __DIR__.'/../database/migrations/2024_01_01_000002_create_genesis_sync_logs_table.php';
    
    expect(file_exists($migrationPath))->toBeTrue();
    
    $migration = include $migrationPath;
    expect($migration)->toBeInstanceOf(\Illuminate\Database\Migrations\Migration::class);
});

it('api tokens migration class can be instantiated', function () {
    $migrationPath = __DIR__.'/../database/migrations/2024_01_01_000003_create_genesis_api_tokens_table.php';
    
    expect(file_exists($migrationPath))->toBeTrue();
    
    $migration = include $migrationPath;
    expect($migration)->toBeInstanceOf(\Illuminate\Database\Migrations\Migration::class);
});

it('migrations have up and down methods', function () {
    $migrationFiles = [
        '2024_01_01_000001_create_genesis_webhook_logs_table.php',
        '2024_01_01_000002_create_genesis_sync_logs_table.php',
        '2024_01_01_000003_create_genesis_api_tokens_table.php',
    ];
    
    foreach ($migrationFiles as $file) {
        $migrationPath = __DIR__.'/../database/migrations/' . $file;
        $migration = include $migrationPath;
        
        expect(method_exists($migration, 'up'))->toBeTrue();
        expect(method_exists($migration, 'down'))->toBeTrue();
    }
});
