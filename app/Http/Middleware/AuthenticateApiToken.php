<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'API token is required. Include it in the Authorization header as: Bearer YOUR_TOKEN'
            ], 401);
        }

        $hashedToken = hash('sha256', $token);
        $apiToken = ApiToken::where('token', $hashedToken)->first();

        if (!$apiToken) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API token'
            ], 401);
        }

        // Mark token as used
        $apiToken->markAsUsed();

        return $next($request);
    }
}
