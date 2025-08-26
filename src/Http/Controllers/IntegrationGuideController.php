<?php

namespace Streeboga\GenesisLaravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use App\Models\Project;

class IntegrationGuideController extends Controller
{
    /**
     * UUID для доступа к инструкции
     * Можно изменить на любой другой UUID
     */
    private const DEFAULT_GUIDE_UUID = '7f3b9c4a-8e5d-4a2b-9c1e-3d7a5b9c4f8e';
    
    /**
     * Показать инструкцию по интеграции с реальными кредами
     */
    public function show(Request $request, string $uuid)
    {
        // Ищем проект по UUID в базе
        $project = Project::query()->where('uuid', $uuid)->first();
        if (!$project) {
            abort(404, 'Project not found');
        }

        // Собираем креды. API-ключ берём из конфигурации, проектные данные — из БД
        $credentials = [
            'api_key' => config('genesis.api_key', env('GENESIS_API_KEY', 'test_api_key_xxxxxxxxxxxxx')),
            'base_url' => config('genesis.base_url', env('GENESIS_BASE_URL', 'https://api.genesis.com/v1/')),
            'project_uuid' => $project->uuid,
            'project_name' => $project->name,
            'project_domain' => $project->domain,
            'webhook_secret' => env('GENESIS_WEBHOOK_SECRET', 'webhook_secret_key_xxxxx'),
            
            // Тестовые данные
            'test_user_uuid' => env('GENESIS_TEST_USER_UUID', '123e4567-e89b-12d3-a456-426614174000'),
            'test_user_email' => env('GENESIS_TEST_USER_EMAIL', 'test@example.com'),
            'test_plan_uuid' => env('GENESIS_TEST_PLAN_UUID', '987f6543-e21c-34d5-b678-987654321000'),
        ];

        // Формат вывода
        $format = $request->get('format', 'html');

        // Если доступен шаблон INTEGRATION_GUIDE.md — используем его в качестве источника
        $guidePath = __DIR__ . '/../../INTEGRATION_GUIDE.md';
        if (is_file($guidePath) && is_readable($guidePath)) {
            $prefix = <<<MD
# Genesis Integration Guide (Dynamic Credentials)

```env
# Genesis API Credentials
GENESIS_API_KEY={$credentials['api_key']}
GENESIS_BASE_URL={$credentials['base_url']}
GENESIS_PROJECT_UUID={$credentials['project_uuid']}
GENESIS_WEBHOOK_SECRET={$credentials['webhook_secret']}

# Test Data
GENESIS_TEST_USER_UUID={$credentials['test_user_uuid']}
GENESIS_TEST_USER_EMAIL={$credentials['test_user_email']}
GENESIS_TEST_PLAN_UUID={$credentials['test_plan_uuid']}
```
MD;

            $template = (string) file_get_contents($guidePath);
            $markdown = trim($prefix) . "\n\n" . $template;

            if ($format === 'json') {
                return response()->json([
                    'guide_uuid' => $credentials['project_uuid'],
                    'credentials' => $credentials,
                    'guide_markdown' => $markdown,
                ]);
            }

            if ($format === 'markdown') {
                return response($markdown)->header('Content-Type', 'text/markdown; charset=utf-8');
            }

            if ($format === 'text') {
                return response(strip_tags(Str::markdown($markdown)))->header('Content-Type', 'text/plain; charset=utf-8');
            }

            return response(Str::markdown($markdown))->header('Content-Type', 'text/html; charset=utf-8');
        }

        // Fallback на встроенные генераторы
        if ($format === 'json') {
            return $this->jsonResponse($credentials);
        }

        if ($format === 'text') {
            return $this->textResponse($credentials);
        }

        if ($format === 'markdown') {
            return $this->markdownResponse($credentials);
        }

        return $this->htmlResponse($credentials);
    }

