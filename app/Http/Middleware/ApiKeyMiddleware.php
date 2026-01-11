<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Validasi Accept Header
        $acceptHeader = $request->header('Accept');
        
        if ($acceptHeader !== 'application/json') {
            return ApiResponse::badRequest(
                'Header Accept harus application/json'
            );
        }

        // 2. Validasi API Key
        $apiKey = $request->header('X-API-Key');
        $validApiKey = config('api.key', 'HumaCode2025');

        if (empty($apiKey)) {
            return ApiResponse::unauthorized('API Key tidak ditemukan');
        }

        if ($apiKey !== $validApiKey) {
            return ApiResponse::unauthorized('API Key tidak valid');
        }

        return $next($request);
    }
}
