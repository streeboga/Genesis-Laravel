# Genesis Laravel Package

Laravel-пакет, подключающий SDK `streeboga/genesis` и предоставляющий интеграцию через сервис-провайдер, конфиг и фасад.

## Установка

```
composer require streeboga/genesis-laravel
```

(В монорепозитории пакет подключён как path-репозиторий.)

## Конфигурация

Опубликуйте и измените конфиг при необходимости:

```
php artisan vendor:publish --tag=config --provider="Streeboga\\GenesisLaravel\\GenesisServiceProvider"
```

Переменные окружения:

- GENESIS_API_KEY
- GENESIS_BASE_URL (по умолчанию https://api.genesis.com/v1/)

## Использование

Через фасад:

```
use Streeboga\\GenesisLaravel\\Facades\\Genesis;

$response = Genesis::billing()->listPlans($projectId);
```

Или через DI:

```
use Streeboga\\Genesis\\GenesisClient;

public function __construct(private GenesisClient $genesis) {}
```

## Публикация

Пакет публикует конфиг config/genesis.php.


