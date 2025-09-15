# User Auth API - Интеграция с Laravel

Этот документ описывает интеграцию User Auth API в Laravel приложения с использованием пакета `streeboga/genesis-laravel`.

## Установка и настройка

### 1. Установка пакета

```bash
composer require streeboga/genesis-laravel
```

### 2. Публикация конфигурации

```bash
php artisan vendor:publish --tag=config --provider="Streeboga\\GenesisLaravel\\GenesisServiceProvider"
```

**🚀 Автоматическая настройка:**
- Middleware `genesis.auth` регистрируется автоматически при подключении пакета
- Не требует дополнительной конфигурации в `bootstrap/app.php`
- Blade директивы `@genesisAuth` и `@genesisFeature` доступны сразу

### 3. Настройка переменных окружения

Добавьте в `.env` файл:

```env
GENESIS_API_KEY=your-genesis-api-key
GENESIS_BASE_URL=https://your-genesis-api.com/api/
GENESIS_PROJECT_UUID=your-project-uuid
```

### 4. Обновление конфигурации

Отредактируйте `config/genesis.php`:

```php
<?php

return [
    'api_key' => env('GENESIS_API_KEY'),
    'base_url' => env('GENESIS_BASE_URL', 'https://api.genesis.com/v1/'),
    'project_uuid' => env('GENESIS_PROJECT_UUID'),
];
```

## Основные способы использования

### 1. Через фасад (рекомендуется)

```php
use Streeboga\GenesisLaravel\Facades\Genesis;

class CheckoutController extends Controller
{
    public function createSession(Request $request)
    {
        $session = Genesis::auth()->authenticateByEmail([
            'email' => $request->user()->email,
            'project_uuid' => config('genesis.project_uuid'),
            'plan_uuid' => $request->plan_uuid
        ]);

        if (!$session['success']) {
            return back()->withErrors(['error' => $session['message']]);
        }

        $paymentUrl = Genesis::auth()->getPaymentUrl(
            $session['data']['session_token'],
            $request->plan_uuid
        );

        return redirect($paymentUrl['data']['checkout_url']);
    }
}
```

### 2. Через Dependency Injection

```php
use Streeboga\Genesis\GenesisClient;

class PaymentService
{
    public function __construct(private GenesisClient $genesis) {}

    public function createCheckoutSession(User $user, string $planUuid): array
    {
        return $this->genesis->auth->authenticateByEmail([
            'email' => $user->email,
            'project_uuid' => config('genesis.project_uuid'),
            'plan_uuid' => $planUuid,
            'name' => $user->name
        ]);
    }

    public function getPaymentUrl(string $sessionToken, string $planUuid): array
    {
        return $this->genesis->auth->getPaymentUrl($sessionToken, $planUuid);
    }
}
```

## Практические примеры

### 1. Простой checkout контроллер

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Streeboga\GenesisLaravel\Facades\Genesis;