    /**
     * HTML формат инструкции
     */
    private function htmlResponse(array $credentials)
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Genesis Integration Guide</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        .credentials {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .credential-item {
            margin: 10px 0;
            display: flex;
            align-items: center;
        }
        .credential-label {
            font-weight: bold;
            width: 200px;
            color: #495057;
        }
        .credential-value {
            font-family: 'Courier New', monospace;
            background: #fff;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            flex: 1;
            word-break: break-all;
        }
        .code-block {
            background: #282c34;
            color: #abb2bf;
            padding: 20px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 20px 0;
        }
        .code-block pre {
            margin: 0;
            font-family: 'Courier New', monospace;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .success {
            background: #d4edda;
            border: 1px solid #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .copy-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 12px;
        }
        .copy-btn:hover {
            background: #0056b3;
        }
        .section {
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Genesis Integration Guide</h1>
        <p><strong>UUID доступа:</strong> <code>{$credentials['project_uuid']}</code></p>
        
        <div class="warning">
            ⚠️ <strong>Внимание:</strong> Эта страница содержит реальные API ключи. Не делитесь этой ссылкой публично!
        </div>

        <div class="section">
            <h2>📋 Текущие креды</h2>
            <div class="credentials">
                <div class="credential-item">
                    <span class="credential-label">API Key:</span>
                    <span class="credential-value">{$credentials['api_key']}</span>
                    <button class="copy-btn" onclick="copyToClipboard('{$credentials['api_key']}')">Копировать</button>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Base URL:</span>
                    <span class="credential-value">{$credentials['base_url']}</span>
                    <button class="copy-btn" onclick="copyToClipboard('{$credentials['base_url']}')">Копировать</button>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Project UUID:</span>
                    <span class="credential-value">{$credentials['project_uuid']}</span>
                    <button class="copy-btn" onclick="copyToClipboard('{$credentials['project_uuid']}')">Копировать</button>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Project Name:</span>
                    <span class="credential-value">{$credentials['project_name']}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Domain:</span>
                    <span class="credential-value">{$credentials['project_domain']}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Webhook Secret:</span>
                    <span class="credential-value">{$credentials['webhook_secret']}</span>
                    <button class="copy-btn" onclick="copyToClipboard('{$credentials['webhook_secret']}')">Копировать</button>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>🧪 Тестовые данные</h2>
            <div class="credentials">
                <div class="credential-item">
                    <span class="credential-label">Test User UUID:</span>
                    <span class="credential-value">{$credentials['test_user_uuid']}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Test User Email:</span>
                    <span class="credential-value">{$credentials['test_user_email']}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Test Plan UUID:</span>
                    <span class="credential-value">{$credentials['test_plan_uuid']}</span>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>🚀 Быстрый старт</h2>
            
            <h3>1. Установка в .env файл</h3>
            <div class="code-block">
                <pre># Genesis API Credentials
GENESIS_API_KEY={$credentials['api_key']}
GENESIS_BASE_URL={$credentials['base_url']}
GENESIS_PROJECT_UUID={$credentials['project_uuid']}
GENESIS_WEBHOOK_SECRET={$credentials['webhook_secret']}

# Test Data
GENESIS_TEST_USER_UUID={$credentials['test_user_uuid']}
GENESIS_TEST_USER_EMAIL={$credentials['test_user_email']}
GENESIS_TEST_PLAN_UUID={$credentials['test_plan_uuid']}</pre>
            </div>
            <button class="copy-btn" onclick="copyEnvConfig()">Копировать весь .env блок</button>

            <h3>2. Тестовый код</h3>
            <div class="code-block">
                <pre>&lt;?php
use Streeboga\GenesisLaravel\Facades\Genesis;

// Проверка подключения
\$projectId = '{$credentials['project_uuid']}';
\$plans = Genesis::billing()->listPlans(\$projectId);
dd(\$plans);

// Тест авторизации
\$response = Genesis::auth()->sendOtp([
    'email' => '{$credentials['test_user_email']}',
    'project_uuid' => \$projectId
]);
dd(\$response);</pre>
            </div>
        </div>

        <div class="section">
            <h2>📚 Форматы вывода</h2>
            <p>Доступны следующие форматы:</p>
            <ul>
                <li><a href="?format=json">JSON формат</a> - для программного доступа</li>
                <li><a href="?format=text">Текстовый формат</a> - для копирования</li>
                <li><a href="?format=markdown">Markdown формат</a> - для документации</li>
                <li><strong>HTML формат</strong> - текущий вид</li>
            </ul>
        </div>

        <div class="success">
            ✅ Инструкция сгенерирована автоматически из текущих настроек окружения
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Скопировано в буфер обмена!');
            });
        }

