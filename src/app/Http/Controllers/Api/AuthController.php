<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse('The provided credentials are incorrect.', 401);
        }

        if (!$user->active) {
            return $this->errorResponse('Your account is inactive.', 403);
        }

        $allowedRoles = config('auth.login_guard.allowed_roles', ['ADMIN', 'STAFF']);
        if (!in_array($user->role, $allowedRoles, true)) {
            return $this->errorResponse('You are not authorized to access this portal.', 403);
        }

        Auth::login($user);

        return $this->successResponse([
            'user' => $user,
        ]);
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->successResponse([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        return $this->successResponse($request->user());
    }

    /**
     * Create API token for admin user
     */
    public function createToken(Request $request)
    {
        $request->validate([
            'token_name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        
        if ($user->role !== 'ADMIN') {
            return $this->errorResponse('Only admin users can create API tokens.', 403);
        }

        $token = $user->createToken($request->token_name, ['*']);

        return $this->successResponse([
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities,
        ]);
    }

    /**
     * List all tokens for the authenticated user
     */
    public function listTokens(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'ADMIN') {
            return $this->errorResponse('Only admin users can list API tokens.', 403);
        }

        $tokens = $user->tokens()->get()->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'created_at' => $token->created_at,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'deleted_at' => $token->deleted_at,
            ];
        });

        return $this->successResponse($tokens);
    }

    /**
     * Revoke a specific token
     */
    public function revokeToken(Request $request, $tokenId)
    {
        $user = $request->user();
        
        if ($user->role !== 'ADMIN') {
            return $this->errorResponse('Only admin users can revoke API tokens.', 403);
        }

        $token = $user->tokens()->find($tokenId);
        
        if (!$token) {
            return $this->errorResponse('Token not found.', 404);
        }

        $token->delete();

        return $this->successResponse([
            'message' => 'Token revoked successfully',
        ]);
    }
}
