<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ComplaintStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\User;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    public function __construct(private readonly ComplaintService $complaints) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Complaint::query()->with(['student.studentProfile', 'room.hostel', 'assignee']);

        if ($user->isStudent()) {
            $query->where('student_id', $user->id);
        } elseif ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->integer('assigned_to'));
        }

        if ($request->boolean('unassigned', false)) {
            $query->whereNull('assigned_to')->open();
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->where('ticket_no', 'like', $search)->orWhere('description', 'like', $search));
        }

        return response()->json($query->latest()->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(['plumbing', 'electrical', 'wifi', 'cleanliness', 'furniture', 'mess', 'other'])],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $complaint = $this->complaints->create(
            $request->user(),
            $data,
            $request->file('photo'),
        );

        return response()->json([
            'message' => 'Complaint submitted.',
            'complaint' => $complaint->load('room.hostel'),
        ], 201);
    }

    public function show(Request $request, Complaint $complaint): JsonResponse
    {
        $this->authorizeView($request, $complaint);

        return response()->json([
            'complaint' => $complaint->load(['student.studentProfile', 'room.hostel', 'assignee']),
        ]);
    }

    public function assign(Request $request, Complaint $complaint): JsonResponse
    {
        $data = $request->validate([
            'assigned_to' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', [Role::Employee->value, Role::Admin->value])
                    ->where('status', UserStatus::Active->value)),
            ],
        ]);

        $staff = User::findOrFail($data['assigned_to']);
        $complaint = $this->complaints->assign($complaint, $staff);

        return response()->json(['message' => 'Complaint assigned.', 'complaint' => $complaint]);
    }

    public function updateStatus(Request $request, Complaint $complaint): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ComplaintStatus::class)],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $complaint = $this->complaints->updateStatus($complaint, $data, $request->user());

        return response()->json(['message' => 'Complaint updated.', 'complaint' => $complaint]);
    }

    public function destroy(Request $request, Complaint $complaint): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Only administrators can delete complaints.');

        $this->complaints->deletePhoto($complaint);
        $complaint->delete();

        return response()->json(['message' => 'Complaint deleted.']);
    }

    private function authorizeView(Request $request, Complaint $complaint): void
    {
        if ($request->user()->isStudent()) {
            abort_unless($complaint->student_id === $request->user()->id, 403, 'This complaint does not belong to you.');
        }
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