        function copyEnvConfig() {
            const envConfig = `# Genesis API Credentials
GENESIS_API_KEY={$credentials['api_key']}
GENESIS_BASE_URL={$credentials['base_url']}
GENESIS_PROJECT_UUID={$credentials['project_uuid']}
GENESIS_WEBHOOK_SECRET={$credentials['webhook_secret']}

# Test Data
GENESIS_TEST_USER_UUID={$credentials['test_user_uuid']}
GENESIS_TEST_USER_EMAIL={$credentials['test_user_email']}
GENESIS_TEST_PLAN_UUID={$credentials['test_plan_uuid']}`;
            
            navigator.clipboard.writeText(envConfig).then(() => {
                alert('ENV конфигурация скопирована в буфер обмена!');
            });
        }
    </script>
</body>
</html>
HTML;

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * JSON формат инструкции
     */
    private function jsonResponse(array $credentials)
    {
        return response()->json([
            'guide_uuid' => $credentials['project_uuid'],
            'credentials' => $credentials,
            'quick_start' => [
                'test_connection' => "Genesis::billing()->listPlans('{$credentials['project_uuid']}')",
                'send_otp' => "Genesis::auth()->sendOtp(['email' => '{$credentials['test_user_email']}', 'project_uuid' => '{$credentials['project_uuid']}'])",
            ],
            'formats' => [
                'html' => url("/genesis/guide/" . $credentials['project_uuid']),
                'json' => url("/genesis/guide/" . $credentials['project_uuid'] . "?format=json"),
                'text' => url("/genesis/guide/" . $credentials['project_uuid'] . "?format=text"),
                'markdown' => url("/genesis/guide/" . $credentials['project_uuid'] . "?format=markdown"),
            ]
        ], 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Текстовый формат инструкции
     */
    private function textResponse(array $credentials)
    {
        $text = <<<TEXT
============================================================
GENESIS LARAVEL INTEGRATION (PROJECT: {$credentials['project_name']} | {$credentials['project_domain']})
============================================================
PROJECT_UUID={$credentials['project_uuid']}

1) УСТАНОВКА ПАКЕТА (composer):
   - composer require streeboga/genesis-laravel
   - php artisan vendor:publish --tag=config --provider="Streeboga\\GenesisLaravel\\GenesisServiceProvider"
   - php artisan vendor:publish --tag=migrations --provider="Streeboga\\GenesisLaravel\\GenesisServiceProvider"
   - php artisan migrate

2) .ENV (вставьте и заполните при необходимости):
   # Genesis API Credentials
   GENESIS_API_KEY={$credentials['api_key']}
   GENESIS_BASE_URL={$credentials['base_url']}
   GENESIS_PROJECT_UUID={$credentials['project_uuid']}
   GENESIS_WEBHOOK_SECRET={$credentials['webhook_secret']}

   # Cache (опционально)
   GENESIS_CACHE_ENABLED=true
   GENESIS_CACHE_TTL=3600
   GENESIS_CACHE_PREFIX=genesis:

   # Queue (опционально)
   GENESIS_QUEUE_CONNECTION=default
   GENESIS_WEBHOOK_QUEUE=genesis-webhooks
   GENESIS_SYNC_QUEUE=genesis-sync

3) ПРОВЕРКА ПОДКЛЮЧЕНИЯ:
   - php artisan genesis:test-connection

4) ИСПОЛЬЗОВАНИЕ В КОДЕ (Laravel):
   // Фасад
   use Streeboga\\GenesisLaravel\\Facades\\Genesis;
   \$plans = Genesis::billing()->listPlans('{$credentials['project_uuid']}');

   // Через DI
   use Streeboga\\Genesis\\GenesisClient;
   public function __construct(private GenesisClient \$genesis) {}
   // \$this->genesis->billing->listPlans('{$credentials['project_uuid']}');

