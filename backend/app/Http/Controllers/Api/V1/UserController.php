<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('studentProfile');

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhereHas('studentProfile', fn ($p) => $p->where('registration_number', 'like', $search));
            });
        }

        return response()->json($query->latest()->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->numbers()->symbols()],
            'role' => ['required', Rule::enum(Role::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
            'registration_number' => ['required_if:role,student', 'nullable', 'string', 'max:40', 'unique:student_profiles,registration_number'],
            'department' => ['required_if:role,student', 'nullable', 'string', 'max:100'],
            'semester' => ['required_if:role,student', 'nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'role' => $data['role'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? UserStatus::Active,
            ]);

            if ($user->role === Role::Student) {
                StudentProfile::create([
                    'user_id' => $user->id,
                    'registration_number' => $data['registration_number'],
                    'department' => $data['department'],
                    'semester' => $data['semester'],
                ]);
            }

            return $user;
        });

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user->load('studentProfile'),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'user' => $user->load(
                'studentProfile',
                'activeBooking.room.hostel',
                'bookings.room.hostel',
                'fees',
            ),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'role' => ['sometimes', Rule::enum(Role::class)],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
            'password' => ['sometimes', Password::min(8)->letters()->numbers()->symbols()],
        ]);

        if ($request->user()->is($user) && isset($data['role']) && $data['role'] !== $user->role->value) {
            return response()->json(['message' => 'You cannot change your own role.'], 422);
        }

        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated.',
            'user' => $user->fresh()->load('studentProfile'),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
