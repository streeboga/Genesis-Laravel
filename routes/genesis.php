<?php

use Illuminate\Support\Facades\Route;
use Streeboga\GenesisLaravel\Http\Controllers\IntegrationGuideController;

/*
|--------------------------------------------------------------------------
| Genesis Package Routes
|--------------------------------------------------------------------------
|
| Роуты для просмотра инструкции по интеграции с реальными кредами
| Доступ по UUID для безопасности
|
*/

// Роут для просмотра инструкции по UUID
// Доступные форматы: html (по умолчанию), json, text, markdown
// Примеры:
// - /genesis/guide/7f3b9c4a-8e5d-4a2b-9c1e-3d7a5b9c4f8e
// - /genesis/guide/7f3b9c4a-8e5d-4a2b-9c1e-3d7a5b9c4f8e?format=json
// - /genesis/guide/7f3b9c4a-8e5d-4a2b-9c1e-3d7a5b9c4f8e?format=text
// - /genesis/guide/7f3b9c4a-8e5d-4a2b-9c1e-3d7a5b9c4f8e?format=markdown
Route::get('/genesis/guide/{uuid}', [IntegrationGuideController::class, 'show'])
    ->name('genesis.guide')
    ->where('uuid', '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}');

// Альтернативный роут через API префикс
Route::prefix('api/genesis')->group(function () {
    Route::get('/integration/{uuid}', [IntegrationGuideController::class, 'show'])
        ->name('genesis.api.integration')
        ->where('uuid', '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}');
});
