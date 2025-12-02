<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->get('role'));
        }

        $records = $query->get();
        return $this->successResponse($records);
    }

    public function count()
    {
        $count = User::count();
        return $this->successResponse(['count' => $count]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => 'nullable|string|min:8|required_unless:role,STUDENT,FACULTY',
            'role' => 'required|in:ADMIN,STAFF,STUDENT,FACULTY',
            'active' => 'boolean',
            'student_id' => [
                'nullable',
                'string',
                Rule::unique('users', 'student_id')->where(fn ($query) => $query->where('role', 'STUDENT')),
            ],
            'faculty_id' => [
                'nullable',
                'string',
                Rule::unique('users', 'faculty_id')->where(fn ($query) => $query->where('role', 'FACULTY')),
            ],
            'course' => 'nullable|string',
            'year_level' => 'nullable|integer',
            'attendance_rate' => 'nullable|numeric',
            'department' => 'nullable|string',
            'clearance' => 'nullable|boolean',
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
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'role' => 'sometimes|in:ADMIN,STAFF,STUDENT,FACULTY',
            'active' => 'sometimes|boolean',
            'student_id' => [
                'nullable',
                'string',
                Rule::unique('users', 'student_id')
                    ->ignore($id)
                    ->where(fn ($query) => $query->where('role', 'STUDENT')),
            ],
            'faculty_id' => [
                'nullable',
                'string',
                Rule::unique('users', 'faculty_id')
                    ->ignore($id)
                    ->where(fn ($query) => $query->where('role', 'FACULTY')),
            ],
            'course' => 'nullable|string',
            'year_level' => 'nullable|integer',
            'attendance_rate' => 'nullable|numeric',
            'department' => 'nullable|string',
            'clearance' => 'sometimes|boolean',
        ];

        // Only add password validation rules if password is provided
        if ($request->filled('password')) {
            $updateRules['password'] = 'required|string|min:8';
            $updateRules['password_confirmation'] = 'required|string|same:password';
        }

        $validated = $request->validate($updateRules);

        // Only hash and update password if it's provided and not empty
        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            // Remove password from validated data to retain old password
            unset($validated['password']);
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
