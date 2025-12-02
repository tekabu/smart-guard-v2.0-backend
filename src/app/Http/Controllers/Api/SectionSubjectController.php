<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SectionSubject;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SectionSubjectController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $records = SectionSubject::with(['section', 'subject', 'faculty'])
            ->when($request->filled('section_id'), fn ($query) => $query->where('section_id', $request->input('section_id')))
            ->when($request->filled('subject_id'), fn ($query) => $query->where('subject_id', $request->input('subject_id')))
            ->get();
        return $this->successResponse($records);
    }

    public function options()
    {
        $options = SectionSubject::with(['section', 'subject', 'faculty'])
            ->get()
            ->map(fn ($record) => [
                'id' => $record->id,
                'label' => sprintf(
                    '%s - %s - %s',
                    $record->section->section ?? 'N/A',
                    $record->subject->subject ?? 'N/A',
                    $record->faculty->name ?? 'N/A'
                ),
            ]);

        return $this->successResponse($options);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => ['required', 'exists:sections,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'faculty_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'FACULTY')),
            ],
        ]);
        $this->ensureUniqueCombination($validated);

        $record = SectionSubject::create($validated);
        return $this->successResponse($record->load(['section', 'subject', 'faculty']), 201);
    }

    public function show(string $id)
    {
        $record = SectionSubject::with(['section', 'subject', 'faculty'])->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = SectionSubject::findOrFail($id);

        $validated = $request->validate([
            'section_id' => ['sometimes', 'exists:sections,id'],
            'subject_id' => ['sometimes', 'exists:subjects,id'],
            'faculty_id' => [
                'sometimes',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'FACULTY')),
            ],
        ]);

        $data = array_merge($record->only(['section_id', 'subject_id', 'faculty_id']), $validated);
        $this->ensureUniqueCombination($data, (int) $id);

        $record->update($validated);
        return $this->successResponse($record->load(['section', 'subject', 'faculty']));
    }

    public function destroy(string $id)
    {
        $record = SectionSubject::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }

    private function ensureUniqueCombination(array $data, ?int $ignoreId = null): void
    {
        $exists = SectionSubject::query()
            ->where('section_id', $data['section_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('faculty_id', $data['faculty_id']);

        if ($ignoreId) {
            $exists->where('id', '!=', $ignoreId);
        }

        if ($exists->exists()) {
            throw ValidationException::withMessages([
                'section_id' => ['The section, subject, and faculty combination has already been taken.'],
            ]);
        }
    }
}
