<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestTiming
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $dbTimeMs = 0.0;
        $dbCount = 0;

        DB::listen(function ($query) use (&$dbTimeMs, &$dbCount): void {
            $dbCount++;
            $dbTimeMs += $query->time;
        });

        $startHandle = microtime(true);
        $response = $next($request);
        $endHandle = microtime(true);

        $startTotal = defined('LARAVEL_START')
            ? LARAVEL_START
            : (float) $request->server('REQUEST_TIME_FLOAT', $startHandle);
        $bootMs = max(0, ($startHandle - $startTotal) * 1000);
        $totalMs = (microtime(true) - $startTotal) * 1000;
        $handleMs = ($endHandle - $startHandle) * 1000;

        Log::info('request_timing', [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'status' => $response->getStatusCode(),
            'total_ms' => (int) round($totalMs),
            'boot_ms' => (int) round($bootMs),
            'handle_ms' => (int) round($handleMs),
            'db_time_ms' => (int) round($dbTimeMs),
            'db_queries' => $dbCount,
        ]);

        return $response;
    }
}


