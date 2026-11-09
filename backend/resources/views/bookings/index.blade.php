@extends('layouts.app')

@section('title', 'Bookings')

@section('content')
    <x-page-header
        :title="$isStaff ? 'Bookings' : 'My bookings'"
        :subtitle="$isStaff ? 'Review room requests, allocate beds and manage check-in and check-out.' : 'Everything you have requested, from submission to allocation.'"
        eyebrow="Residence">
        @if ($isStaff)
            <a href="{{ route('rooms.index', ['status' => 'available']) }}" class="btn btn-secondary">
                <x-icon name="bed" class="h-4 w-4" />
                Available rooms
            </a>
        @else
            <a href="{{ route('rooms.index') }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Request a room
            </a>
        @endif
    </x-page-header>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Total bookings" :value="$stats['total']" hint="All time" icon="clipboard" tone="brand" :delay="1" />
        <x-stat label="Pending" :value="$stats['pending']" hint="Awaiting review" icon="clock" tone="amber" :delay="2" />
        <x-stat label="Approved" :value="$stats['approved']" hint="Active allocations" icon="check" tone="emerald" :delay="3" />
        @if ($isStaff)
            <x-stat label="Checked out" :value="$stats['checked_out']" hint="Released beds" icon="logout" tone="zinc" :delay="4" />
        @else
            <x-stat label="Account status" :value="auth()->user()->status->value" hint="Hostel account" icon="user" tone="sky" :delay="4" />
        @endif
    </div>

    @if ($isStaff)
        <form method="GET" action="{{ route('bookings.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
            <div class="min-w-[200px] flex-1">
                <label class="label" for="search">Search student</label>
                <input id="search" name="search" value="{{ request('search') }}" class="input" placeholder="Name or email">
            </div>
            <div class="w-full sm:w-44">
                <label class="label" for="status">Status</label>
                <select id="status" name="status" class="select">
                    <option value="">Any status</option>
                    @foreach (['pending', 'approved', 'rejected', 'cancelled', 'checked_out'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
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
            <button class="btn btn-secondary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
            @if (request()->hasAny(['search', 'status', 'hostel_id']))
                <a href="{{ route('bookings.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="table-shell mt-5 rise rise-2">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Room</th>
                            <th>Requested</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-paper text-[10px] font-semibold uppercase text-ink">
                                            {{ str($booking->student?->name ?? '?')->explode(' ')->take(2)->map(fn ($p) => str($p)->substr(0, 1))->implode('') }}
                                        </span>
                                        <div class="min-w-0">
                                            <a href="{{ $booking->student ? route('students.show', $booking->student) : '#' }}" class="block truncate text-sm font-semibold text-ink hover:text-brand-700">
                                                {{ $booking->student?->name ?? 'Deleted user' }}
                                            </a>
                                            <p class="truncate text-[11px] text-muted">{{ $booking->student?->studentProfile?->registration_number ?? $booking->student?->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <img src="{{ $booking->room?->image_url ?? asset('images/rooms/standard.jpg') }}"
                                             alt="Room {{ $booking->room?->room_number }}"
                                             loading="lazy"
                                             class="h-9 w-9 shrink-0 rounded-lg object-cover ring-1 ring-line">
                                        <div>
                                            <p class="num text-sm font-semibold text-ink">{{ $booking->room?->room_number ?? '—' }}</p>
                                            <p class="text-[11px] text-muted">{{ $booking->room?->hostel?->name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-xs text-ink-soft">{{ ($booking->requested_from ?? $booking->created_at)->format('d M Y') }}</td>
                                <td>
                                    <x-status :value="$booking->status" />
                                    @if ($booking->review_note)
                                        <p class="mt-1 max-w-[160px] truncate text-[10px] text-muted">{{ $booking->review_note }}</p>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if (in_array($booking->status->value, ['approved', 'checked_out'], true))
                                            <a href="{{ route('bookings.slip', $booking) }}" target="_blank"
                                               class="icon-btn h-8 w-8" title="Print allocation slip" aria-label="Print allocation slip">
                                                <x-icon name="clipboard" class="h-4 w-4" />
                                            </a>
                                        @endif
                                        @if (in_array($booking->status->value, ['pending', 'approved'], true))
                                            <button type="button" class="btn btn-ghost btn-sm"
                                                    x-on:click="$dispatch('open-modal', 'booking-edit-{{ $booking->id }}')">
                                                Edit
                                            </button>
                                        @endif
                                        @if ($booking->status->value === 'pending')
                                            <form method="POST" action="{{ route('bookings.approve', $booking) }}">
                                                @csrf
                                                <button class="btn btn-primary btn-sm">Approve</button>
                                            </form>
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                    x-on:click="$dispatch('open-modal', 'booking-reject-{{ $booking->id }}')">
                                                Reject
                                            </button>
                                        @elseif ($booking->status->value === 'approved')
                                            @if ($booking->student?->studentProfile?->check_in_status === 'checked_in')
                                                <x-status value="checked_in" />
                                            @else
                                                <form method="POST" action="{{ route('bookings.check-in', $booking) }}">
                                                    @csrf
                                                    <button class="btn btn-secondary btn-sm">Check in</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('bookings.check-out', $booking) }}"
                                                  onsubmit="return confirm('Check this resident out and release the bed?');">
                                                @csrf
                                                <button class="btn btn-ghost btn-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10">Check out</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-muted">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-10 text-center text-sm text-muted">No bookings match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @foreach ($bookings as $booking)
            @if (in_array($booking->status->value, ['pending', 'approved'], true))
                <x-modal :name="'booking-edit-'.$booking->id" title="Edit booking" :description="$booking->student?->name" maxWidth="max-w-lg">
                    <form method="POST" action="{{ route('bookings.update', $booking) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="label">Room</label>
                            <select name="room_id" class="select" required>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->id }}" @selected($booking->room_id === $room->id)>
                                        {{ $room->hostel?->name }} · {{ $room->room_number }} ({{ $room->available_beds }} free)
                                    </option>
                                @endforeach
                            </select>
                            <p class="hint">Only available rooms should be selected. Moving an approved booking updates both rooms.</p>
                        </div>
                        <div>
                            <label class="label">Note</label>
                            <textarea name="note" class="textarea" rows="3">{{ $booking->note }}</textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                            <button class="btn btn-primary">Save booking</button>
                        </div>
                    </form>
                </x-modal>
            @endif
        @endforeach

        @foreach ($bookings as $booking)
            @if ($booking->status->value === 'pending')
                <x-modal :name="'booking-reject-'.$booking->id" title="Reject booking" description="Let {{ $booking->student?->name }} know why.">
                    <form method="POST" action="{{ route('bookings.reject', $booking) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="label">Reason (optional)</label>
                            <textarea name="review_note" class="textarea" rows="3" placeholder="Room unavailable for this semester..."></textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                            <button class="btn btn-danger">Reject booking</button>
                        </div>
                    </form>
                </x-modal>
            @endif
        @endforeach
    @else
        @if ($bookings->isEmpty())
            <div class="mt-6">
                <x-empty illustration="ticket" title="No bookings yet"
                         message="Browse the available rooms and send your first request. You can track its status here.">
                    <a href="{{ route('rooms.index') }}" class="btn btn-primary btn-sm">Browse rooms</a>
                </x-empty>
            </div>
        @else
            <div class="mt-6 space-y-4">
                @foreach ($bookings as $booking)
                    <article class="card rise overflow-hidden">
                        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-start gap-4">
                                <img src="{{ $booking->room?->image_url ?? asset('images/rooms/standard.jpg') }}"
                                     alt="Room {{ $booking->room?->room_number }}"
                                     loading="lazy"
                                     class="h-24 w-32 shrink-0 rounded-xl object-cover ring-1 ring-line">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-base font-semibold tracking-tight text-ink">
                                            Room {{ $booking->room?->room_number ?? '—' }}
                                        </p>
                                        <x-status :value="$booking->status" />
                                    </div>
                                    <p class="mt-0.5 text-sm text-muted">
                                        {{ $booking->room?->hostel?->name }} · requested {{ $booking->created_at->format('d M Y') }}
                                    </p>
                                    @if ($booking->review_note)
                                        <p class="mt-2 rounded-lg bg-paper px-3 py-2 text-xs text-ink-soft ring-1 ring-line">
                                            <span class="font-semibold">Office note:</span> {{ $booking->review_note }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2 sm:flex-col sm:items-end">
                                <p class="text-xs text-muted">
                                    @if ($booking->reviewed_at)
                                        Reviewed {{ $booking->reviewed_at->format('d M Y') }}
                                    @else
                                        Awaiting review
                                    @endif
                                </p>
                                @if (in_array($booking->status->value, ['approved', 'checked_out'], true))
                                    <a href="{{ route('bookings.slip', $booking) }}" target="_blank" class="btn btn-ghost btn-sm">
                                        <x-icon name="clipboard" class="h-3.5 w-3.5" />
                                        Allocation slip
                                    </a>
                                @endif
                                @if ($booking->status->value === 'pending')
                                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}"
                                          onsubmit="return confirm('Cancel this booking request?');">
                                        @csrf
                                        <button class="btn btn-secondary btn-sm">Cancel request</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">{{ $bookings->links() }}</div>
        @endif
    @endif
@endsection
