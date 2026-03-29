<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'idempotent' => \App\Http\Middleware\IdempotencyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                $details = [];
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $details[] = [
                            'field' => $field,
                            'message' => $message,
                        ];
                    }
                }

                return response()->json([
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => 'Validation failed',
                        'details' => $details,
                    ],
                ], 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => [
                        'code' => 'unauthorized',
                        'message' => 'Unauthenticated.',
                    ],
                ], 401);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Resource not found.',
                    ],
                ], 404);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode();
                $code = match ($status) {
                    403 => 'forbidden',
                    404 => 'not_found',
                    409 => 'conflict',
                    422 => 'unprocessable_entity',
                    default => 'http_error',
                };

                return response()->json([
                    'error' => [
                        'code' => $code,
                        'message' => $e->getMessage() ?: 'An error occurred.',
                    ],
                ], $status);
            }
        });
        
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                // Prevent internal error details from leaking to external consumers.
                return response()->json([
                    'error' => [
                        'code' => 'internal_error',
                        'message' => config('app.debug') ? $e->getMessage() : 'Internal Server Error.',
                    ],
                ], 500);
            }
        });
    })->create();
