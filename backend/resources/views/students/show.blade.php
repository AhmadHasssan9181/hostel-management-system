@extends('layouts.app')

@section('title', $student->name)

@section('content')
    @php
        $profile = $student->studentProfile;
        $currency = fn ($value) => 'Rs '.number_format((float) $value);
        $outstanding = $student->fees
            ->whereIn('status', [\App\Enums\FeeStatus::Unpaid, \App\Enums\FeeStatus::Overdue, \App\Enums\FeeStatus::PendingVerification])
            ->sum(fn ($fee) => (float) $fee->amount + (float) $fee->fine);
        $openComplaints = $student->complaints->filter(fn ($complaint) => ! $complaint->isResolved())->count();
    @endphp

    <div class="mb-4 flex items-center gap-1.5 text-xs text-muted">
        <a href="{{ route('students.index') }}" class="font-medium text-ink-soft hover:text-brand-700">Students</a>
        <x-icon name="chevron-right" class="h-3.5 w-3.5" />
        <span>{{ $student->name }}</span>
    </div>

    <div class="card rise overflow-hidden">
        <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-brand-200 text-xl font-semibold uppercase text-brand-800">
                    {{ str($student->name)->explode(' ')->take(2)->map(fn ($p) => str($p)->substr(0, 1))->implode('') }}
                </span>
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-ink">{{ $student->name }}</h1>
                    <p class="mt-1 text-sm text-muted">{{ $profile?->registration_number ?? 'Registration pending' }} · {{ $profile?->department ?? 'No department' }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
                        <x-status :value="$profile?->check_in_status ?? 'pending'" />
                        <span class="text-line-strong">·</span>
                        <x-status :value="$profile?->discipline_status ?? 'clear'" />
                        <span class="text-line-strong">·</span>
                        <x-status :value="$student->status" />
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-secondary" x-on:click="$dispatch('open-modal', 'student-edit')">
                <x-icon name="user" class="h-4 w-4" />
                Edit record
            </button>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Current room" :value="$student->activeBooking?->room?->room_number ?? '—'"
                :hint="$student->activeBooking?->room?->hostel?->name ?? 'Not allocated'" icon="bed" tone="brand" :delay="1" />
        <x-stat label="Outstanding fees" :value="$currency($outstanding)" hint="Unpaid or awaiting verification" icon="wallet" tone="amber" :delay="2" />
        <x-stat label="Open complaints" :value="$openComplaints" hint="Tickets in progress" icon="wrench" tone="sky" :delay="3" />
        <x-stat label="Bookings" :value="$student->bookings->count()" hint="All time" icon="clipboard" tone="emerald" :delay="4" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card rise rise-2 overflow-hidden">
                <div class="border-b border-line px-6 py-4">
                    <h2 class="section-title">Booking history</h2>
                    <p class="text-xs text-muted">Room allocations and their outcomes</p>
                </div>
                @if ($student->bookings->isEmpty())
                    <p class="px-6 py-8 text-center text-sm text-muted">No bookings recorded.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Block</th>
                                    <th>Requested</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($student->bookings as $booking)
                                    <tr>
                                        <td class="font-semibold text-ink">{{ $booking->room?->room_number ?? '—' }}</td>
                                        <td class="text-xs text-ink-soft">{{ $booking->room?->hostel?->name }}</td>
                                        <td class="text-xs text-muted">{{ $booking->created_at->format('d M Y') }}</td>
                                        <td><x-status :value="$booking->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="card rise rise-3 overflow-hidden">
                <div class="border-b border-line px-6 py-4">
                    <h2 class="section-title">Fees</h2>
                    <p class="text-xs text-muted">Invoice history for this resident</p>
                </div>
                @if ($student->fees->isEmpty())
                    <p class="px-6 py-8 text-center text-sm text-muted">No invoices generated yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Due</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($student->fees->sortByDesc('created_at') as $fee)
                                    <tr>
                                        <td class="text-xs font-semibold text-ink">{{ $fee->reference }}</td>
                                        <td class="text-xs capitalize text-ink-soft">{{ str_replace('_', ' ', $fee->type) }}</td>
                                        <td class="text-xs font-semibold text-ink">{{ $currency($fee->total()) }}</td>
                                        <td class="text-xs text-muted">{{ $fee->due_date->format('d M Y') }}</td>
                                        <td><x-status :value="$fee->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="card rise rise-4 overflow-hidden">
                <div class="border-b border-line px-6 py-4">
                    <h2 class="section-title">Complaints</h2>
                    <p class="text-xs text-muted">Reported issues</p>
                </div>
                @if ($student->complaints->isEmpty())
                    <p class="px-6 py-8 text-center text-sm text-muted">No complaints filed.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($student->complaints->sortByDesc('created_at') as $complaint)
                            <li>
                                <a href="{{ route('complaints.show', $complaint) }}" class="flex items-center justify-between gap-3 px-6 py-3.5 transition hover:bg-paper">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium capitalize text-ink">{{ $complaint->category }} · {{ $complaint->ticket_no }}</p>
                                        <p class="text-[11px] text-muted">{{ $complaint->created_at->format('d M Y') }}</p>
                                    </div>
                                    <x-status :value="$complaint->status" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            <section class="card rise rise-3 p-6">
                <h2 class="section-title">Contact & profile</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Email</dt>
                        <dd class="mt-0.5 break-all font-medium text-ink">{{ $student->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Phone</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ $student->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Address</dt>
                        <dd class="mt-0.5 text-ink-soft">{{ $profile?->address ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Guardian</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ $profile?->guardian_name ?? '—' }}</dd>
                        @if ($profile?->guardian_phone)
                            <dd class="text-xs text-muted">{{ $profile->guardian_phone }}</dd>
                        @endif
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Semester</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ $profile?->semester ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Medical notes</dt>
                        <dd class="mt-0.5 text-ink-soft">{{ $profile?->medical_notes ?? 'None recorded' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="card rise rise-4 p-6">
                <h2 class="section-title">Requests</h2>
                <ul class="mt-3 divide-y divide-line">
                    @forelse ($student->studentRequests->sortByDesc('created_at')->take(4) as $request)
                        <li class="flex items-center justify-between gap-2 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-medium text-ink">{{ $request->subject }}</p>
                                <p class="text-[10px] capitalize text-muted">{{ str_replace('_', ' ', $request->type->value) }}</p>
                            </div>
                            <x-status :value="$request->status" />
                        </li>
                    @empty
                        <li class="py-4 text-center text-xs text-muted">No requests.</li>
                    @endforelse
                </ul>

                <h3 class="mt-5 text-[11px] font-semibold uppercase tracking-wide text-muted">Room swaps</h3>
                <ul class="mt-2 divide-y divide-line">
                    @forelse ($student->roomSwapRequests->sortByDesc('created_at')->take(3) as $swap)
                        <li class="flex items-center justify-between gap-2 py-2.5">
                            <p class="text-[13px] font-medium text-ink">
                                {{ $swap->currentRoom?->room_number ?? '—' }} → {{ $swap->requestedRoom?->room_number ?? '—' }}
                            </p>
                            <x-status :value="$swap->status" />
                        </li>
                    @empty
                        <li class="py-4 text-center text-xs text-muted">No swap requests.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>

    <x-modal name="student-edit" title="Edit student record" :description="$student->name" maxWidth="max-w-xl">
        <form method="POST" action="{{ route('students.update', $student) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label">Registration number</label>
                    <input name="registration_number" value="{{ $profile?->registration_number }}" class="input">
                </div>
                <div>
                    <label class="label">Department</label>
                    <input name="department" value="{{ $profile?->department }}" class="input">
                </div>
                <div>
                    <label class="label">Semester</label>
                    <input name="semester" type="number" min="1" max="12" value="{{ $profile?->semester }}" class="input">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Address</label>
                    <input name="address" value="{{ $profile?->address }}" class="input">
                </div>
                <div>
                    <label class="label">Guardian name</label>
                    <input name="guardian_name" value="{{ $profile?->guardian_name }}" class="input">
                </div>
                <div>
                    <label class="label">Guardian phone</label>
                    <input name="guardian_phone" value="{{ $profile?->guardian_phone }}" class="input">
                </div>
                <div>
                    <label class="label">Check-in status</label>
                    <select name="check_in_status" class="select">
                        @foreach (['pending', 'checked_in', 'checked_out'] as $status)
                            <option value="{{ $status }}" @selected(($profile?->check_in_status ?? 'pending') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Discipline</label>
                    <select name="discipline_status" class="select">
                        @foreach (['clear', 'warning', 'probation'] as $status)
                            <option value="{{ $status }}" @selected(($profile?->discipline_status ?? 'clear') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Medical notes</label>
                    <textarea name="medical_notes" class="textarea" rows="2">{{ $profile?->medical_notes }}</textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                <button class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </x-modal>
@endsection
