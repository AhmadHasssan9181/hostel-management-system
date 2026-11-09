<?php

namespace App\Http\Controllers\Web;

use App\Enums\RequestStatus;
use App\Enums\StudentRequestType;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\StudentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = StudentRequest::query()->with(['student.studentProfile', 'reviewer:id,name']);

        if ($user->isStudent()) {
            $query->where('student_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        $statsQuery = StudentRequest::query();
        if ($user->isStudent()) {
            $statsQuery->where('student_id', $user->id);
        }

        return view('requests.index', [
            'requests' => $query->latest()->paginate(12)->withQueryString(),
            'isStaff' => $user->isStaff(),
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'pending' => (clone $statsQuery)->where('status', RequestStatus::Pending->value)->count(),
                'approved' => (clone $statsQuery)->where('status', RequestStatus::Approved->value)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(StudentRequestType::class)],
            'subject' => ['required', 'string', 'max:150'],
            'details' => ['nullable', 'string', 'max:2000'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        StudentRequest::create($data + [
            'student_id' => $request->user()->id,
            'status' => RequestStatus::Pending,
        ]);

        return back()->with('success', 'Request submitted.');
    }

    public function cancel(Request $request, StudentRequest $studentRequest): RedirectResponse
    {
        abort_unless($studentRequest->student_id === $request->user()->id, 403);

        if ($studentRequest->status !== RequestStatus::Pending) {
            return back()->with('error', 'Only pending requests can be cancelled.');
        }

        $studentRequest->update(['status' => RequestStatus::Cancelled]);

        return back()->with('success', 'Request cancelled.');
    }

    public function review(Request $request, StudentRequest $studentRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([RequestStatus::Approved->value, RequestStatus::Rejected->value])],
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($studentRequest->status !== RequestStatus::Pending) {
            return back()->with('error', 'This request has already been reviewed.');
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

        return back()->with('success', 'Request reviewed.');
    }
}
