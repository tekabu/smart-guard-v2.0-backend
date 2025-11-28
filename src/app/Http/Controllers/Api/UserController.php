<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $records = User::query()->get();
        return $this->successResponse($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:ADMIN,STAFF,STUDENT,FACULTY',
            'active' => 'boolean',
            'student_id' => 'nullable|string',
            'faculty_id' => 'nullable|string',
            'course' => 'nullable|string',
            'year_level' => 'nullable|integer',
            'attendance_rate' => 'nullable|numeric',
            'department' => 'nullable|string',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $record = User::create($validated);
        return $this->successResponse($record, 201);
    }

    public function show(string $id)
    {
        $record = User::query()->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = User::findOrFail($id);

        $updateRules = [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,{id}',
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:ADMIN,STAFF,STUDENT,FACULTY',
            'active' => 'sometimes|boolean',
            'student_id' => 'nullable|string',
            'faculty_id' => 'nullable|string',
            'course' => 'nullable|string',
            'year_level' => 'nullable|integer',
            'attendance_rate' => 'nullable|numeric',
            'department' => 'nullable|string',
        ];

        // Replace {id} placeholder in unique rules
        foreach ($updateRules as $field => $rule) {
            $updateRules[$field] = str_replace('{id}', $id, $rule);
        }

        $validated = $request->validate($updateRules);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $record->update($validated);
        return $this->successResponse($record);
    }

    public function destroy(string $id)
    {
        $record = User::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
