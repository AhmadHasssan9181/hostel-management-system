@extends('layouts.app')

@section('title', 'Complaints')

@section('content')
    <x-page-header
        :title="$isStaff ? 'Complaints' : 'My complaints'"
        :subtitle="$isStaff ? 'Triage tickets, assign staff and keep residents informed.' : 'Raise an issue and follow its progress from open to resolved.'"
        eyebrow="Services">
        @if (! $isStaff)
            <button type="button" class="btn btn-primary" x-on:click="$dispatch('open-modal', 'complaint-create')">
                <x-icon name="plus" class="h-4 w-4" />
                File a complaint
            </button>
        @endif
    </x-page-header>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Total tickets" :value="$stats['total']" hint="All time" icon="clipboard" tone="brand" :delay="1" />
        <x-stat label="Open" :value="$stats['open']" hint="Awaiting resolution" icon="wrench" tone="sky" :delay="2" />
        <x-stat label="Resolved" :value="$stats['resolved']" hint="Closed successfully" icon="check" tone="emerald" :delay="3" />
        <x-stat label="High priority" :value="$stats['urgent']" hint="Needs attention" icon="alert" tone="rose" :delay="4" />
    </div>

    @if ($isStaff)
        <form method="GET" action="{{ route('complaints.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
            <div class="min-w-[200px] flex-1">
                <label class="label" for="search">Search</label>
                <input id="search" name="search" value="{{ request('search') }}" class="input" placeholder="Ticket number or keyword">
            </div>
            <div class="w-full sm:w-40">
                <label class="label" for="status">Status</label>
                <select id="status" name="status" class="select">
                    <option value="">Any status</option>
                    @foreach (['open', 'in_progress', 'escalated', 'resolved', 'closed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-36">
                <label class="label" for="priority">Priority</label>
                <select id="priority" name="priority" class="select">
                    <option value="">Any</option>
                    @foreach (['low', 'normal', 'high', 'urgent'] as $priority)
                        <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-40">
                <label class="label" for="category">Category</label>
                <select id="category" name="category" class="select">
                    <option value="">All</option>
                    @foreach (['plumbing', 'electrical', 'wifi', 'cleanliness', 'furniture', 'mess', 'other'] as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ ucfirst($category) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
            @if (request()->hasAny(['search', 'status', 'priority', 'category']))
                <a href="{{ route('complaints.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="table-shell mt-5 rise rise-2">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Resident</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Handled by</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($complaints as $complaint)
                            <tr>
                                <td>
                                    <p class="text-sm font-semibold text-ink">{{ $complaint->ticket_no }}</p>
                                    <p class="text-[11px] text-muted">{{ $complaint->created_at->format('d M Y') }}</p>
                                </td>
                                <td>
                                    <p class="text-sm text-ink">{{ $complaint->student?->name }}</p>
                                    <p class="text-[11px] text-muted">Room {{ $complaint->room?->room_number ?? '—' }}</p>
                                </td>
                                <td class="capitalize text-ink-soft">{{ $complaint->category }}</td>
                                <td><x-status :value="$complaint->priority" /></td>
                                <td><x-status :value="$complaint->status" /></td>
                                <td class="text-xs text-ink-soft">{{ $complaint->assignee?->name ?? 'Unassigned' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-secondary btn-sm">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-10 text-center text-sm text-muted">No complaints match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $complaints->links() }}</div>
    @else
        @if ($complaints->isEmpty())
            <div class="mt-6">
                <x-empty illustration="ticket" title="No complaints filed"
                         message="If something in your room or block needs fixing, raise a ticket and the office will handle it.">
                    <button type="button" class="btn btn-primary btn-sm" x-on:click="$dispatch('open-modal', 'complaint-create')">File a complaint</button>
                </x-empty>
            </div>
        @else
            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                @foreach ($complaints as $complaint)
                    <a href="{{ route('complaints.show', $complaint) }}"
                       class="card card-hover rise block overflow-hidden p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-ink">{{ ucfirst($complaint->category) }} issue</p>
                                <p class="mt-0.5 text-[11px] text-muted">{{ $complaint->ticket_no }} · {{ $complaint->created_at->format('d M Y') }}</p>
                            </div>
                            <x-status :value="$complaint->status" />
                        </div>

                        <p class="mt-3 line-clamp-2 text-sm leading-6 text-ink-soft">{{ $complaint->description }}</p>

                        <div class="mt-4 flex items-center justify-between border-t border-line pt-3">
                            <div class="flex items-center gap-2 text-xs text-muted">
                                <x-status :value="$complaint->priority" />
                                @if ($complaint->assignee)
                                    <span>· {{ $complaint->assignee->name }}</span>
                                @endif
                            </div>
                            <span class="text-xs font-medium text-ink-soft">View ticket →</span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">{{ $complaints->links() }}</div>
        @endif

        <x-modal name="complaint-create" title="File a complaint" description="Describe the issue and the hostel office will assign someone." maxWidth="max-w-xl">
            <form method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Category</label>
                        <select name="category" class="select" required>
                            @foreach (['plumbing' => 'Plumbing', 'electrical' => 'Electrical', 'wifi' => 'Wi-Fi / Internet', 'cleanliness' => 'Cleanliness', 'furniture' => 'Furniture', 'mess' => 'Mess / Food', 'other' => 'Other'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Priority</label>
                        <select name="priority" class="select">
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">What is wrong?</label>
                        <textarea name="description" class="textarea" rows="4" placeholder="Give as much detail as you can, including where the issue is." required></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Photo <span class="font-normal text-muted">(optional, max 5 MB)</span></label>
                        <input type="file" name="photo" accept="image/*" class="input py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-paper file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-ink">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                    <button class="btn btn-primary">Submit complaint</button>
                </div>
            </form>
        </x-modal>
    @endif
@endsection
