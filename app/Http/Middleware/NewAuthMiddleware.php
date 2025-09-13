<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;

class NewAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasHeader('Authorization')) {
            $header = $request->header('Authorization');
            $token  = str_replace('Bearer ', '', $header);

            $dataRequest = [
                'refresh_token' => $token,
            ];

            $response = Http::asForm()->post(
                env('UNSURYA_MIDDLEWARE_API') . '/refresh',
                $dataRequest
            );

            // cek status HTTP dari middleware API
            if ($response->failed()) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'error'   => $response->json(),
                ], 401);
            }

            return $next($request);
        }

        return response()->json([
            'message' => 'Authorization header missing',
        ], 401);
    }
}
