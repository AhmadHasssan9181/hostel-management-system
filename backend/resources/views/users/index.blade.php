@extends('layouts.app')

@section('title', 'User accounts')

@section('content')
    <x-page-header
        title="User accounts"
        subtitle="Create staff and student accounts, reset access and manage roles."
        eyebrow="Administration">
        <button type="button" class="btn btn-primary" x-on:click="$dispatch('open-modal', 'user-create')">
            <x-icon name="plus" class="h-4 w-4" />
            Add user
        </button>
    </x-page-header>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Accounts" :value="$stats['total']" hint="All roles" icon="key" tone="brand" :delay="1" />
        <x-stat label="Students" :value="$stats['students']" hint="Resident accounts" icon="users" tone="sky" :delay="2" />
        <x-stat label="Staff" :value="$stats['staff']" hint="Employees and admins" icon="user" tone="emerald" :delay="3" />
        <x-stat label="Suspended" :value="$stats['suspended']" hint="Access disabled" icon="alert" tone="rose" :delay="4" />
    </div>

    <form method="GET" action="{{ route('users.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <div class="min-w-[220px] flex-1">
            <label class="label" for="search">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" class="input" placeholder="Name or email">
        </div>
        <div class="w-full sm:w-40">
            <label class="label" for="role">Role</label>
            <select id="role" name="role" class="select">
                <option value="">All roles</option>
                @foreach (['student', 'employee', 'admin'] as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-40">
            <label class="label" for="status">Status</label>
            <select id="status" name="status" class="select">
                <option value="">Any</option>
                @foreach (['active', 'suspended', 'pending'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-secondary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
        @if (request()->hasAny(['search', 'role', 'status']))
            <a href="{{ route('users.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>

    <div class="table-shell mt-5 rise rise-2">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last login</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-paper text-[10px] font-semibold uppercase text-ink">
                                        {{ str($user->name)->explode(' ')->take(2)->map(fn ($p) => str($p)->substr(0, 1))->implode('') }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-ink">{{ $user->name }}</p>
                                        <p class="truncate text-[11px] text-muted">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-xs font-medium capitalize text-ink-soft">
                                {{ $user->role->value }}
                            </td>
                            <td><x-status :value="$user->status" /></td>
                            <td class="text-xs text-ink-soft">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" class="btn btn-secondary btn-sm"
                                            x-on:click="$dispatch('open-modal', 'user-edit-{{ $user->id }}')">
                                        Edit
                                    </button>
                                    @if (! auth()->user()->is($user))
                                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                                              onsubmit="return confirm('Delete {{ $user->name }}? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-ghost btn-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-10 text-center text-sm text-muted">No users match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>

    @foreach ($users as $user)
        <x-modal :name="'user-edit-'.$user->id" title="Edit {{ $user->name }}">
            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Full name</label>
                        <input name="name" value="{{ $user->name }}" class="input" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Email</label>
                        <input name="email" type="email" value="{{ $user->email }}" class="input" required>
                    </div>
                    <div>
                        <label class="label">Phone</label>
                        <input name="phone" value="{{ $user->phone }}" class="input">
                    </div>
                    <div>
                        <label class="label">Role</label>
                        <select name="role" class="select" @disabled(auth()->user()->is($user))>
                            @foreach (['student', 'employee', 'admin'] as $role)
                                <option value="{{ $role }}" @selected($user->role->value === $role)>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select name="status" class="select">
                            @foreach (['active', 'suspended', 'pending'] as $status)
                                <option value="{{ $status }}" @selected($user->status->value === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">New password <span class="font-normal text-muted">(optional)</span></label>
                        <input name="password" type="password" class="input" placeholder="Leave blank to keep">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                    <button class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </x-modal>
    @endforeach

    <x-modal name="user-create" title="Add a user" description="Create a student, employee or administrator account." maxWidth="max-w-xl">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-4" x-data="{ role: 'student' }">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label">Full name</label>
                    <input name="name" class="input" required>
                </div>
                <div>
                    <label class="label">Email</label>
                    <input name="email" type="email" class="input" required>
                </div>
                <div>
                    <label class="label">Phone</label>
                    <input name="phone" class="input">
                </div>
                <div>
                    <label class="label">Role</label>
                    <select name="role" class="select" x-model="role">
                        <option value="student">Student</option>
                        <option value="employee">Employee</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="label">Password</label>
                    <input name="password" type="password" class="input" placeholder="Min 8 with symbol" required>
                </div>
                <div class="sm:col-span-2" x-show="role === 'student'" x-cloak>
                    <div class="grid gap-4 rounded-xl bg-paper p-4 ring-1 ring-line sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="label">Registration number</label>
                            <input name="registration_number" class="input" placeholder="BS CS SP-2023-Eve-0300">
                        </div>
                        <div>
                            <label class="label">Department</label>
                            <input name="department" class="input" value="Computer Science">
                        </div>
                        <div>
                            <label class="label">Semester</label>
                            <input name="semester" type="number" min="1" max="12" value="1" class="input">
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                <button class="btn btn-primary">Create user</button>
            </div>
        </form>
    </x-modal>
@endsection
