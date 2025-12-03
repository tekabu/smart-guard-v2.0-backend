<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserRfid;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class UserRfidController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = UserRfid::query()->with(['user']);
        
        // Apply user_id filter if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        
        $records = $query->get();
        return $this->successResponse($records);
    }

    public function count()
    {
        $count = UserRfid::count();
        return $this->successResponse(['count' => $count]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'card_id' => 'required|string|unique:user_rfids,card_id',
            'active' => 'boolean',
        ]);

        $record = UserRfid::create($validated);
        return $this->successResponse($record, 201);
    }

    public function show(string $id)
    {
        $record = UserRfid::query()->with(['user'])->findOrFail($id);
        return $this->successResponse($record);
    }

    public function showByCardId(string $cardId)
    {
        $record = UserRfid::query()
            ->with(['user'])
            ->where('card_id', $cardId)
            ->firstOrFail();

        return $this->successResponse($record);
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
        return $this->successResponse($record);
    }

    public function destroy(string $id)
    {
        $record = UserRfid::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
