<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    private const ALLOWED_ORIGINS = ['*'];
    private const ALLOWED_METHODS = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
    private const ALLOWED_HEADERS = 'Content-Type, Authorization, X-Requested-With, Accept';

    public function handle(Request $request, Closure $next): Response
    {
        // Respond immediately to preflight OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', self::ALLOWED_METHODS)
                ->header('Access-Control-Allow-Headers', self::ALLOWED_HEADERS)
                ->header('Access-Control-Max-Age', '86400');
        }

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', self::ALLOWED_METHODS);
        $response->headers->set('Access-Control-Allow-Headers', self::ALLOWED_HEADERS);

        return $response;
    }
}
