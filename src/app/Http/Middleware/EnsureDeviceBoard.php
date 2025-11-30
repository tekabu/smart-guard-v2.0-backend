<?php

namespace App\Http\Middleware;

use App\Models\DeviceBoard;
use Closure;
use Illuminate\Http\Request;

class EnsureDeviceBoard
{
    /**
     * Ensure the authenticated Sanctum token belongs to a device board.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user instanceof DeviceBoard) {
            return response()->json([
                'status' => false,
                'message' => 'This action is unauthorized.',
            ], 403);
        }

        return $next($request);
    }
}
