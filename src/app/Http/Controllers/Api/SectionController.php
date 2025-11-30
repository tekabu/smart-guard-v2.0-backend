<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $records = Section::query()->get();
        return $this->successResponse($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections', 'section'),
            ],
        ]);

        $record = Section::create($validated);
        return $this->successResponse($record, 201);
    }

    public function show(string $id)
    {
        $record = Section::findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = Section::findOrFail($id);

        $validated = $request->validate([
            'section' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('sections', 'section')->ignore($id),
            ],
        ]);

        $record->update($validated);
        return $this->successResponse($record);
    }

    public function destroy(string $id)
    {
        $record = Section::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
