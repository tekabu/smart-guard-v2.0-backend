<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserFingerprint;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class UserFingerprintController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $records = UserFingerprint::query()->with(['user'])->get();
        return $this->successResponse($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fingerprint_id' => 'required|integer|unique:user_fingerprints,fingerprint_id',
            'active' => 'boolean',
        ]);

        $record = UserFingerprint::create($validated);
        return $this->successResponse($record, 201);
    }

    public function show(string $id)
    {
        $record = UserFingerprint::query()->with(['user'])->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = UserFingerprint::findOrFail($id);

        $updateRules = [
            'user_id' => 'sometimes|exists:users,id',
            'fingerprint_id' => 'sometimes|integer|unique:user_fingerprints,fingerprint_id,{id}',
            'active' => 'sometimes|boolean',
        ];

        // Replace {id} placeholder in unique rules
        foreach ($updateRules as $field => $rule) {
            $updateRules[$field] = str_replace('{id}', $id, $rule);
        }

        $validated = $request->validate($updateRules);

        $record->update($validated);
        return $this->successResponse($record);
    }

    public function destroy(string $id)
    {
        $record = UserFingerprint::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
