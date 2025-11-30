<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceBoard;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DeviceCommunicationController extends Controller
{
    use ApiResponse;

    /**
     * Allow an authenticated device board to report its status and update metadata.
     */
    public function heartbeat(Request $request)
    {
        /** @var DeviceBoard $deviceBoard */
        $deviceBoard = $request->user();

        $validated = $request->validate([
            'firmware_version' => 'nullable|string|max:255',
        ]);

        if (array_key_exists('firmware_version', $validated)) {
            $deviceBoard->firmware_version = $validated['firmware_version'];
        }

        $deviceBoard->last_seen_at = now();
        $deviceBoard->last_ip = $request->ip();
        $deviceBoard->save();

        return $this->successResponse([
            'board' => $deviceBoard->fresh()->load('device'),
        ]);
    }

    /**
     * Fetch the authenticated device board profile.
     */
    public function me(Request $request)
    {
        /** @var DeviceBoard $deviceBoard */
        $deviceBoard = $request->user()->load('device');

        return $this->successResponse($deviceBoard);
    }
}
