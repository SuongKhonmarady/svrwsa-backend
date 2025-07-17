<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'remember_me' => ['boolean'] // Optional: for extended sessions
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Clear existing tokens for security (optional - force single session)
        // $user->tokens()->delete();

        // Set token expiry based on remember_me option
        $tokenExpiry = $this->getTokenExpiry($request->get('remember_me', false));
        
        $token = $user->createToken(
            'auth-token', 
            ['*'], 
            $tokenExpiry
        )->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'expires_at' => $tokenExpiry->toISOString(),
            'expires_in_minutes' => $tokenExpiry->diffInMinutes(now()),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_display' => $user->getRoleDisplayName()
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
            'logged_out_at' => now()->toISOString()
        ]);
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAll(Request $request)
    {
        $tokenCount = $request->user()->tokens()->count();
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices',
            'revoked_tokens' => $tokenCount,
            'logged_out_at' => now()->toISOString()
        ]);
    }

    /**
     * Refresh token (extend expiry)
     */
    public function refreshToken(Request $request)
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();

        // Check if token is close to expiry (within 30 minutes)
        if ($currentToken->expires_at && $currentToken->expires_at->diffInMinutes(now()) > 30) {
            return response()->json([
                'message' => 'Token still valid, no refresh needed',
                'expires_at' => $currentToken->expires_at->toISOString(),
                'expires_in_minutes' => $currentToken->expires_at->diffInMinutes(now())
            ]);
        }

        // Delete current token
        $currentToken->delete();

        // Create new token with extended expiry
        $tokenExpiry = $this->getTokenExpiry(false); // Default expiry
        $newToken = $user->createToken(
            'auth-token-refreshed',
            ['*'],
            $tokenExpiry
        )->plainTextToken;

        return response()->json([
            'message' => 'Token refreshed successfully',
            'token' => $newToken,
            'expires_at' => $tokenExpiry->toISOString(),
            'expires_in_minutes' => $tokenExpiry->diffInMinutes(now())
        ]);
    }

    /**
     * Check token status
     */
    public function tokenStatus(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        $now = now();

        $timeRemaining = $token->expires_at ? $token->expires_at->diffInMinutes($now) : null;
        $isExpiringSoon = $timeRemaining && $timeRemaining <= 30;

        return response()->json([
            'token_name' => $token->name,
            'created_at' => $token->created_at->toISOString(),
            'expires_at' => $token->expires_at?->toISOString(),
            'expires_in_minutes' => $timeRemaining,
            'is_expiring_soon' => $isExpiringSoon,
            'last_used_at' => $token->last_used_at?->toISOString(),
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'role' => $request->user()->role
            ]
        ]);
    }

    /**
     * Get token expiry time based on options
     */
    private function getTokenExpiry(bool $rememberMe = false): Carbon
    {
        if ($rememberMe) {
            // Extended session for "remember me" - 30 days
            return now()->addDays(Config::get('sanctum.remember_me_expiry', 30));
        }

        // Regular session - configurable, default 8 hours
        return now()->addHours(Config::get('sanctum.token_expiry_hours', 8));
    }

    /**
     * Clean up expired tokens (can be called via cron job)
     */
    public function cleanupExpiredTokens()
    {
        $expiredCount = \Laravel\Sanctum\PersonalAccessToken::where('expires_at', '<', now())->count();
        \Laravel\Sanctum\PersonalAccessToken::where('expires_at', '<', now())->delete();

        return response()->json([
            'message' => 'Expired tokens cleaned up',
            'deleted_tokens' => $expiredCount,
            'cleaned_at' => now()->toISOString()
        ]);
    }
}

