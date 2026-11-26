<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        $fail = fn(string $message, int $status, $errors = null) => response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);

        $exceptions->render(function (ValidationException $e, Request $request) use ($fail) {
            if ($request->is('api/*')) {
                return $fail('Data yang dikirim tidak valid.', 422, $e->errors());
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($fail) {
            if ($request->is('api/*')) {
                return $fail('Belum login atau token tidak valid.', 401);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($fail) {
            if ($request->is('api/*')) {
                return $fail('Data tidak ditemukan.', 404);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) use ($fail) {
            if ($request->is('api/*')) {
                return $fail('Terlalu banyak percobaan. Coba lagi nanti.', 429);
            }
        });
    })->create();
