# Genesis Laravel Integration Guide

## 🎯 Цель
Подключить Genesis Platform SDK в Laravel проект для управления авторизацией, биллингом и функциями.

## 📋 План интеграции по шагам

### Шаг 1: Установка пакета

```bash
# Добавить в composer.json в секцию require или require-dev:
"streeboga/genesis": "@dev",
"streeboga/genesis-laravel": "@dev"

# Добавить репозитории (если используете локальную разработку):
"repositories": [
    {
        "type": "path",
        "url": "packages/streeboga/genesis"
    },
    {
        "type": "path",
        "url": "packages/streeboga/genesis-laravel"
    }
]

# Установить пакеты:
composer update streeboga/genesis streeboga/genesis-laravel -W
```

### Шаг 2: Настройка переменных окружения

Добавьте в файл `.env` вашего проекта:

```env
# Genesis API Credentials
GENESIS_API_KEY=your_actual_api_key_here
GENESIS_BASE_URL=https://api.genesis.com/v1/
GENESIS_PROJECT_UUID=your_project_uuid_here

# Cache Settings (опционально)
GENESIS_CACHE_ENABLED=true
GENESIS_CACHE_TTL=3600
GENESIS_CACHE_PREFIX=genesis:

# Queue Settings (опционально)
GENESIS_QUEUE_CONNECTION=default
GENESIS_WEBHOOK_QUEUE=genesis-webhooks
GENESIS_SYNC_QUEUE=genesis-sync
```

### Шаг 3: Публикация конфигурации

```bash
# Опубликовать конфиг файл
php artisan vendor:publish --tag=config --provider="Streeboga\GenesisLaravel\GenesisServiceProvider"

# Опубликовать миграции (если нужны локальные таблицы)
php artisan vendor:publish --tag=migrations --provider="Streeboga\GenesisLaravel\GenesisServiceProvider"

# Выполнить миграции
php artisan migrate
```

### Шаг 4: Проверка подключения

```bash
# Проверить соединение с Genesis API
php artisan genesis:test-connection

# Интерактивная настройка (опционально)
php artisan genesis:setup
```

## 🔧 Основные методы использования

### 1. Авторизация пользователей

```php
use Streeboga\GenesisLaravel\Facades\Genesis;

// Отправка OTP кода на email
$response = Genesis::auth()->sendOtp([
    'email' => 'user@example.com',
    'project_uuid' => env('GENESIS_PROJECT_UUID')
]);

// Верификация OTP кода
$response = Genesis::auth()->verifyOtp([
    'email' => 'user@example.com',
    'code' => '123456',
    'project_uuid' => env('GENESIS_PROJECT_UUID')
]);

// Получение сессии пользователя
$session = Genesis::auth()->getSession($sessionToken);

// Выход из системы
Genesis::auth()->logout($sessionToken);
```

### 2. Управление биллингом

```php
use Streeboga\GenesisLaravel\Facades\Genesis;

$projectId = env('GENESIS_PROJECT_UUID');

// Получить список тарифных планов
$plans = Genesis::billing()->listPlans($projectId);

// Создать подписку
$subscription = Genesis::billing()->createSubscription($projectId, [
    'user_uuid' => $userUuid,
    'plan_uuid' => $planUuid,
    'payment_method' => 'card'
]);

// Инициировать платеж
$payment = Genesis::billing()->initiatePayment([
    'project_uuid' => $projectId,
    'amount' => 1500,
    'currency' => 'RUB',
    'description' => 'Оплата подписки'
]);

// Получить статус подписки пользователя
$status = Genesis::billing()->getSubscriptionStatus($projectId, $userUuid);

// Рассчитать стоимость overage
$price = Genesis::billing()->calculateOveragePrice(
    $projectId, 
    $planUuid, 
    'api-calls', 
    1000
);
```

### 3. Управление функциями (Features)

```php
use Streeboga\GenesisLaravel\Facades\Genesis;

$projectId = env('GENESIS_PROJECT_UUID');

// Проверить доступ к функции
$hasAccess = Genesis::features()->check(
    $projectId,
    $userUuid,
    'api-calls'
);

// Использовать функцию (consume)
$result = Genesis::features()->consume(
    $projectId,
    $userUuid,
    'api-calls',
    10 // количество
);

// Получить оставшиеся лимиты
$limits = Genesis::features()->getLimits($projectId, $userUuid);

// Выдать демо-доступ
$demo = Genesis::demo()->giveAccess($projectId, $userUuid, [
    'features' => ['api-calls' => 100, 'storage' => 1],
    'days' => 7
]);
```

### 4. Управление пользователями

```php
use Streeboga\GenesisLaravel\Facades\Genesis;

$projectId = env('GENESIS_PROJECT_UUID');

// Получить профиль пользователя
$profile = Genesis::users()->getProfile($projectId, $userUuid);

// Обновить профиль
$updated = Genesis::users()->updateProfile($projectId, $userUuid, [
    'name' => 'John Doe',
    'metadata' => ['role' => 'admin']
]);

// Получить список сессий
$sessions = Genesis::users()->listSessions($projectId, $userUuid);

// Отозвать сессию
Genesis::users()->revokeSession($projectId, $userUuid, $sessionId);

// Удалить аккаунт
Genesis::users()->deleteAccount($projectId, $userUuid);
```

### 5. Защита маршрутов (Middleware)

