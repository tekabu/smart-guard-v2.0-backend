<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SectionSubjectStudent;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SectionSubjectStudentController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $records = SectionSubjectStudent::with([
            'sectionSubject.section',
            'sectionSubject.subject',
            'sectionSubject.faculty',
            'student',
        ])
            ->when($request->filled('section_id'), fn ($query) => $query->whereHas(
                'sectionSubject',
                fn ($sectionSubjectQuery) => $sectionSubjectQuery->where('section_id', $request->input('section_id'))
            ))
            ->when($request->filled('subject_id'), fn ($query) => $query->whereHas(
                'sectionSubject',
                fn ($sectionSubjectQuery) => $sectionSubjectQuery->where('subject_id', $request->input('subject_id'))
            ))
            ->when($request->filled('faculty_id'), fn ($query) => $query->whereHas(
                'sectionSubject',
                fn ($sectionSubjectQuery) => $sectionSubjectQuery->where('faculty_id', $request->input('faculty_id'))
            ))
            ->get();
        return $this->successResponse($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_subject_id' => ['required', 'exists:section_subjects,id'],
            'student_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'STUDENT')),
            ],
        ]);

        $this->ensureUniqueCombination($validated);

        $record = SectionSubjectStudent::create($validated);
        return $this->successResponse($record->load([
            'sectionSubject.section',
            'sectionSubject.subject',
            'sectionSubject.faculty',
            'student',
        ]), 201);
    }

    public function show(string $id)
    {
        $record = SectionSubjectStudent::with([
            'sectionSubject.section',
            'sectionSubject.subject',
            'sectionSubject.faculty',
            'student',
        ])->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = SectionSubjectStudent::findOrFail($id);

        $validated = $request->validate([
            'section_subject_id' => ['sometimes', 'exists:section_subjects,id'],
            'student_id' => [
                'sometimes',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'STUDENT')),
            ],
        ]);

        $data = array_merge($record->only(['section_subject_id', 'student_id']), $validated);

        $this->ensureUniqueCombination($data, (int) $id);

        $record->update($validated);
        return $this->successResponse($record->load([
            'sectionSubject.section',
            'sectionSubject.subject',
            'sectionSubject.faculty',
            'student',
        ]));
    }

    public function destroy(string $id)
    {
        $record = SectionSubjectStudent::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }

    private function ensureUniqueCombination(array $data, ?int $ignoreId = null): void
    {
        $exists = SectionSubjectStudent::query()
            ->where('section_subject_id', $data['section_subject_id'])
            ->where('student_id', $data['student_id']);

        if ($ignoreId) {
            $exists->where('id', '!=', $ignoreId);
        }

        if ($exists->exists()) {
            throw ValidationException::withMessages([
                'section_subject_id' => ['The section subject and student combination has already been taken.'],
            ]);
        }
    }
}
