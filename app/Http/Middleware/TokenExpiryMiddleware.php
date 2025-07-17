<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class TokenExpiryMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = $request->user()->currentAccessToken();

        // Check if token exists
        if (!$token) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // Check if token has expired
        if ($token->expires_at && $token->expires_at->isPast()) {
            // Delete expired token
            $token->delete();
            
            return response()->json([
                'error' => 'Token has expired',
                'expired_at' => $token->expires_at->toISOString(),
                'message' => 'Please login again'
            ], 401);
        }

        // Update last_used_at timestamp
        $token->forceFill(['last_used_at' => now()])->save();

        // Add expiry warning to response headers if token is expiring soon
        if ($token->expires_at) {
            $minutesRemaining = $token->expires_at->diffInMinutes(now());
            
            if ($minutesRemaining <= 30) {
                // Add warning headers
                $response = $next($request);
                $response->headers->set('X-Token-Expiring-Soon', 'true');
                $response->headers->set('X-Token-Expires-In', $minutesRemaining);
                $response->headers->set('X-Token-Expires-At', $token->expires_at->toISOString());
                
                return $response;
            }
        }

        return $next($request);
    }
}
