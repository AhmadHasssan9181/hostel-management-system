<?php

namespace App\Http\Controllers\Web;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->where('role', Role::Student->value)
            ->with(['studentProfile', 'activeBooking.room.hostel']);

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhereHas('studentProfile', fn ($p) => $p->where('registration_number', 'like', $search));
            });
        }

        if ($request->filled('hostel_id')) {
            $query->whereHas('activeBooking.room', fn ($r) => $r->where('hostel_id', $request->integer('hostel_id')));
        }

        if ($request->filled('check_in_status')) {
            $query->whereHas('studentProfile', fn ($p) => $p->where('check_in_status', $request->string('check_in_status')));
        }

        return view('students.index', [
            'students' => $query->latest()->paginate(15)->withQueryString(),
            'hostels' => Hostel::query()->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'total' => User::where('role', Role::Student->value)->count(),
                'checked_in' => User::where('role', Role::Student->value)
                    ->whereHas('studentProfile', fn ($q) => $q->where('check_in_status', 'checked_in'))
                    ->count(),
                'pending' => User::where('role', Role::Student->value)
                    ->whereHas('studentProfile', fn ($q) => $q->where('check_in_status', 'pending'))
                    ->count(),
            ],
        ]);
    }

    public function show(User $student): View
    {
        abort_unless($student->role === Role::Student, 404);

        return view('students.show', [
            'student' => $student->load(
                'studentProfile',
                'activeBooking.room.hostel',
                'bookings.room.hostel',
                'complaints',
                'fees',
                'roomSwapRequests',
                'studentRequests',
            ),
        ]);
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === Role::Student, 404);

        $data = $request->validate([
            'registration_number' => [
                'sometimes', 'string', 'max:40',
                Rule::unique('student_profiles', 'registration_number')->ignore($student->studentProfile?->id),
            ],
            'department' => ['sometimes', 'string', 'max:100'],
            'semester' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'guardian_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'guardian_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'medical_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'check_in_status' => ['sometimes', Rule::in(['pending', 'checked_in', 'checked_out'])],
            'discipline_status' => ['sometimes', Rule::in(['clear', 'warning', 'probation'])],
        ]);

        $student->studentProfile()->updateOrCreate(
            ['user_id' => $student->id],
            $data + [
                'registration_number' => $student->studentProfile?->registration_number ?? 'PENDING-'.$student->id,
                'department' => $student->studentProfile?->department ?? 'N/A',
            ],
        );

        return back()->with('success', 'Student record updated.');
    }
}
