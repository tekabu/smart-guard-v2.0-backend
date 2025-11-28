<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserRfid;
use Illuminate\Http\Request;

class UserRfidController extends Controller
{
    public function index()
    {
        $records = UserRfid::query()->with(['user'])->get();
        return response()->json($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'card_id' => 'required|string|unique:user_rfids,card_id',
            'active' => 'boolean',
        ]);

        $record = UserRfid::create($validated);
        return response()->json($record, 201);
    }

    public function show(string $id)
    {
        $record = UserRfid::query()->with(['user'])->findOrFail($id);
        return response()->json($record);
    }

    public function update(Request $request, string $id)
    {
        $record = UserRfid::findOrFail($id);

        $updateRules = [
            'user_id' => 'sometimes|exists:users,id',
            'card_id' => 'sometimes|string|unique:user_rfids,card_id,{id}',
            'active' => 'sometimes|boolean',
        ];

        // Replace {id} placeholder in unique rules
        foreach ($updateRules as $field => $rule) {
            $updateRules[$field] = str_replace('{id}', $id, $rule);
        }

        $validated = $request->validate($updateRules);

        $record->update($validated);
        return response()->json($record);
    }

    public function destroy(string $id)
    {
        $record = UserRfid::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
