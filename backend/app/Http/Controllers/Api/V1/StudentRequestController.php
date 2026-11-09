<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RequestStatus;
use App\Enums\StudentRequestType;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\StudentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = StudentRequest::query()->with(['student.studentProfile', 'reviewer']);

        if ($user->isStudent()) {
            $query->where('student_id', $user->id);
        } elseif ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return response()->json($query->latest()->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(StudentRequestType::class)],
            'subject' => ['required', 'string', 'max:150'],
            'details' => ['nullable', 'string', 'max:2000'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $studentRequest = StudentRequest::create($data + [
            'student_id' => $request->user()->id,
            'status' => RequestStatus::Pending,
        ]);

        return response()->json([
            'message' => 'Request submitted.',
            'request' => $studentRequest->load('student.studentProfile'),
        ], 201);
    }

    public function review(Request $request, StudentRequest $studentRequest): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([RequestStatus::Approved->value, RequestStatus::Rejected->value])],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($studentRequest->status !== RequestStatus::Pending) {
            throw ValidationException::withMessages(['status' => 'This request has already been reviewed.']);
        }

        $studentRequest->update([
            'status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'review_note' => $data['review_note'] ?? null,
            'reviewed_at' => now(),
        ]);

        AppNotification::send(
            $studentRequest->student,
            'request',
            "Request {$data['status']}",
            ($data['review_note'] ?? null) ?: "Your request \"{$studentRequest->subject}\" was {$data['status']}.",
            ['request_id' => $studentRequest->id],
        );

        return response()->json([
            'message' => 'Request reviewed.',
            'request' => $studentRequest->fresh(['student.studentProfile', 'reviewer']),
        ]);
    }

    public function cancel(Request $request, StudentRequest $studentRequest): JsonResponse
    {
        abort_unless($studentRequest->student_id === $request->user()->id, 403, 'This request does not belong to you.');

        if ($studentRequest->status !== RequestStatus::Pending) {
            return response()->json(['message' => 'Only pending requests can be cancelled.'], 422);
        }

        $studentRequest->update(['status' => RequestStatus::Cancelled]);

        return response()->json(['message' => 'Request cancelled.', 'request' => $studentRequest]);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
