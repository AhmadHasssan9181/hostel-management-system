@extends('layouts.app')

@section('title', 'Ticket '.$complaint->ticket_no)

@section('content')
    <div class="mb-4 flex items-center gap-1.5 text-xs text-muted">
        <a href="{{ route('complaints.index') }}" class="font-medium text-ink-soft hover:text-brand-700">Complaints</a>
        <x-icon name="chevron-right" class="h-3.5 w-3.5" />
        <span>{{ $complaint->ticket_no }}</span>
    </div>

    <x-page-header :title="ucfirst($complaint->category).' issue'"
                   :subtitle="'Filed '.$complaint->created_at->format('d M Y, H:i').' by '.($complaint->student?->name ?? 'a resident')"
                   eyebrow="Complaint detail">
        <x-status :value="$complaint->status" />
        <span class="text-line-strong">·</span>
        <x-status :value="$complaint->priority" />
        <a href="{{ route('complaints.ticket', $complaint) }}" target="_blank" class="btn btn-secondary btn-sm">
            <x-icon name="clipboard" class="h-3.5 w-3.5" />
            Print ticket
        </a>
    </x-page-header>

    @php
        $statusValue = $complaint->status->value;
        $assigned = (bool) $complaint->assigned_to;
        $inProgress = in_array($statusValue, ['in_progress', 'escalated', 'resolved', 'closed'], true);
        $resolved = in_array($statusValue, ['resolved', 'closed'], true);
        $closed = $statusValue === 'closed';

        $steps = [
            ['title' => 'Submitted', 'description' => 'Ticket created and queued for the office.', 'done' => true, 'at' => $complaint->created_at],
            ['title' => 'Assigned', 'description' => $complaint->assignee ? 'Handled by '.$complaint->assignee->name : 'Waiting for a staff member.', 'done' => $assigned, 'at' => $assigned ? $complaint->updated_at : null],
            ['title' => 'In progress', 'description' => 'Maintenance is working on the issue.', 'done' => $inProgress, 'at' => null],
            ['title' => 'Resolved', 'description' => $complaint->resolution_note ?: 'Resolution notes will appear here.', 'done' => $resolved, 'at' => $complaint->resolved_at],
            ['title' => 'Closed', 'description' => 'Ticket archived.', 'done' => $closed, 'at' => $complaint->closed_at],
        ];
    @endphp

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card rise rise-2 p-6">
                <h2 class="section-title">Reported issue</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-7 text-ink-soft">{{ $complaint->description }}</p>

                @if ($complaint->photo_url)
                    <div class="mt-5 overflow-hidden rounded-xl border border-line">
                        <img src="{{ $complaint->photo_url }}" alt="Complaint photo" class="max-h-[360px] w-full object-cover">
                    </div>
                @endif
            </section>

            <section class="card rise rise-3 p-6">
                <h2 class="section-title">Progress</h2>
                <ol class="mt-5 space-y-0">
                    @foreach ($steps as $step)
                        <li class="relative flex gap-4 pb-6 last:pb-0">
                            @if (! $loop->last)
                                <span class="absolute left-[11px] top-6 h-full w-px {{ $step['done'] ? 'bg-brand-300' : 'bg-line' }}"></span>
                            @endif
                            <span class="relative z-10 mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $step['done'] ? 'bg-brand-600 text-white dark:text-[#08211c]' : 'bg-surface text-muted ring-1 ring-line' }}">
                                @if ($step['done'])
                                    <x-icon name="check" class="h-3.5 w-3.5" />
                                @else
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                @endif
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold {{ $step['done'] ? 'text-ink' : 'text-muted' }}">{{ $step['title'] }}</p>
                                    @if ($step['at'])
                                        <span class="text-[11px] text-muted">{{ $step['at']->format('d M Y, H:i') }}</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-xs leading-5 text-muted">{{ $step['description'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card rise rise-3 p-6">
                <h2 class="section-title">Details</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Ticket</dt>
                        <dd class="font-semibold text-ink">{{ $complaint->ticket_no }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Resident</dt>
                        <dd class="text-right font-medium text-ink">{{ $complaint->student?->name }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Room</dt>
                        <dd class="text-right font-medium text-ink">
                            {{ $complaint->room?->room_number ?? '—' }}
                            @if ($complaint->room?->hostel)
                                <span class="block text-[11px] font-normal text-muted">{{ $complaint->room->hostel->name }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Category</dt>
                        <dd class="font-medium capitalize text-ink">{{ $complaint->category }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Priority</dt>
                        <dd><x-status :value="$complaint->priority" /></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Assigned to</dt>
                        <dd class="text-right font-medium text-ink">{{ $complaint->assignee?->name ?? 'Unassigned' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Resolved</dt>
                        <dd class="text-right font-medium text-ink">{{ $complaint->resolved_at?->format('d M Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            @if ($isStaff)
                <section class="card rise rise-4 p-6">
                    <h2 class="section-title">Assign</h2>
                    <form method="POST" action="{{ route('complaints.assign', $complaint) }}" class="mt-3 space-y-3">
                        @csrf
                        <select name="assigned_to" class="select" required>
                            <option value="">Choose a staff member</option>
                            @foreach ($staff as $member)
                                <option value="{{ $member->id }}" @selected($complaint->assigned_to === $member->id)>{{ $member->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-secondary w-full">Assign ticket</button>
                    </form>
                </section>

                <section class="card rise rise-5 p-6">
                    <h2 class="section-title">Update status</h2>
                    <form method="POST" action="{{ route('complaints.status', $complaint) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="select" required>
                            @foreach (['open' => 'Open', 'in_progress' => 'In progress', 'escalated' => 'Escalated', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $value => $label)
                                <option value="{{ $value }}" @selected($complaint->status->value === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea name="resolution_note" class="textarea" rows="3" placeholder="Add resolution notes (optional)">{{ $complaint->resolution_note }}</textarea>
                        <button class="btn btn-primary w-full">Save update</button>
                    </form>
                </section>

                <section class="card rise rise-5 p-6">
                    <h2 class="section-title">Edit ticket</h2>
                    <form method="POST" action="{{ route('complaints.update', $complaint) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                        @csrf
                        @method('PATCH')
                        <select name="category" class="select" required>
                            @foreach (['plumbing', 'electrical', 'wifi', 'cleanliness', 'furniture', 'mess', 'other'] as $category)
                                <option value="{{ $category }}" @selected($complaint->category === $category)>{{ ucfirst($category) }}</option>
                            @endforeach
                        </select>
                        <select name="priority" class="select" required>
                            @foreach (['low', 'normal', 'high', 'urgent'] as $priority)
                                <option value="{{ $priority }}" @selected($complaint->priority->value === $priority)>{{ ucfirst($priority) }} priority</option>
                            @endforeach
                        </select>
                        <textarea name="description" class="textarea" rows="4" required>{{ $complaint->description }}</textarea>
                        @if ($complaint->photo_url)
                            <img src="{{ $complaint->photo_url }}" alt="" class="h-20 w-full rounded-lg object-cover ring-1 ring-line">
                        @endif
                        <input type="file" name="photo" accept="image/*" class="input py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-paper file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-ink">
                        <button class="btn btn-secondary w-full">Save ticket</button>
                    </form>
                </section>
            @else
                <section class="card rise rise-4 p-6">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-paper text-ink ring-1 ring-line">
                            <x-icon name="bell" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-ink">You will be notified</p>
                            <p class="mt-1 text-xs leading-5 text-muted">
                                The hostel office updates this ticket as work progresses. Need to add more detail? Visit the office and quote {{ $complaint->ticket_no }}.
                            </p>
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection
