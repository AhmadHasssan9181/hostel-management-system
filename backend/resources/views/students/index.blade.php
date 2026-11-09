@extends('layouts.app')

@section('title', 'Students')

@section('content')
    <x-page-header
        title="Students"
        subtitle="The complete resident directory with allocations and account status."
        eyebrow="Residence">
        <a href="{{ route('rooms.index') }}" class="btn btn-secondary">
            <x-icon name="bed" class="h-4 w-4" />
            Rooms
        </a>
    </x-page-header>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Registered students" :value="$stats['total']" hint="With portal accounts" icon="users" tone="brand" :delay="1" />
        <x-stat label="Checked in" :value="$stats['checked_in']" hint="Living in a hostel" icon="check" tone="emerald" :delay="2" />
        <x-stat label="Awaiting check-in" :value="$stats['pending']" hint="Allocated or registering" icon="clock" tone="amber" :delay="3" />
    </div>

    <form method="GET" action="{{ route('students.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <div class="min-w-[220px] flex-1">
            <label class="label" for="search">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" class="input" placeholder="Name, email or registration number">
        </div>
        <div class="w-full sm:w-48">
            <label class="label" for="hostel_id">Block</label>
            <select id="hostel_id" name="hostel_id" class="select">
                <option value="">All blocks</option>
                @foreach ($hostels as $hostel)
                    <option value="{{ $hostel->id }}" @selected(request('hostel_id') == $hostel->id)>{{ $hostel->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-48">
            <label class="label" for="check_in_status">Check-in</label>
            <select id="check_in_status" name="check_in_status" class="select">
                <option value="">Any</option>
                @foreach (['pending', 'checked_in', 'checked_out'] as $status)
                    <option value="{{ $status }}" @selected(request('check_in_status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-secondary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
        @if (request()->hasAny(['search', 'hostel_id', 'check_in_status']))
            <a href="{{ route('students.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>

    <div class="table-shell mt-5 rise rise-2">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Registration</th>
                        <th>Department</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-paper text-[10px] font-semibold uppercase text-ink">
                                        {{ str($student->name)->explode(' ')->take(2)->map(fn ($p) => str($p)->substr(0, 1))->implode('') }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-ink">{{ $student->name }}</p>
                                        <p class="truncate text-[11px] text-muted">{{ $student->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-xs text-ink-soft">{{ $student->studentProfile?->registration_number ?? '—' }}</td>
                            <td class="text-xs text-ink-soft">{{ $student->studentProfile?->department ?? '—' }} · Sem {{ $student->studentProfile?->semester ?? '—' }}</td>
                            <td>
                                @if ($student->activeBooking?->room)
                                    <p class="text-sm font-semibold text-ink">{{ $student->activeBooking->room->room_number }}</p>
                                    <p class="text-[11px] text-muted">{{ $student->activeBooking->room->hostel?->name }}</p>
                                @else
                                    <span class="text-xs text-muted">Not allocated</span>
                                @endif
                            </td>
                            <td><x-status :value="$student->studentProfile?->check_in_status ?? 'pending'" /></td>
                            <td class="text-right">
                                <a href="{{ route('students.show', $student) }}" class="btn btn-secondary btn-sm">View profile</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-sm text-muted">No students match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $students->links() }}</div>
@endsection
