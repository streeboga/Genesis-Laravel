<?php

namespace Streeboga\GenesisLaravel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Streeboga\Genesis\Config as SdkConfig;
use Streeboga\Genesis\GenesisClient;
use Streeboga\GenesisLaravel\Commands\GenesisSetupCommand;
use Streeboga\GenesisLaravel\Commands\GenesisTestConnectionCommand;
use Streeboga\GenesisLaravel\Middleware\GenesisAuthMiddleware;
use Streeboga\GenesisLaravel\Services\GenesisCacheService;

class GenesisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/genesis.php', 'genesis');

        $this->app->singleton(GenesisClient::class, function ($app) {
            $config = $app['config']->get('genesis');
            $apiKey = $config['api_key'] ?? env('GENESIS_API_KEY', '');
            $baseUrl = $config['base_url'] ?? env('GENESIS_BASE_URL', 'https://api.genesis.com/v1/');

            return new GenesisClient($apiKey, $baseUrl);
        });

        $this->app->alias(GenesisClient::class, 'genesis');

        $this->app->singleton(GenesisCacheService::class, function ($app) {
            $config = $app['config']->get('genesis.cache', []);
            return new GenesisCacheService(
                $app['cache'],
                $config['enabled'] ?? true,
                $config['ttl'] ?? 3600,
                $config['prefix'] ?? 'genesis:'
            );
        });
    }

    public function boot(): void
    {
        // Подключаем роуты пакета
        $this->loadRoutesFrom(__DIR__.'/../routes/genesis.php');

        $this->publishes([
            __DIR__.'/../config/genesis.php' => config_path('genesis.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenesisSetupCommand::class,
                GenesisTestConnectionCommand::class,
            ]);
        }

        // Автоматическая регистрация genesis.auth middleware
        $this->registerMiddleware();
        
        $this->registerBladeDirectives();
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('genesisAuth', function ($expression) {
            return "<?php if (app('Streeboga\\Genesis\\GenesisClient')->auth()->check({$expression})): ?>";
        });

        Blade::directive('endgenesisAuth', function () {
            return '<?php endif; ?>';
        });

        Blade::directive('genesisFeature', function ($expression) {
            return "<?php if (app('Streeboga\\Genesis\\GenesisClient')->features()->hasAccess({$expression})): ?>";
        });

        Blade::directive('endgenesisFeature', function () {
            return '<?php endif; ?>';
        });
    }

    /**
     * Регистрация middleware для автоматического подключения
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        
        // Регистрируем alias для middleware
        $router->aliasMiddleware('genesis.auth', GenesisAuthMiddleware::class);
    }
}


