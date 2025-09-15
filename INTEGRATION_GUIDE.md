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

**🚀 Автоматическая регистрация:**
- Middleware `genesis.auth` регистрируется автоматически
- Blade директивы `@genesisAuth` и `@genesisFeature` доступны сразу
- Роуты пакета подключаются автоматически

### Шаг 4: Проверка подключения

```bash
# Проверить соединение с Genesis API
php artisan genesis:test-connection

# Интерактивная настройка (опционально)
php artisan genesis:setup
```

## 🎯 Выбор метода авторизации

### Когда использовать User Auth API:
- ✅ Пользователи уже авторизованы в вашей системе
- ✅ Нужен только checkout функционал
- ✅ Хотите избежать повторного ввода пароля
- ✅ Требуется быстрая интеграция оплаты

### Когда использовать стандартную авторизацию:
- ✅ Создаете полноценную интеграцию с Genesis API
- ✅ Нужен долгосрочный доступ к API функциям
- ✅ Требуется высокий уровень безопасности

### Когда использовать Genesis Platform OTP:
- ✅ Интеграция с Genesis Platform экосистемой
- ✅ Нужна OTP верификация
- ✅ Работа с Genesis специфичными функциями

## 🔧 Основные методы использования

### 1. Авторизация пользователей

#### A. User Auth API (Рекомендуется для checkout)

```php
use Streeboga\GenesisLaravel\Facades\Genesis;

// Авторизация пользователя по email без пароля
$session = Genesis::auth()->authenticateByEmail([
    'email' => 'user@example.com',
    'project_uuid' => env('GENESIS_PROJECT_UUID'),
    'plan_uuid' => 'plan-uuid-here', // опционально
    'name' => 'John Doe' // опционально
]);

// Проверка валидности сессии
$validation = Genesis::auth()->validateSession($sessionToken);

// Получение URL для оплаты
$paymentUrl = Genesis::auth()->getPaymentUrl($sessionToken, $planUuid);

// Продление сессии
$extended = Genesis::auth()->extendSession($sessionToken, 4); // на 4 часа

// Получение информации о сессии
$sessionInfo = Genesis::auth()->getSessionInfo($sessionToken);

// Завершение сессии
Genesis::auth()->destroySession($sessionToken);
```

#### B. Стандартная авторизация (для полного API доступа)

```php
use Streeboga\GenesisLaravel\Facades\Genesis;

// Полная авторизация с паролем
$tokens = Genesis::auth()->login([
    'email' => 'user@example.com',
    'password' => 'secure-password'
]);

// Регистрация нового пользователя
$user = Genesis::auth()->register([
    'email' => 'user@example.com',
    'password' => 'secure-password',
    'name' => 'John Doe'
]);

// Обновление токена
$newTokens = Genesis::auth()->refresh($refreshToken);

// Выход из системы
Genesis::auth()->logout($accessToken);
```

#### C. Genesis Platform OTP (для интеграции с Genesis)

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

// Инициировать платеж - СОВРЕМЕННЫЙ метод (рекомендуемый)
$payment = Genesis::billing()->initiatePayment($projectId, [
    'user_uuid' => $userUuid,
    'subscription_uuid' => $subscriptionUuid, // опционально
    'amount' => 1500,
    'currency' => 'RUB',
    'description' => 'Оплата подписки',
    'payment_method' => 'cloudpayments', // или 'robokassa'
    'return_url' => 'https://yoursite.com/success',
    'cancel_url' => 'https://yoursite.com/cancel',
    'metadata' => ['order_id' => '12345']
]);

