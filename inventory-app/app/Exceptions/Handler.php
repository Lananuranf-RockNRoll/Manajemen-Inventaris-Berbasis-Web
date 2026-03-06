<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        // Force JSON for API routes
        if ($request->is('api/*') || $request->expectsJson()) {

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'Data yang diberikan tidak valid.',
                    'errors'  => $e->errors(),
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Tidak terautentikasi. Silakan login.',
                ], 401);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'message' => 'Data yang diminta tidak ditemukan.',
                ], 404);
            }

            if (config('app.debug')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTrace(),
                ], 500);
            }

            return response()->json([
                'message' => 'Terjadi kesalahan pada server.',
            ], 500);
        }

        return parent::render($request, $e);
    }
}