```php
// routes/api.php
Route::middleware('genesis.auth')->group(function () {
    Route::get('/protected', 'ProtectedController@index');
    Route::post('/api/resource', 'ResourceController@store');
});

// Или в контроллере
class ProtectedController extends Controller
{
    public function __construct()
    {
        $this->middleware('genesis.auth');
    }
}
```

### 6. Blade директивы

```blade
{{-- Проверка авторизации --}}
@genesisAuth($token)
    <p>Вы авторизованы!</p>
    <a href="/dashboard">Перейти в личный кабинет</a>
@else
    <p>Пожалуйста, войдите в систему</p>
    <a href="/login">Войти</a>
@endgenesisAuth

{{-- Проверка доступа к функции --}}
@genesisFeature('premium-features')
    <div class="premium-content">
        Премиум контент доступен
    </div>
@else
    <div class="upgrade-prompt">
        <a href="/upgrade">Обновить подписку для доступа</a>
    </div>
@endgenesisFeature
```

### 7. Кэширование API ответов

```php
use Streeboga\GenesisLaravel\Services\GenesisCacheService;

class BillingController extends Controller
{
    public function __construct(
        private GenesisCacheService $cache
    ) {}

    public function getPlans()
    {
        $projectId = env('GENESIS_PROJECT_UUID');
        
        // Кэшировать на 1 час
        return $this->cache->remember(
            "plans:{$projectId}",
            fn() => Genesis::billing()->listPlans($projectId),
            3600
        );
    }
}
```

### 8. Обработка вебхуков

```php
// routes/api.php
Route::post('/webhooks/genesis', 'WebhookController@handleGenesis');

// app/Http/Controllers/WebhookController.php
use Streeboga\GenesisLaravel\Jobs\ProcessGenesisWebhook;

class WebhookController extends Controller
{
    public function handleGenesis(Request $request)
    {
        // Верификация подписи (если требуется)
        $signature = $request->header('X-Genesis-Signature');
        
        // Отправить в очередь для обработки
        ProcessGenesisWebhook::dispatch($request->all());
        
        return response()->json(['status' => 'accepted'], 200);
    }
}
```

### 9. Синхронизация данных через очереди

```php
use Streeboga\GenesisLaravel\Jobs\SyncGenesisData;

// Синхронизировать пользователей
SyncGenesisData::dispatch(
    env('GENESIS_PROJECT_UUID'),
    'users'
);

// Синхронизировать биллинг
SyncGenesisData::dispatch(
    env('GENESIS_PROJECT_UUID'),
    'billing'
);

// Запланировать регулярную синхронизацию
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->job(new SyncGenesisData(
        env('GENESIS_PROJECT_UUID'),
        'features'
    ))->hourly();
}
```

## 🔑 Важные UUID и креды для тестирования

```env
# Тестовые значения (замените на реальные)
GENESIS_API_KEY=test_api_key_xxxxxxxxxxxxx
GENESIS_PROJECT_UUID=550e8400-e29b-41d4-a716-446655440000
GENESIS_WEBHOOK_SECRET=webhook_secret_key_xxxxx

# Тестовый пользователь
TEST_USER_UUID=123e4567-e89b-12d3-a456-426614174000
TEST_USER_EMAIL=test@example.com

# Тестовый план подписки
TEST_PLAN_UUID=987f6543-e21c-34d5-b678-987654321000
```

## 📝 Последовательность действий для ИИ

1. **Установить пакеты** через composer
2. **Настроить .env** с реальными API ключами
3. **Опубликовать конфиг** через artisan команду
4. **Проверить подключение** командой genesis:test-connection
5. **Реализовать авторизацию** через Genesis::auth()
6. **Настроить биллинг** через Genesis::billing()
7. **Управлять функциями** через Genesis::features()
8. **Защитить маршруты** middleware
9. **Добавить кэширование** для оптимизации
10. **Настроить вебхуки** для real-time обновлений
11. **Запустить очереди** для фоновых задач
12. **Протестировать интеграцию** с реальными данными

## 🚀 Быстрый старт (копипаст для начала)

```php
// TestController.php
<?php

namespace App\Http\Controllers;

use Streeboga\GenesisLaravel\Facades\Genesis;

class TestController extends Controller
{
    public function testConnection()
    {
        try {
            $projectId = env('GENESIS_PROJECT_UUID');
            $plans = Genesis::billing()->listPlans($projectId);
            
            return response()->json([
                'status' => 'connected',
                'plans_count' => count($plans),
                'project_id' => $projectId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
```

## ⚠️ Проверочный чеклист

- [ ] Установлены пакеты streeboga/genesis и streeboga/genesis-laravel
- [ ] Настроены переменные окружения в .env
- [ ] Опубликован конфиг config/genesis.php
- [ ] Выполнены миграции (если нужны)
- [ ] Проверено подключение к API
- [ ] Реализована базовая авторизация
- [ ] Настроен хотя бы один маршрут с защитой
- [ ] Протестирован биллинг (получение планов)
- [ ] Проверена работа с функциями
- [ ] Настроены очереди для вебхуков (опционально)

## 📞 Поддержка

При возникновении проблем проверьте:
1. Корректность API ключей в .env
2. Доступность Genesis API по указанному BASE_URL
3. Правильность UUID проекта
4. Логи Laravel в storage/logs/laravel.log
5. Статус очередей (если используются)
