<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! $key) {
            return $next($request);
        }

        $cacheKey = "idempotency_key:{$key}";

        if ($cached = Cache::get($cacheKey)) {
            return response($cached['content'], $cached['status'], $cached['headers']);
        }

        $lockKey = "idempotency_lock:{$key}";
        $lock = Cache::lock($lockKey, 30);

        if (! $lock->get()) {
            return response()->json([
                'error' => [
                    'code' => 'conflict',
                    'message' => 'Idempotency key already being processed.',
                ]
            ], 409);
        }

        try {
            /** @var Response $response */
            $response = $next($request);

            // Enforce domain rules: Failure cases must not be cached to avoid poisoning.
            if ($response->isSuccessful()) {
                Cache::put($cacheKey, [
                    'status' => $response->getStatusCode(),
                    'headers' => $response->headers->all(),
                    'content' => $response->getContent(),
                ], now()->addHours(24));
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}
