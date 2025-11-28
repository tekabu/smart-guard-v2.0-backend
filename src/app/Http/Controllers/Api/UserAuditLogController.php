<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAuditLog;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class UserAuditLogController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $records = UserAuditLog::query()->with(['user'])->get();
        return $this->successResponse($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'description' => 'required|string',
        ]);

        $record = UserAuditLog::create($validated);
        return $this->successResponse($record, 201);
    }

    public function show(string $id)
    {
        $record = UserAuditLog::query()->with(['user'])->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = UserAuditLog::findOrFail($id);

        $updateRules = [
            'user_id' => 'sometimes|exists:users,id',
            'description' => 'sometimes|string',
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
        $record = UserAuditLog::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