class CheckoutController extends Controller
{
    /**
     * Создать сессию для оплаты
     */
    public function createSession(Request $request)
    {
        $request->validate([
            'plan_uuid' => 'required|uuid'
        ]);

        try {
            // Создаем сессию для текущего пользователя
            $session = Genesis::auth()->authenticateByEmail([
                'email' => auth()->user()->email,
                'project_uuid' => config('genesis.project_uuid'),
                'plan_uuid' => $request->plan_uuid,
                'name' => auth()->user()->name
            ]);

            if (!$session['success']) {
                throw new \Exception($session['message']);
            }

            // Получаем URL для оплаты
            $paymentResponse = Genesis::auth()->getPaymentUrl(
                $session['data']['session_token'],
                $request->plan_uuid
            );

            if (!$paymentResponse['success']) {
                throw new \Exception($paymentResponse['message']);
            }

            // Сохраняем токен сессии в сессии Laravel для отслеживания
            session(['genesis_session_token' => $session['data']['session_token']]);

            return response()->json([
                'success' => true,
                'checkout_url' => $paymentResponse['data']['checkout_url'],
                'session_expires_at' => $session['data']['expires_at']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания сессии: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Проверить статус сессии
     */
    public function checkSession()
    {
        $sessionToken = session('genesis_session_token');
        
        if (!$sessionToken) {
            return response()->json([
                'success' => false,
                'message' => 'Сессия не найдена'
            ], 404);
        }

        try {
            $validation = Genesis::auth()->validateSession($sessionToken);
            
            return response()->json($validation);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка проверки сессии: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Завершить сессию
     */
    public function destroySession()
    {
        $sessionToken = session('genesis_session_token');
        
        if ($sessionToken) {
            try {
                Genesis::auth()->destroySession($sessionToken);
            } catch (\Exception $e) {
                // Логируем ошибку, но не прерываем процесс
                \Log::warning('Ошибка завершения Genesis сессии: ' . $e->getMessage());
            }
        }

        session()->forget('genesis_session_token');

        return response()->json([
            'success' => true,
            'message' => 'Сессия завершена'
        ]);
    }
}
```

### 2. Сервис для управления сессиями

```php
<?php

namespace App\Services;

use App\Models\User;
use Streeboga\Genesis\GenesisClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenesisSessionService
{
    public function __construct(private GenesisClient $genesis) {}

    /**
     * Создать сессию для пользователя
     */
    public function createUserSession(User $user, string $planUuid): array
    {
        try {
            $response = $this->genesis->auth->authenticateByEmail([
                'email' => $user->email,
                'project_uuid' => config('genesis.project_uuid'),
                'plan_uuid' => $planUuid,
                'name' => $user->name
            ]);

            if ($response['success']) {
                // Кешируем информацию о сессии
                $this->cacheSessionInfo($user->id, $response['data']);
                
                Log::info('Genesis session created', [
                    'user_id' => $user->id,
                    'plan_uuid' => $planUuid,
                    'session_token' => substr($response['data']['session_token'], 0, 8) . '...'
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Genesis session creation failed', [
                'user_id' => $user->id,
                'plan_uuid' => $planUuid,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка создания сессии: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Получить URL для оплаты
     */
    public function getPaymentUrl(string $sessionToken, string $planUuid): array
    {
        try {
            return $this->genesis->auth->getPaymentUrl($sessionToken, $planUuid);
        } catch (\Exception $e) {
            Log::error('Genesis payment URL generation failed', [
                'session_token' => substr($sessionToken, 0, 8) . '...',
                'plan_uuid' => $planUuid,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка получения URL: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Проверить валидность сессии
     */
    public function validateSession(string $sessionToken): array
    {
        try {
            return $this->genesis->auth->validateSession($sessionToken);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка валидации сессии: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Продлить сессию
     */
    public function extendSession(string $sessionToken, int $hours = 2): array
    {
        try {
            $response = $this->genesis->auth->extendSession($sessionToken, $hours);
            
            if ($response['success']) {
                Log::info('Genesis session extended', [
                    'session_token' => substr($sessionToken, 0, 8) . '...',
                    'hours' => $hours
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('Genesis session extension failed', [
                'session_token' => substr($sessionToken, 0, 8) . '...',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка продления сессии: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Завершить сессию
     */
    public function destroySession(string $sessionToken): array
    {
        try {
            $response = $this->genesis->auth->destroySession($sessionToken);
            
            // Очищаем кеш
            $this->clearSessionCache($sessionToken);
            
            Log::info('Genesis session destroyed', [
                'session_token' => substr($sessionToken, 0, 8) . '...'
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Genesis session destruction failed', [
                'session_token' => substr($sessionToken, 0, 8) . '...',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка завершения сессии: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Кешировать информацию о сессии
     */
    private function cacheSessionInfo(int $userId, array $sessionData): void
    {
        $cacheKey = "genesis_session_{$userId}";
        $ttl = now()->addHours(2); // Кешируем на 2 часа
        
        Cache::put($cacheKey, [
            'session_token' => $sessionData['session_token'],
            'user' => $sessionData['user'],
            'expires_at' => $sessionData['expires_at'],
            'created_at' => now()
        ], $ttl);
    }

    /**
     * Очистить кеш сессии
     */
    private function clearSessionCache(string $sessionToken): void
    {
        // Находим и удаляем кеш по токену
        $pattern = "genesis_session_*";
        $keys = Cache::getRedis()->keys($pattern);
        
        foreach ($keys as $key) {
            $data = Cache::get($key);
            if ($data && $data['session_token'] === $sessionToken) {
                Cache::forget($key);
                break;
            }
        }
    }
}
```

### 3. Middleware для проверки Genesis сессии

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Streeboga\GenesisLaravel\Facades\Genesis;

class ValidateGenesisSession
{
    public function handle(Request $request, Closure $next)
    {
        $sessionToken = $request->header('X-Genesis-Session-Token') 
                     ?? session('genesis_session_token');

        if (!$sessionToken) {
            return response()->json([
                'success' => false,
                'message' => 'Genesis session token required'
            ], 401);
        }

        try {
            $validation = Genesis::auth()->validateSession($sessionToken);
            
            if (!$validation['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Genesis session'
                ], 401);
            }

            // Добавляем данные сессии в request
            $request->merge(['genesis_session' => $validation['data']]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Genesis session validation error'
            ], 500);
        }

        return $next($request);
    }
}
```

### 4. Artisan команда для очистки истекших сессий

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CleanupGenesisSessions extends Command
{
    protected $signature = 'genesis:cleanup-sessions';
    protected $description = 'Clean up expired Genesis sessions from cache';

    public function handle()
    {
        $pattern = "genesis_session_*";
        $keys = Cache::getRedis()->keys($pattern);
        $cleaned = 0;

        foreach ($keys as $key) {
            $data = Cache::get($key);
            
            if ($data && isset($data['expires_at'])) {
                $expiresAt = \Carbon\Carbon::parse($data['expires_at']);
                
                if ($expiresAt->isPast()) {
                    Cache::forget($key);
                    $cleaned++;
                }
            }
        }

        $this->info("Cleaned up {$cleaned} expired Genesis sessions.");
    }
}
```

## Маршруты

Добавьте в `routes/web.php` или `routes/api.php`:

```php
// Web routes для checkout
Route::middleware(['auth'])->group(function () {
    Route::post('/checkout/create-session', [CheckoutController::class, 'createSession'])
        ->name('checkout.create-session');
    
    Route::get('/checkout/session-status', [CheckoutController::class, 'checkSession'])
        ->name('checkout.session-status');
    
    Route::delete('/checkout/destroy-session', [CheckoutController::class, 'destroySession'])
        ->name('checkout.destroy-session');
});

// API routes с middleware проверки Genesis сессии
Route::middleware(['validate.genesis.session'])->group(function () {
    Route::get('/api/genesis/session-info', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->genesis_session
        ]);
    });
});
```

## Обработка ошибок

### 1. Глобальный обработчик исключений

Добавьте в `app/Exceptions/Handler.php`:

```php
use Streeboga\Genesis\Exceptions\ApiException;
use Streeboga\Genesis\Exceptions\ValidationException;

public function register()
{
    $this->renderable(function (ApiException $e, $request) {
        return response()->json([
            'success' => false,
            'message' => 'Genesis API Error: ' . $e->getMessage(),
            'code' => $e->getCode()
        ], 400);
    });

    $this->renderable(function (ValidationException $e, $request) {
        return response()->json([
            'success' => false,
            'message' => 'Genesis Validation Error',
            'errors' => $e->getErrors()
        ], 422);
    });
}
```

### 2. Retry логика для API вызовов

```php
use Illuminate\Support\Facades\Retry;

class GenesisService
{
    public function authenticateWithRetry(array $data): array
    {
        return Retry::times(3)
            ->sleep(1000) // 1 секунда между попытками
            ->when(function ($exception) {
                return $exception instanceof \GuzzleHttp\Exception\RequestException;
            })
            ->throw()
            ->call(function () use ($data) {
                return Genesis::auth()->authenticateByEmail($data);
            });
    }
}
```

## Тестирование

### 1. Unit тесты

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Streeboga\GenesisLaravel\Facades\Genesis;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GenesisAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_genesis_session()
    {
        // Мокаем Genesis API
        Genesis::shouldReceive('auth->authenticateByEmail')
            ->once()
            ->with([
                'email' => 'test@example.com',
                'project_uuid' => config('genesis.project_uuid'),
                'plan_uuid' => 'test-plan-uuid'
            ])
            ->andReturn([
                'success' => true,
                'data' => [
                    'session_token' => str_repeat('a', 64),
                    'user' => ['id' => 1, 'email' => 'test@example.com'],
                    'expires_at' => now()->addHours(2)->toISOString()
                ]
            ]);

        $user = \App\Models\User::factory()->create([
            'email' => 'test@example.com'
        ]);

        $service = app(\App\Services\GenesisSessionService::class);
        $result = $service->createUserSession($user, 'test-plan-uuid');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('session_token', $result['data']);
    }
}
```

### 2. Feature тесты

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Streeboga\GenesisLaravel\Facades\Genesis;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_checkout_session()
    {
        $user = User::factory()->create();

        Genesis::shouldReceive('auth->authenticateByEmail')
            ->andReturn([
                'success' => true,
                'data' => [
                    'session_token' => str_repeat('a', 64),
                    'expires_at' => now()->addHours(2)->toISOString()
                ]
            ]);

        Genesis::shouldReceive('auth->getPaymentUrl')
            ->andReturn([
                'success' => true,
                'data' => [
                    'checkout_url' => 'https://example.com/checkout'
                ]
            ]);

        $response = $this->actingAs($user)
            ->postJson('/checkout/create-session', [
                'plan_uuid' => 'test-plan-uuid'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'checkout_url' => 'https://example.com/checkout'
            ]);
    }
}
```

## Лучшие практики

### 1. Кеширование сессий

```php
// Кешируйте информацию о сессиях для быстрого доступа
Cache::remember("genesis_session_{$userId}", 3600, function () use ($sessionToken) {
    return Genesis::auth()->getSessionInfo($sessionToken);
});
```

### 2. Логирование операций

```php
// Логируйте все операции с Genesis API
Log::channel('genesis')->info('Session created', [
    'user_id' => $user->id,
    'session_token' => substr($sessionToken, 0, 8) . '...',
    'plan_uuid' => $planUuid
]);
```

### 3. Мониторинг производительности

```php
// Используйте Laravel Telescope или другие инструменты
\Illuminate\Support\Facades\DB::listen(function ($query) {
    if (str_contains($query->sql, 'genesis')) {
        Log::debug('Genesis related query', ['sql' => $query->sql]);
    }
});
```

### 4. Graceful degradation

```php
// Обрабатывайте недоступность Genesis API
try {
    $session = Genesis::auth()->authenticateByEmail($data);
} catch (\Exception $e) {
    // Fallback на локальную обработку или уведомление пользователя
    return $this->handleGenesisUnavailable($e);
}
```

## Заключение

User Auth API интегрируется в Laravel приложения через пакет `streeboga/genesis-laravel`, предоставляя простой и эффективный способ создания checkout сессий без повторной авторизации пользователей. Следуйте приведенным примерам и лучшим практикам для надежной интеграции.



