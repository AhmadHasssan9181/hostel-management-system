<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->where('role', Role::Student->value)
            ->with(['studentProfile', 'activeBooking.room.hostel']);

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhereHas('studentProfile', fn ($p) => $p->where('registration_number', 'like', $search));
            });
        }

        if ($request->filled('hostel_id')) {
            $query->whereHas('activeBooking.room', fn ($r) => $r->where('hostel_id', $request->integer('hostel_id')));
        }

        if ($request->filled('check_in_status')) {
            $query->whereHas('studentProfile', fn ($p) => $p->where('check_in_status', $request->string('check_in_status')));
        }

        return response()->json($query->latest()->paginate($this->perPage($request)));
    }

    public function show(User $student): JsonResponse
    {
        abort_unless($student->role === Role::Student, 404, 'Student not found.');

        return response()->json([
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

    public function update(Request $request, User $student): JsonResponse
    {
        abort_unless($student->role === Role::Student, 404, 'Student not found.');

        $data = $request->validate([
            'registration_number' => ['sometimes', 'string', 'max:40', Rule::unique('student_profiles', 'registration_number')->ignore($student->studentProfile?->id)],
            'department' => ['sometimes', 'string', 'max:100'],
            'semester' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'address' => ['sometimes', 'nullable', 'string'],
            'guardian_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'guardian_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'medical_notes' => ['sometimes', 'nullable', 'string'],
            'check_in_status' => ['sometimes', Rule::in(['pending', 'checked_in', 'checked_out'])],
            'discipline_status' => ['sometimes', Rule::in(['clear', 'warning', 'probation'])],
        ]);

        $profile = $student->studentProfile()->updateOrCreate(
            ['user_id' => $student->id],
            $data + [
                'registration_number' => $student->studentProfile?->registration_number ?? 'PENDING-'.$student->id,
                'department' => $student->studentProfile?->department ?? 'N/A',
            ],
        );

        return response()->json([
            'message' => 'Student record updated.',
            'student' => $student->fresh()->load('studentProfile', 'activeBooking.room.hostel'),
        ]);
    }

    public function updateOwnProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'address' => ['sometimes', 'nullable', 'string'],
            'guardian_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'guardian_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'medical_notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $profile = StudentProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'registration_number' => 'PENDING-'.$user->id,
                'department' => 'N/A',
                'semester' => 1,
            ],
        );

        $profile->update($data);

        return response()->json([
            'message' => 'Profile updated.',
            'profile' => $profile,
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
