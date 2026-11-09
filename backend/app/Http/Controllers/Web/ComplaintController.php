<?php

namespace App\Http\Controllers\Web;

use App\Enums\ComplaintStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\User;
use App\Services\ComplaintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function __construct(private readonly ComplaintService $complaints) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Complaint::query()->with(['student:id,name', 'room:id,room_number', 'assignee:id,name']);

        if ($user->isStudent()) {
            $query->where('student_id', $user->id);
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

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->where('ticket_no', 'like', $search)
                ->orWhere('description', 'like', $search));
        }

        $statsQuery = Complaint::query();
        if ($user->isStudent()) {
            $statsQuery->where('student_id', $user->id);
        }

        return view('complaints.index', [
            'complaints' => $query->latest()->paginate(12)->withQueryString(),
            'isStaff' => $user->isStaff(),
            'staff' => $user->isStaff()
                ? User::query()
                    ->whereIn('role', [Role::Employee->value, Role::Admin->value])
                    ->where('status', UserStatus::Active->value)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : collect(),
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'open' => (clone $statsQuery)->open()->count(),
                'resolved' => (clone $statsQuery)->whereIn('status', [ComplaintStatus::Resolved->value, ComplaintStatus::Closed->value])->count(),
                'urgent' => (clone $statsQuery)->open()->whereIn('priority', ['high', 'urgent'])->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(['plumbing', 'electrical', 'wifi', 'cleanliness', 'furniture', 'mess', 'other'])],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        try {
            $complaint = $this->complaints->create($request->user(), $data, $request->file('photo'));
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('complaints.show', $complaint)
            ->with('success', "Complaint filed. Your ticket is {$complaint->ticket_no}.");
    }

    public function show(Request $request, Complaint $complaint): View
    {
        if ($request->user()->isStudent()) {
            abort_unless($complaint->student_id === $request->user()->id, 403);
        }

        return view('complaints.show', [
            'complaint' => $complaint->load(['student.studentProfile', 'room.hostel', 'assignee']),
            'isStaff' => $request->user()->isStaff(),
            'staff' => $request->user()->isStaff()
                ? User::query()
                    ->whereIn('role', [Role::Employee->value, Role::Admin->value])
                    ->where('status', UserStatus::Active->value)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function ticket(Request $request, Complaint $complaint): View
    {
        if ($request->user()->isStudent()) {
            abort_unless($complaint->student_id === $request->user()->id, 403);
        }

        return view('documents.complaint-ticket', [
            'complaint' => $complaint->load('student.studentProfile', 'room.hostel', 'assignee'),
        ]);
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(['plumbing', 'electrical', 'wifi', 'cleanliness', 'furniture', 'mess', 'other'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $photo = $request->file('photo');
        unset($data['photo']);

        if ($photo) {
            $this->complaints->deletePhoto($complaint);
            $data['photo_path'] = $photo->store('complaints', 'public');
        }

        $complaint->update($data);

        return back()->with('success', "Ticket {$complaint->ticket_no} updated.");
    }

    public function assign(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validate([
            'assigned_to' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', [Role::Employee->value, Role::Admin->value])
                    ->where('status', UserStatus::Active->value)),
            ],
        ]);

        try {
            $this->complaints->assign($complaint, User::findOrFail($data['assigned_to']));
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Complaint assigned to a staff member.');
    }

    public function updateStatus(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ComplaintStatus::class)],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->complaints->updateStatus($complaint, $data, $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Ticket {$complaint->ticket_no} updated.");
    }
}
