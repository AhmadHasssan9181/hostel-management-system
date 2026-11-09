@extends('layouts.app')

@section('title', 'Requests')

@section('content')
    <x-page-header
        :title="$isStaff ? 'Student requests' : 'My requests'"
        :subtitle="$isStaff ? 'Approve or reject leave, room change and facility requests.' : 'Ask for leave or any other hostel permission and track the decision.'"
        eyebrow="Services">
        @if (! $isStaff)
            <button type="button" class="btn btn-primary" x-on:click="$dispatch('open-modal', 'request-create')">
                <x-icon name="plus" class="h-4 w-4" />
                New request
            </button>
        @endif
    </x-page-header>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Requests" :value="$stats['total']" hint="All time" icon="calendar" tone="brand" :delay="1" />
        <x-stat label="Pending" :value="$stats['pending']" hint="Awaiting review" icon="clock" tone="amber" :delay="2" />
        <x-stat label="Approved" :value="$stats['approved']" hint="Accepted requests" icon="check" tone="emerald" :delay="3" />
    </div>

    @if ($isStaff)
        <form method="GET" action="{{ route('requests.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-48">
                <label class="label" for="status">Status</label>
                <select id="status" name="status" class="select">
                    <option value="">Any status</option>
                    @foreach (['pending', 'approved', 'rejected', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-44">
                <label class="label" for="type">Type</label>
                <select id="type" name="type" class="select">
                    <option value="">All types</option>
                    @foreach (['leave', 'room_change', 'facility', 'other'] as $type)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
            @if (request()->hasAny(['status', 'type']))
                <a href="{{ route('requests.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="table-shell mt-5 rise rise-2">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Request</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $request)
                            <tr>
                                <td>
                                    <p class="text-sm font-semibold text-ink">{{ $request->student?->name }}</p>
                                    <p class="text-[11px] text-muted">{{ $request->student?->studentProfile?->registration_number }}</p>
                                </td>
                                <td class="max-w-[300px]">
                                    <p class="text-sm text-ink">{{ $request->subject }}</p>
                                    <p class="mt-0.5 text-[11px] capitalize text-muted">{{ str_replace('_', ' ', $request->type->value) }}</p>
                                </td>
                                <td class="text-xs text-ink-soft">
                                    @if ($request->from_date)
                                        {{ $request->from_date->format('d M') }}@if ($request->to_date) – {{ $request->to_date->format('d M') }}@endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><x-status :value="$request->status" /></td>
                                <td>
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($request->status->value === 'pending')
                                            <button type="button" class="btn btn-primary btn-sm"
                                                    x-on:click="$dispatch('open-modal', 'request-review-{{ $request->id }}')">
                                                Review
                                            </button>
                                        @else
                                            <span class="text-xs text-muted">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-10 text-center text-sm text-muted">No requests match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $requests->links() }}</div>

        @foreach ($requests as $request)
            @if ($request->status->value === 'pending')
                <x-modal :name="'request-review-'.$request->id" title="Review request" :description="$request->subject">
                    <div class="mb-4 rounded-xl bg-paper p-4 text-sm ring-1 ring-line">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">{{ str_replace('_', ' ', $request->type->value) }}</p>
                        <p class="mt-1 leading-6 text-ink-soft">{{ $request->details ?: 'No additional details provided.' }}</p>
                        @if ($request->from_date)
                            <p class="mt-2 text-xs text-muted">
                                {{ $request->from_date->format('d M Y') }}@if ($request->to_date) → {{ $request->to_date->format('d M Y') }}@endif
                            </p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('requests.review', $request) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="label">Decision</label>
                            <select name="status" class="select">
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Note <span class="font-normal text-muted">(optional)</span></label>
                            <textarea name="review_note" class="textarea" rows="2" placeholder="Approved until Sunday 9 PM..."></textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                            <button class="btn btn-primary">Save decision</button>
                        </div>
                    </form>
                </x-modal>
            @endif
        @endforeach
    @else
        @if ($requests->isEmpty())
            <div class="mt-6">
                <x-empty illustration="ticket" title="No requests yet"
                         message="Leave, room changes and other permissions all start here.">
                    <button type="button" class="btn btn-primary btn-sm" x-on:click="$dispatch('open-modal', 'request-create')">Create a request</button>
                </x-empty>
            </div>
        @else
            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                @foreach ($requests as $request)
                    <article class="card rise p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-paper text-ink">
                                    <x-icon name="calendar" class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-ink">{{ $request->subject }}</p>
                                    <p class="mt-0.5 text-[11px] capitalize text-muted">{{ str_replace('_', ' ', $request->type->value) }} · {{ $request->created_at->format('d M Y') }}</p>
                                </div>
                            </div>
                            <x-status :value="$request->status" />
                        </div>

                        @if ($request->details)
                            <p class="mt-3 line-clamp-2 text-sm leading-6 text-ink-soft">{{ $request->details }}</p>
                        @endif

                        @if ($request->from_date)
                            <p class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-paper px-2.5 py-1.5 text-[11px] text-muted ring-1 ring-line">
                                <x-icon name="calendar" class="h-3.5 w-3.5" />
                                {{ $request->from_date->format('d M Y') }}@if ($request->to_date) → {{ $request->to_date->format('d M Y') }}@endif
                            </p>
                        @endif

                        @if ($request->review_note)
                            <p class="mt-3 rounded-lg bg-paper px-3 py-2 text-xs text-ink-soft ring-1 ring-line">
                                <span class="font-semibold">Office note:</span> {{ $request->review_note }}
                            </p>
                        @endif

                        @if ($request->status->value === 'pending')
                            <div class="mt-4 flex justify-end border-t border-line pt-3">
                                <form method="POST" action="{{ route('requests.cancel', $request) }}"
                                      onsubmit="return confirm('Cancel this request?');">
                                    @csrf
                                    <button class="btn btn-ghost btn-sm">Cancel request</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="mt-6">{{ $requests->links() }}</div>
        @endif

        <x-modal name="request-create" title="New request" description="Leave, room change or any other hostel permission.">
            <form method="POST" action="{{ route('requests.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="label">Type</label>
                    <select name="type" class="select" required>
                        <option value="leave">Leave</option>
                        <option value="room_change">Room change</option>
                        <option value="facility">Facility</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="label">Subject</label>
                    <input name="subject" class="input" placeholder="Weekend leave to visit family" required>
                </div>
                <div>
                    <label class="label">Details <span class="font-normal text-muted">(optional)</span></label>
                    <textarea name="details" class="textarea" rows="3" placeholder="Add any context the office should know"></textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">From</label>
                        <input name="from_date" type="date" class="input">
                    </div>
                    <div>
                        <label class="label">To</label>
                        <input name="to_date" type="date" class="input">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                    <button class="btn btn-primary">Submit request</button>
                </div>
            </form>
        </x-modal>
    @endif
@endsection
