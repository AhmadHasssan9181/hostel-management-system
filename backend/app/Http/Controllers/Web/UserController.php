<?php

namespace App\Http\Controllers\Web;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
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
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('email', 'like', $search));
        }

        return view('users.index', [
            'users' => $query->latest()->paginate(15)->withQueryString(),
            'stats' => [
                'total' => User::count(),
                'students' => User::where('role', Role::Student->value)->count(),
                'staff' => User::whereIn('role', [Role::Employee->value, Role::Admin->value])->count(),
                'suspended' => User::where('status', UserStatus::Suspended->value)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        DB::transaction(function () use ($data) {
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
        });

        return back()->with('success', 'User account created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'role' => ['sometimes', Rule::enum(Role::class)],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
            'password' => ['sometimes', 'nullable', Password::min(8)->letters()->numbers()->symbols()],
        ]);

        if ($request->user()->is($user) && isset($data['role']) && $data['role'] !== $user->role->value) {
            return back()->with('error', 'You cannot change your own role.');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        $user->update($data);

        return back()->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
