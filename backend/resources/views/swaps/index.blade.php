@extends('layouts.app')

@section('title', 'Room swaps')

@section('content')
    <x-page-header
        :title="$isStaff ? 'Room swap requests' : 'Room swap'"
        :subtitle="$isStaff ? 'Review requests to move between rooms and update allocations atomically.' : 'Need a different room? Explain why and the office will review your request.'"
        eyebrow="Residence">
        @if (! $isStaff)
            <a href="{{ route('rooms.index') }}" class="btn btn-secondary">
                <x-icon name="bed" class="h-4 w-4" />
                Browse rooms
            </a>
        @endif
    </x-page-header>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Requests" :value="$stats['total']" hint="All time" icon="swap" tone="brand" :delay="1" />
        <x-stat label="Pending" :value="$stats['pending']" hint="Awaiting review" icon="clock" tone="amber" :delay="2" />
        <x-stat label="Approved" :value="$stats['approved']" hint="Allocation moved" icon="check" tone="emerald" :delay="3" />
    </div>

    @if ($isStaff)
        <form method="GET" action="{{ route('swaps.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
            <div class="min-w-[200px] flex-1">
                <label class="label" for="search">Search student</label>
                <input id="search" name="search" value="{{ request('search') }}" class="input" placeholder="Name">
            </div>
            <div class="w-full sm:w-48">
                <label class="label" for="status">Status</label>
                <select id="status" name="status" class="select">
                    <option value="">Any status</option>
                    @foreach (['pending', 'approved', 'rejected', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
            @if (request()->hasAny(['search', 'status']))
                <a href="{{ route('swaps.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="table-shell mt-5 rise rise-2">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Move</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($swaps as $swap)
                            <tr>
                                <td>
                                    <p class="text-sm font-semibold text-ink">{{ $swap->student?->name }}</p>
                                    <p class="text-[11px] text-muted">{{ $swap->student?->studentProfile?->registration_number }}</p>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-canvas px-1.5 py-1 font-semibold text-ink ring-1 ring-line">
                                            <img src="{{ $swap->currentRoom?->image_url ?? asset('images/rooms/standard.jpg') }}" alt="" class="h-8 w-8 rounded object-cover">
                                            {{ $swap->currentRoom?->room_number ?? '—' }}
                                        </span>
                                        <x-icon name="arrow-right" class="h-3.5 w-3.5 text-muted" />
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-canvas px-1.5 py-1 font-semibold text-ink ring-1 ring-line">
                                            <img src="{{ $swap->requestedRoom?->image_url ?? asset('images/rooms/standard.jpg') }}" alt="" class="h-8 w-8 rounded object-cover">
                                            {{ $swap->requestedRoom?->room_number ?? '—' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-[10px] text-muted">{{ $swap->requestedRoom?->hostel?->name }}</p>
                                </td>
                                <td class="max-w-[260px]">
                                    <p class="line-clamp-2 text-xs leading-5 text-ink-soft">{{ $swap->reason }}</p>
                                </td>
                                <td>
                                    <x-status :value="$swap->status" />
                                    @if ($swap->review_note)
                                        <p class="mt-1 max-w-[160px] truncate text-[10px] text-muted">{{ $swap->review_note }}</p>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($swap->status->value === 'pending')
                                            <form method="POST" action="{{ route('swaps.approve', $swap) }}">
                                                @csrf
                                                <button class="btn btn-primary btn-sm">Approve</button>
                                            </form>
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                    x-on:click="$dispatch('open-modal', 'swap-reject-{{ $swap->id }}')">
                                                Reject
                                            </button>
                                        @else
                                            <span class="text-xs text-muted">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-10 text-center text-sm text-muted">No swap requests match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $swaps->links() }}</div>

        @foreach ($swaps as $swap)
            @if ($swap->status->value === 'pending')
                <x-modal :name="'swap-reject-'.$swap->id" title="Reject swap request" :description="$swap->student?->name">
                    <form method="POST" action="{{ route('swaps.reject', $swap) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="label">Reason (optional)</label>
                            <textarea name="review_note" class="textarea" rows="3" placeholder="Requested room is reserved for another resident..."></textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                            <button class="btn btn-danger">Reject request</button>
                        </div>
                    </form>
                </x-modal>
            @endif
        @endforeach
    @else
        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1">
                @if (! $activeBooking)
                    <div class="card p-6">
                        <x-empty illustration="room" title="No active allocation" message="You need an approved room before requesting a swap.">
                            <a href="{{ route('rooms.index') }}" class="btn btn-primary btn-sm">Find a room</a>
                        </x-empty>
                    </div>
                @elseif ($hasPending)
                    <div class="card p-6">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-paper text-amber-600 ring-1 ring-line">
                                <x-icon name="clock" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-ink">Request pending</p>
                                <p class="mt-1 text-xs leading-5 text-muted">
                                    You already have a pending swap request. The office will review it before you can submit another.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card p-6">
                        <h2 class="section-title">Request a move</h2>
                        <p class="mt-1 text-xs text-muted">
                            Currently in room {{ $activeBooking->room?->room_number }} · {{ $activeBooking->room?->hostel?->name }}
                        </p>

                        <form method="POST" action="{{ route('swaps.store') }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label class="label">Move to</label>
                                <select name="requested_room_id" class="select" required>
                                    <option value="">Choose a room</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}">
                                            {{ $room->hostel?->name }} · {{ $room->room_number }} ({{ $room->available_beds }} free)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Why do you need the move?</label>
                                <textarea name="reason" class="textarea" rows="4" placeholder="Give a clear reason — this helps the office decide." required></textarea>
                            </div>
                            <button class="btn btn-primary w-full">Submit request</button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-2">
                @if ($swaps->isEmpty())
                    <x-empty illustration="room" title="No swap requests yet"
                             message="When you request a room change, its progress will appear here." />
                @else
                    <div class="space-y-4">
                        @foreach ($swaps as $swap)
                            <article class="card rise p-5">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-canvas px-1.5 py-1 font-semibold text-ink ring-1 ring-line">
                                            <img src="{{ $swap->currentRoom?->image_url ?? asset('images/rooms/standard.jpg') }}" alt="" class="h-10 w-10 rounded-md object-cover">
                                            {{ $swap->currentRoom?->room_number ?? '—' }}
                                        </span>
                                        <x-icon name="arrow-right" class="h-4 w-4 text-muted" />
                                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-canvas px-1.5 py-1 font-semibold text-ink ring-1 ring-line">
                                            <img src="{{ $swap->requestedRoom?->image_url ?? asset('images/rooms/standard.jpg') }}" alt="" class="h-10 w-10 rounded-md object-cover">
                                            {{ $swap->requestedRoom?->room_number ?? '—' }}
                                        </span>
                                    </div>
                                    <x-status :value="$swap->status" />
                                </div>
                                <p class="mt-3 text-sm leading-6 text-ink-soft">{{ $swap->reason }}</p>
                                @if ($swap->review_note)
                                    <p class="mt-3 rounded-lg bg-paper px-3 py-2 text-xs text-ink-soft ring-1 ring-line">
                                        <span class="font-semibold">Office note:</span> {{ $swap->review_note }}
                                    </p>
                                @endif
                                <div class="mt-4 flex items-center justify-between border-t border-line pt-3">
                                    <p class="text-[11px] text-muted">Requested {{ $swap->created_at->format('d M Y') }}</p>
                                    @if ($swap->status->value === 'pending')
                                        <form method="POST" action="{{ route('swaps.cancel', $swap) }}"
                                              onsubmit="return confirm('Cancel this swap request?');">
                                            @csrf
                                            <button class="btn btn-ghost btn-sm">Cancel request</button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-6">{{ $swaps->links() }}</div>
                @endif
            </div>
        </div>
    @endif
@endsection