// LEGACY метод (для совместимости с существующим кодом)
$payment = Genesis::billing()->initiatePayment([
    'project_uuid' => $projectId,
    'user_uuid' => $userUuid,
    'amount' => 1500,
    'currency' => 'RUB',
    'description' => 'Оплата подписки',
    'payment_method' => 'cloudpayments',
    'return_url' => 'https://yoursite.com/success',
    'cancel_url' => 'https://yoursite.com/cancel'
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

#### 🔄 Полный цикл создания подписки и платежа

Комплексный пример создания пользователя, подписки и инициации платежа:

```php
use Streeboga\GenesisLaravel\Facades\Genesis;
use Illuminate\Support\Facades\DB;

$projectId = env('GENESIS_PROJECT_UUID');

try {
    DB::beginTransaction();

    // 1. Создание/поиск пользователя через OTP
    $otpResponse = Genesis::auth()->sendOtp([
        'email' => 'user@example.com',
        'project_uuid' => $projectId,
        'user_uuid' => Str::uuid(),
        'name' => 'John Doe'
    ]);
    
    // 2. Верификация OTP (имитация кода 123456 для тестов)
    $verificationResponse = Genesis::auth()->verifyOtp([
        'email' => 'user@example.com',
        'code' => '123456',
        'project_uuid' => $projectId
    ]);
    
    if ($verificationResponse['success']) {
        $userUuid = $verificationResponse['data']['user']['uuid'];
        
        // 3. Создание подписки
        $subscription = Genesis::billing()->createSubscription($projectId, [
            'user_uuid' => $userUuid,
            'plan_uuid' => $planUuid,
            'email' => 'user@example.com',
            'name' => 'John Doe'
        ]);
        
        if ($subscription['success']) {
            // 4. Инициация платежа
            $payment = Genesis::billing()->initiatePayment($projectId, [
                'user_uuid' => $userUuid,
                'subscription_uuid' => $subscription['data']['uuid'],
                'amount' => $subscription['data']['plan']['price'],
                'currency' => 'RUB',
                'description' => 'Оплата подписки на план: ' . $subscription['data']['plan']['name'],
                'payment_method' => 'cloudpayments',
                'return_url' => route('payment.success'),
                'cancel_url' => route('payment.cancel'),
                'metadata' => [
                    'subscription_id' => $subscription['data']['id'],
                    'source' => 'genesis_integration'
                ]
            ]);
            
            DB::commit();
            
            // 5. Перенаправление на платежную страницу
            return response()->json([
                'success' => true,
                'payment_url' => $payment['data']['payment_url'],
                'transaction_uuid' => $payment['data']['transaction_uuid'],
                'subscription' => $subscription['data']
            ]);
        }
    }
    
} catch (\Exception $e) {
    DB::rollback();
    
    return response()->json([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
```

#### 🌊 Обработка платежных колбэков

Пример обработки результатов платежа:

```php
// routes/web.php
Route::post('/webhooks/genesis-payment', 'PaymentWebhookController@handle');

// PaymentWebhookController.php
class PaymentWebhookController extends Controller 
{
    public function handle(Request $request)
    {
        // Верификация webhook подписи
        $signature = $request->header('X-Genesis-Signature');
        if (!$this->verifySignature($request->getContent(), $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        $data = $request->json()->all();
        
        switch ($data['event']) {
            case 'payment.completed':
                $this->handlePaymentSuccess($data);
                break;
                
            case 'payment.failed': 
                $this->handlePaymentFailure($data);
                break;
                
            case 'subscription.activated':
                $this->handleSubscriptionActivated($data);
                break;
        }
        
        return response()->json(['status' => 'processed']);
    }
    
    private function handlePaymentSuccess($data)
    {
        $transactionUuid = $data['transaction_uuid'];
        $subscriptionUuid = $data['subscription_uuid'];
        
        // Обновляем статус заказа в локальной БД
        // Отправляем подтверждение пользователю
        // Активируем подписку
        
        Log::info('Payment completed successfully', $data);
    }
}
```

#### 💳 Доступные платежные методы

Поддерживаемые платежные провайдеры:

```php
// Получить доступные методы оплаты для проекта  
$paymentMethods = [
    'cloudpayments' => 'CloudPayments (карты)',
    'robokassa' => 'RoboKassa (карты, кошельки)',
    'card' => 'Прямая интеграция карт',
    'bank_transfer' => 'Банковский перевод',
    'wallet' => 'Электронные кошельки'
];

// Проверка доступности метода для проекта
$project = Project::where('uuid', $projectId)->first();
$availableMethods = $project->paymentMethods()
    ->where('is_active', true)
    ->pluck('type')
    ->toArray();
```

#### 🔗 Создание ссылки на оформление подписки

Быстрый способ создать готовую ссылку для оплаты конкретного плана:

```php
use Streeboga\GenesisLaravel\Facades\Genesis;
use Illuminate\Support\Facades\DB;

/**
 * Создать прямую ссылку на оплату плана
 */
public function createPaymentLink(string $planUuid, array $userData, array $options = []): string
{
    $projectId = config('genesis.project_uuid');
    
    try {
        DB::beginTransaction();
        
        // 1. Создание подписки
        $subscription = Genesis::billing()->createSubscription($projectId, [
            'user_uuid' => $userData['user_uuid'],
            'plan_uuid' => $planUuid,
            'email' => $userData['email'],
            'name' => $userData['name'],
            'phone' => $userData['phone'] ?? null,
        ]);
        
        // 2. Инициация платежа  
        $payment = Genesis::billing()->initiatePayment($projectId, [
            'user_uuid' => $userData['user_uuid'],
            'subscription_uuid' => $subscription['subscription']['uuid'],
            'amount' => $subscription['subscription']['plan']['price'],
            'currency' => $subscription['subscription']['plan']['currency'],
            'description' => "Оплата: {$subscription['subscription']['plan']['name']}",
            'payment_method' => $options['payment_method'] ?? 'cloudpayments',
            'return_url' => $options['return_url'] ?? route('payment.success'),
            'cancel_url' => $options['cancel_url'] ?? route('payment.cancel'),
        ]);
        
        DB::commit();
        return $payment['payment_url'];
        
    } catch (Exception $e) {
        DB::rollBack();
        throw new Exception("Payment link failed: " . $e->getMessage());
    }
}

// Использование:
$paymentUrl = $this->createPaymentLink(
    'plan-uuid-here',
    [
        'user_uuid' => 'user-' . uniqid(),
        'email' => 'user@example.com', 
        'name' => 'John Doe'
    ],
    [
        'payment_method' => 'cloudpayments',
        'return_url' => 'https://mysite.com/success'
    ]
);

// В контроллере:
return redirect($paymentUrl);

// Или для AJAX:
return response()->json(['payment_url' => $paymentUrl]);
```

**Пример для конкретного случая (исправление 405 ошибки):**

Если у вас есть старый код, который не работает:

```php
// ❌ СТАРЫЙ код (не работает):  
$payment = Genesis::billing()->initiatePayment([
    'project_uuid' => $projectId,
    'amount' => 1500
]);

// ✅ НОВЫЙ код (работает с backward compatibility):
$payment = Genesis::billing()->initiatePayment($projectId, [
    'user_uuid' => $userUuid,
    'amount' => 1500,
    'currency' => 'RUB',
    'payment_method' => 'cloudpayments'
]);
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

**Middleware `genesis.auth` регистрируется автоматически** при подключении пакета.

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

## 🎮 Практические сценарии использования

### Сценарий 1: E-commerce checkout с User Auth API

```php
// CheckoutController.php
class CheckoutController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'plan_uuid' => 'required|uuid',
            'user_email' => 'required|email'
        ]);

        try {
            // 1. Создаем сессию для пользователя
            $session = Genesis::auth()->authenticateByEmail([
                'email' => $request->user_email,
                'project_uuid' => config('genesis.project_uuid'),
                'plan_uuid' => $request->plan_uuid,
                'name' => auth()->user()->name ?? null
            ]);

            // 2. Получаем URL для оплаты
            $paymentResponse = Genesis::auth()->getPaymentUrl(
                $session['data']['session_token'],
                $request->plan_uuid
            );

            // 3. Сохраняем токен в сессии для отслеживания
            session([
                'genesis_session_token' => $session['data']['session_token'],
                'genesis_expires_at' => $session['data']['expires_at']
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $paymentResponse['data']['checkout_url'],
                'session_expires_at' => $session['data']['expires_at']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания checkout сессии: ' . $e->getMessage()
            ], 400);
        }
    }

    public function checkPaymentStatus()
    {
        $sessionToken = session('genesis_session_token');
        
        if (!$sessionToken) {
            return response()->json(['error' => 'Сессия не найдена'], 404);
        }

        try {
            $sessionInfo = Genesis::auth()->getSessionInfo($sessionToken);
            
            return response()->json([
                'status' => $sessionInfo['data']['payment_status'] ?? 'pending',
                'user' => $sessionInfo['data']['user'],
                'expires_at' => $sessionInfo['data']['expires_at']
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка проверки статуса'], 400);
        }
    }
}
```

### Сценарий 2: SaaS приложение с полной интеграцией

```php
// UserService.php
class UserService
{
    public function authenticateUser(string $email, string $password): array
    {
        try {
            // Полная авторизация для доступа ко всем API
            $tokens = Genesis::auth()->login([
                'email' => $email,
                'password' => $password
            ]);

            // Сохраняем токены в базе или кеше
            $this->storeUserTokens($email, $tokens);

            return [
                'success' => true,
                'user' => $tokens['user'],
                'access_token' => $tokens['access_token']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getUserFeatures(string $userUuid): array
    {
        $projectId = config('genesis.project_uuid');
        
        // Получаем доступные функции пользователя
        $features = Genesis::features()->getFeatures($projectId, $userUuid);
        
        // Проверяем лимиты
        $limits = Genesis::features()->getLimits($projectId, $userUuid);
        
        return [
            'features' => $features,
            'limits' => $limits,
            'subscription' => Genesis::billing()->getSubscriptionStatus($projectId, $userUuid)
        ];
    }
}
```

### Сценарий 3: Middleware для защиты маршрутов

```php
// GenesisAuthMiddleware.php
class GenesisAuthMiddleware
{
    public function handle(Request $request, Closure $next, string $authType = 'session')
    {
        if ($authType === 'session') {
            return $this->handleSessionAuth($request, $next);
        } else {
            return $this->handleTokenAuth($request, $next);
        }
    }

    private function handleSessionAuth(Request $request, Closure $next)
    {
        $sessionToken = $request->header('X-Genesis-Session-Token') 
                     ?? session('genesis_session_token');

        if (!$sessionToken) {
            return response()->json(['error' => 'Session token required'], 401);
        }

        try {
            $validation = Genesis::auth()->validateSession($sessionToken);
            
            if (!$validation['success']) {
                return response()->json(['error' => 'Invalid session'], 401);
            }

            // Добавляем данные пользователя в request
            $request->merge(['genesis_user' => $validation['data']['user']]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Session validation failed'], 500);
        }

        return $next($request);
    }

    private function handleTokenAuth(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'Bearer token required'], 401);
        }

        // Здесь можно добавить валидацию JWT токена
        // или проверку через Genesis API
        
        return $next($request);
    }
}
```

### Сценарий 4: Webhook обработка

```php
// WebhookController.php
class WebhookController extends Controller
{
    public function handleGenesisWebhook(Request $request)
    {
        // Верификация подписи
        $signature = $request->header('X-Genesis-Signature');
        $payload = $request->getContent();
        
        if (!$this->verifySignature($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->json()->all();
        
        // Обработка разных типов событий
        switch ($event['type']) {
            case 'payment.completed':
                $this->handlePaymentCompleted($event['data']);
                break;
                
            case 'subscription.created':
                $this->handleSubscriptionCreated($event['data']);
                break;
                
            case 'user.session.expired':
                $this->handleSessionExpired($event['data']);
                break;
                
            default:
                Log::info('Unknown Genesis webhook event', ['type' => $event['type']]);
        }

        return response()->json(['status' => 'processed']);
    }

    private function handlePaymentCompleted(array $data): void
    {
        // Обновляем статус заказа в базе данных
        $order = Order::where('genesis_session_token', $data['session_token'])->first();
        
        if ($order) {
            $order->update([
                'status' => 'paid',
                'payment_id' => $data['payment_id'],
                'paid_at' => now()
            ]);
            
            // Отправляем уведомление пользователю
            Mail::to($order->user)->send(new PaymentConfirmationMail($order));
        }
    }

    private function handleSessionExpired(array $data): void
    {
        // Очищаем истекшие сессии из кеша
        Cache::forget("genesis_session_{$data['session_token']}");
        
        // Уведомляем пользователя о необходимости повторной авторизации
        if (isset($data['user']['email'])) {
            // Логика уведомления
        }
    }
}
```

### Сценарий 5: Кастомные Artisan команды

```php
// SyncGenesisDataCommand.php
class SyncGenesisDataCommand extends Command
{
    protected $signature = 'genesis:sync {type=all} {--project=}';
    protected $description = 'Синхронизация данных с Genesis Platform';

    public function handle()
    {
        $type = $this->argument('type');
        $projectId = $this->option('project') ?? config('genesis.project_uuid');

        $this->info("Начинаем синхронизацию данных типа: {$type}");

        try {
            switch ($type) {
                case 'users':
                    $this->syncUsers($projectId);
                    break;
                    
                case 'plans':
                    $this->syncPlans($projectId);
                    break;
                    
                case 'all':
                    $this->syncUsers($projectId);
                    $this->syncPlans($projectId);
                    break;
                    
                default:
                    $this->error("Неизвестный тип данных: {$type}");
                    return 1;
            }

            $this->info('Синхронизация завершена успешно!');
            return 0;

        } catch (\Exception $e) {
            $this->error("Ошибка синхронизации: {$e->getMessage()}");
            return 1;
        }
    }

    private function syncUsers(string $projectId): void
    {
        $this->info('Синхронизация пользователей...');
        
        // Получаем список пользователей из Genesis
        $users = Genesis::users()->listUsers($projectId);
        
        $bar = $this->output->createProgressBar(count($users));
        
        foreach ($users as $genesisUser) {
            // Обновляем или создаем пользователя в локальной БД
            User::updateOrCreate(
                ['genesis_uuid' => $genesisUser['uuid']],
                [
                    'email' => $genesisUser['email'],
                    'name' => $genesisUser['name'],
                    'genesis_data' => $genesisUser
                ]
            );
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
    }

    private function syncPlans(string $projectId): void
    {
        $this->info('Синхронизация тарифных планов...');
        
        $plans = Genesis::billing()->listPlans($projectId);
        
        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['genesis_uuid' => $plan['uuid']],
                [
                    'name' => $plan['name'],
                    'price' => $plan['price'],
                    'currency' => $plan['currency'],
                    'features' => $plan['features'],
                    'genesis_data' => $plan
                ]
            );
        }
        
        $this->info("Синхронизировано планов: " . count($plans));
    }
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

## 🔧 Расширенная настройка

### Настройка кеширования

```php
// config/cache.php
'stores' => [
    'genesis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'prefix' => 'genesis:',
    ],
],

// В сервисах
use Illuminate\Support\Facades\Cache;

class BillingService
{
    public function getCachedPlans(string $projectId): array
    {
        return Cache::store('genesis')->remember(
            "plans:{$projectId}",
            now()->addHour(),
            fn() => Genesis::billing()->listPlans($projectId)
        );
    }
}
```

### Настройка очередей для производительности

```php
// config/queue.php
'connections' => [
    'genesis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('GENESIS_QUEUE', 'genesis'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],

// Использование в коде
use Streeboga\GenesisLaravel\Jobs\ProcessGenesisWebhook;
use Streeboga\GenesisLaravel\Jobs\SyncUserData;

// Обработка webhook в фоне
ProcessGenesisWebhook::dispatch($webhookData)->onQueue('genesis');

// Синхронизация данных пользователя
SyncUserData::dispatch($userId, $projectId)->onQueue('genesis');
```

### Настройка логирования

```php
// config/logging.php
'channels' => [
    'genesis' => [
        'driver' => 'daily',
        'path' => storage_path('logs/genesis.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
],

// В сервисах
use Illuminate\Support\Facades\Log;

class GenesisService
{
    public function logApiCall(string $method, array $data, $response): void
    {
        Log::channel('genesis')->info("Genesis API Call: {$method}", [
            'request' => $data,
            'response' => $response,
            'timestamp' => now()->toISOString()
        ]);
    }
}
```

### Мониторинг и метрики

```php
// app/Services/GenesisMonitoringService.php
class GenesisMonitoringService
{
    public function trackApiUsage(string $endpoint, float $responseTime): void
    {
        // Отправка метрик в систему мониторинга
        $this->sendMetric('genesis.api.response_time', $responseTime, [
            'endpoint' => $endpoint
        ]);
    }

    public function trackError(string $endpoint, \Exception $e): void
    {
        $this->sendMetric('genesis.api.error', 1, [
            'endpoint' => $endpoint,
            'error_type' => get_class($e)
        ]);
    }
}
```

## 🧪 Тестирование интеграции

### Unit тесты

```php
// tests/Unit/GenesisServiceTest.php
use Tests\TestCase;
use Streeboga\GenesisLaravel\Facades\Genesis;
use Illuminate\Support\Facades\Http;

class GenesisServiceTest extends TestCase
{
    public function test_can_list_plans(): void
    {
        Http::fake([
            'api.genesis.com/v1/billing/plans*' => Http::response([
                'success' => true,
                'data' => [
                    ['uuid' => 'plan-1', 'name' => 'Basic', 'price' => 1000]
                ]
            ])
        ]);

        $plans = Genesis::billing()->listPlans('project-uuid');
        
        $this->assertIsArray($plans);
        $this->assertCount(1, $plans);
        $this->assertEquals('Basic', $plans[0]['name']);
    }

    public function test_handles_api_errors_gracefully(): void
    {
        Http::fake([
            'api.genesis.com/v1/*' => Http::response(['error' => 'Unauthorized'], 401)
        ]);

        $this->expectException(\Streeboga\Genesis\Exceptions\ApiException::class);
        
        Genesis::billing()->listPlans('invalid-project');
    }
}
```

### Feature тесты

```php
// tests/Feature/GenesisIntegrationTest.php
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GenesisIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_processing(): void
    {
        $webhookData = [
            'type' => 'payment.completed',
            'data' => [
                'payment_id' => 'pay_123',
                'user_uuid' => 'user_456',
                'amount' => 1500
            ]
        ];

        $response = $this->postJson('/api/webhooks/genesis', $webhookData, [
            'X-Genesis-Signature' => $this->generateSignature($webhookData)
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'processed']);
    }

    public function test_protected_route_requires_auth(): void
    {
        $response = $this->getJson('/api/protected');
        
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Session token required']);
    }

    public function test_protected_route_works_with_valid_token(): void
    {
        $token = $this->createValidSessionToken();
        
        $response = $this->getJson('/api/protected', [
            'X-Genesis-Session-Token' => $token
        ]);
        
        $response->assertStatus(200);
    }
}
```

### Интеграционные тесты

```php
// tests/Integration/GenesisApiTest.php
class GenesisApiTest extends TestCase
{
    /** @test */
    public function it_can_create_user_and_get_payment_url(): void
    {
        // Создаем пользователя через User Auth API
        $session = Genesis::auth()->authenticateByEmail([
            'email' => 'test@example.com',
            'project_uuid' => config('genesis.project_uuid'),
            'name' => 'Test User'
        ]);

        $this->assertArrayHasKey('session_token', $session['data']);
        
        // Получаем URL для оплаты
        $paymentUrl = Genesis::auth()->getPaymentUrl(
            $session['data']['session_token'],
            'plan-uuid-here'
        );

        $this->assertArrayHasKey('checkout_url', $paymentUrl['data']);
        $this->assertStringContainsString('checkout', $paymentUrl['data']['checkout_url']);
    }
}
```

## 🚀 Производственное развертывание

### Настройка окружения

```bash
# Производственные переменные
GENESIS_API_KEY=prod_api_key_here
GENESIS_BASE_URL=https://api.genesis.com/v1/
GENESIS_PROJECT_UUID=your-production-project-uuid

# Кеширование
GENESIS_CACHE_ENABLED=true
GENESIS_CACHE_TTL=3600
CACHE_DRIVER=redis

# Очереди
QUEUE_CONNECTION=redis
GENESIS_QUEUE_CONNECTION=redis
GENESIS_WEBHOOK_QUEUE=genesis-webhooks

# Логирование
LOG_CHANNEL=stack
LOG_LEVEL=info
```

### Supervisor конфигурация

```ini
; /etc/supervisor/conf.d/genesis-worker.conf
[program:genesis-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/artisan queue:work redis --queue=genesis-webhooks,genesis-sync --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/worker.log
stopwaitsecs=3600
```

### Мониторинг и алерты

```php
// app/Console/Commands/GenesisHealthCheck.php
class GenesisHealthCheck extends Command
{
    protected $signature = 'genesis:health-check';
    protected $description = 'Проверка состояния Genesis API';

    public function handle(): int
    {
        try {
            $projectId = config('genesis.project_uuid');
            $plans = Genesis::billing()->listPlans($projectId);
            
            $this->info('✅ Genesis API доступен');
            $this->info("Найдено планов: " . count($plans));
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Genesis API недоступен: ' . $e->getMessage());
            
            // Отправка алерта
            $this->sendAlert('Genesis API Health Check Failed', $e->getMessage());
            
            return 1;
        }
    }

    private function sendAlert(string $subject, string $message): void
    {
        // Интеграция с системой алертов (Slack, email, etc.)
        Mail::to(config('app.admin_email'))->send(
            new AlertMail($subject, $message)
        );
    }
}
```

### Автоматизация развертывания

```bash
#!/bin/bash
# deploy.sh

echo "🚀 Развертывание Genesis интеграции..."

# Обновление кода
git pull origin main

# Установка зависимостей
composer install --no-dev --optimize-autoloader

# Кеширование конфигурации
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Миграции
php artisan migrate --force

# Перезапуск очередей
php artisan queue:restart

# Проверка здоровья
php artisan genesis:health-check

echo "✅ Развертывание завершено!"
```

## ⚠️ Проверочный чеклист

### Базовая настройка
- [ ] Установлены пакеты streeboga/genesis и streeboga/genesis-laravel
- [ ] Настроены переменные окружения в .env
- [ ] Опубликован конфиг config/genesis.php
- [ ] Выполнены миграции (если нужны)
- [ ] Проверено подключение к API

### Функциональность
- [ ] Реализована базовая авторизация (User Auth API или стандартная)
- [ ] Настроен хотя бы один маршрут с защитой
- [ ] Протестирован биллинг (получение планов)
- [ ] **НОВОЕ**: Протестирован полный цикл создания подписки и платежа
- [ ] **НОВОЕ**: Настроена интеграция с платежными провайдерами (CloudPayments/RoboKassa)  
- [ ] **НОВОЕ**: Проверена работа POST /api/v1/projects/{project}/payments
- [ ] **НОВОЕ**: Реализован метод создания ссылок на оформление подписки
- [ ] **НОВОЕ**: Протестирована backward compatibility для существующего кода
- [ ] Проверена работа с функциями
- [ ] Настроены webhook обработчики для платежных событий

### Производительность
- [ ] Настроено кеширование API ответов
- [ ] Настроены очереди для вебхуков
- [ ] Настроено логирование Genesis операций
- [ ] Добавлен мониторинг API вызовов

### Безопасность
- [ ] Проверена валидация webhook подписей
- [ ] Настроена защита API ключей
- [ ] Добавлена обработка ошибок авторизации
- [ ] Настроены rate limits для API вызовов

### Тестирование
- [ ] Написаны unit тесты для основных сервисов
- [ ] Добавлены feature тесты для API endpoints
- [ ] Проведены интеграционные тесты с реальным API
- [ ] Настроены автоматические health checks

### Производство
- [ ] Настроены производственные переменные окружения
- [ ] Настроен Supervisor для очередей
- [ ] Добавлены алерты для критических ошибок
- [ ] Настроена автоматизация развертывания

## 📞 Поддержка и отладка

### Частые проблемы

**1. Ошибка "Project not found"**
```bash
# Проверьте правильность UUID проекта
php artisan tinker
>>> config('genesis.project_uuid')
>>> App\Models\Project::where('uuid', 'your-uuid')->first()
```

**2. Ошибки авторизации API**
```bash
# Проверьте API ключ
php artisan tinker
>>> config('genesis.api_key')
>>> Genesis::billing()->listPlans('test-project-uuid')
```

**4. Тестирование платежного API**
```bash
# Проверка доступности платежных методов
php artisan tinker
>>> $project = App\Models\Project::where('uuid', 'your-project-uuid')->first()
>>> $project->paymentMethods()->where('is_active', true)->get(['type'])

# Проверка маршрута платежей
php artisan route:list --path="payments"

# Тестирование создания подписки
>>> Genesis::billing()->createSubscription('project-uuid', ['user_uuid' => 'test', 'plan_uuid' => 'plan'])

# Тестирование инициации платежа (НОВЫЙ формат)
>>> Genesis::billing()->initiatePayment('project-uuid', ['user_uuid' => 'test', 'amount' => 1000, 'currency' => 'RUB', 'payment_method' => 'cloudpayments'])

# Или LEGACY формат (для совместимости)
>>> Genesis::billing()->initiatePayment(['project_uuid' => 'project-uuid', 'user_uuid' => 'test', 'amount' => 1000, 'currency' => 'RUB'])
```

**3. Проблемы с webhook**
```bash
# Проверьте очереди
php artisan queue:work --once
php artisan queue:failed
php artisan queue:retry all
```

### Логи и отладка

```bash
# Просмотр логов Genesis
tail -f storage/logs/genesis.log

# Просмотр общих логов Laravel
tail -f storage/logs/laravel.log

# Отладка очередей
php artisan queue:work --verbose

# Проверка конфигурации
php artisan config:show genesis
```

### Контакты поддержки

При возникновении проблем проверьте:
1. Корректность API ключей в .env
2. Доступность Genesis API по указанному BASE_URL
3. Правильность UUID проекта
4. Логи Laravel в storage/logs/laravel.log
5. Статус очередей (если используются)
6. Настройки кеширования и Redis подключения

**Техническая поддержка:**
- Email: support@genesis.com
- Документация: https://docs.genesis.com
- GitHub Issues: https://github.com/streeboga/genesis/issues