5) ROUTES (Webhook + защита):
   // routes/api.php
   use Streeboga\\GenesisLaravel\\Jobs\\ProcessGenesisWebhook;
   Route::post('/webhooks/genesis', function (Illuminate\\Http\\Request \$r) {
       ProcessGenesisWebhook::dispatch(\$r->all());
       return response()->json(['status' => 'accepted']);
   });

   // Защита маршрутов токеном
   Route::middleware('genesis.auth')->group(function () {
       Route::get('/protected', fn() => 'ok');
   });

6) BLADE ДИРЕКТИВЫ:
   @genesisAuth(
       request()->bearerToken()
   ) Авторизован @endgenesisAuth

   @genesisFeature('api-calls') Доступ к api-calls @endgenesisFeature

7) ПРИМЕРЫ API (быстрый старт):
   // OTP
   Genesis::auth()->sendOtp(['email' => '{$credentials['test_user_email']}', 'project_uuid' => '{$credentials['project_uuid']}']);
   // Billing
   Genesis::billing()->createSubscription('{$credentials['project_uuid']}', ['user_uuid' => '{$credentials['test_user_uuid']}', 'plan_uuid' => '{$credentials['test_plan_uuid']}']);
   // Features
   Genesis::features()->consume('{$credentials['project_uuid']}', '{$credentials['test_user_uuid']}', 'api-calls', 10);

8) ТЕСТОВЫЕ ДАННЫЕ:
   TEST_USER_UUID={$credentials['test_user_uuid']}
   TEST_USER_EMAIL={$credentials['test_user_email']}
   TEST_PLAN_UUID={$credentials['test_plan_uuid']}

9) ФОРМАТЫ ВЫВОДА ИНСТРУКЦИИ:
   - HTML:     /genesis/guide/{$credentials['project_uuid']}
   - TEXT:     /genesis/guide/{$credentials['project_uuid']}?format=text
   - JSON:     /genesis/guide/{$credentials['project_uuid']}?format=json
   - MARKDOWN: /genesis/guide/{$credentials['project_uuid']}?format=markdown

============================================================
TEXT;

        return response($text)->header('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * Markdown формат инструкции
     */
    private function markdownResponse(array $credentials)
    {
        $markdown = <<<MARKDOWN
# Genesis Integration Guide

**Guide UUID:** `{$credentials['project_uuid']}`

## 🔐 Credentials

| Parameter | Value |
|-----------|-------|
| **API Key** | `{$credentials['api_key']}` |
| **Base URL** | `{$credentials['base_url']}` |
| **Project UUID** | `{$credentials['project_uuid']}` |
| **Webhook Secret** | `{$credentials['webhook_secret']}` |

## 🧪 Test Data

| Parameter | Value |
|-----------|-------|
| **Test User UUID** | `{$credentials['test_user_uuid']}` |
| **Test User Email** | `{$credentials['test_user_email']}` |
| **Test Plan UUID** | `{$credentials['test_plan_uuid']}` |

## 📋 ENV Configuration

```env
# Genesis API Credentials
GENESIS_API_KEY={$credentials['api_key']}
GENESIS_BASE_URL={$credentials['base_url']}
GENESIS_PROJECT_UUID={$credentials['project_uuid']}
GENESIS_WEBHOOK_SECRET={$credentials['webhook_secret']}

# Test Data
GENESIS_TEST_USER_UUID={$credentials['test_user_uuid']}
GENESIS_TEST_USER_EMAIL={$credentials['test_user_email']}
GENESIS_TEST_PLAN_UUID={$credentials['test_plan_uuid']}
```

## 🚀 Quick Start

```php
use Streeboga\GenesisLaravel\Facades\Genesis;

// Test connection
\$projectId = '{$credentials['project_uuid']}';
\$plans = Genesis::billing()->listPlans(\$projectId);

// Test auth
\$response = Genesis::auth()->sendOtp([
    'email' => '{$credentials['test_user_email']}',
    'project_uuid' => \$projectId
]);
```

---
*Generated automatically from current environment settings*
MARKDOWN;

        return response($markdown)->header('Content-Type', 'text/markdown; charset=utf-8');
    }

    /**
     * Получить UUID для доступа к инструкции
     */
    // getGuideUuid/isAllowedUuid удалены — теперь UUID берём из БД (Project)
}
