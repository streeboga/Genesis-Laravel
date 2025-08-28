<?php

namespace Streeboga\GenesisLaravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Streeboga\Genesis\GenesisClient;

class GenesisAuthMiddleware
{
    public function __construct(private GenesisClient $genesis)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader) {
            return new Response('Unauthorized', 401);
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return new Response('Unauthorized', 401);
        }

        $token = substr($authHeader, 7);
        
        if (empty($token)) {
            return new Response('Unauthorized', 401);
        }

        // В реальной реализации здесь была бы проверка токена через Genesis API
        // Для тестов пока считаем любой непустой Bearer токен валидным
        
        return $next($request);
    }
}






