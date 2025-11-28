<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class SubjectController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $records = Subject::query()->get();
        return $this->successResponse($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|unique:subjects,subject',
            'active' => 'boolean',
        ]);

        $record = Subject::create($validated);
        return $this->successResponse($record, 201);
    }

    public function show(string $id)
    {
        $record = Subject::query()->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = Subject::findOrFail($id);

        $updateRules = [
            'subject' => 'sometimes|string|unique:subjects,subject,{id}',
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
        $record = Subject::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
