<?php
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Force JSON for all API routes
        $middleware->api(prepend: [ForceJsonResponse::class]);

        // CORS for all routes
        $middleware->prepend(CorsMiddleware::class);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Return JSON for 404 on API routes
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Resource tidak ditemukan.'], 404);
            }
        });

        // Return JSON for validation errors on API routes
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Data yang diberikan tidak valid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });
    })
    ->create();
