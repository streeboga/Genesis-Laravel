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

### Через фасад

```php
use Streeboga\\GenesisLaravel\\Facades\\Genesis;

// Биллинг
$response = Genesis::billing()->listPlans($projectId);

// Стандартная авторизация
$tokens = Genesis::auth()->login(['email' => 'user@example.com', 'password' => 'password']);

// User Auth API - авторизация для checkout
$session = Genesis::auth()->authenticateByEmail([
    'email' => 'user@example.com',
    'project_uuid' => config('genesis.project_uuid'),
    'plan_uuid' => 'plan-uuid'
]);

$paymentUrl = Genesis::auth()->getPaymentUrl($session['data']['session_token'], 'plan-uuid');
```

### Через DI

```php
use Streeboga\\Genesis\\GenesisClient;

public function __construct(private GenesisClient $genesis) {}

public function createCheckoutSession(string $email, string $planUuid) 
{
    $session = $this->genesis->auth->authenticateByEmail([
        'email' => $email,
        'project_uuid' => config('genesis.project_uuid'),
        'plan_uuid' => $planUuid
    ]);
    
    return $this->genesis->auth->getPaymentUrl(
        $session['data']['session_token'], 
        $planUuid
    );
}
```

## Публикация

Пакет публикует конфиг config/genesis.php.






