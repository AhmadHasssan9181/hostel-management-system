@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $currency = fn ($value) => 'Rs '.number_format((float) $value);
        $circumference = 2 * M_PI * 52;
        $dashOffset = $circumference * (1 - min(100, $occupancyRate) / 100);
    @endphp

    <x-page-header
        title="Operations overview"
        subtitle="A live picture of occupancy, fees and resident requests across both hostels."
        eyebrow="Staff workspace">
        <a href="{{ route('bookings.index', ['status' => 'pending']) }}" class="btn btn-secondary">
            <x-icon name="clipboard" class="h-4 w-4" />
            Review bookings
            @if ($pendingBookings > 0)
                <span class="num rounded-md bg-paper px-1.5 py-0.5 text-[11px] font-semibold text-ink">{{ $pendingBookings }}</span>
            @endif
        </a>
        <a href="{{ route('fees.index') }}" class="btn btn-primary">
            <x-icon name="wallet" class="h-4 w-4" />
            Generate invoice
        </a>
    </x-page-header>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Bed occupancy" :value="$occupancyRate.'%'"
                :hint="$bedsOccupied.' of '.$bedsTotal.' beds · '.$roomsAvailable.' rooms with space'"
                icon="bed" :delay="1" />
        <x-stat label="Pending bookings" :value="$pendingBookings" hint="Waiting for approval" icon="clipboard" :delay="2" />
        <x-stat label="Open complaints" :value="$openComplaints"
                :hint="$urgentComplaints.' high priority · '.$unassignedComplaints.' unassigned'"
                icon="wrench" :delay="3" />
        <x-stat label="Outstanding fees" :value="$currency($outstanding)"
                :hint="$pendingVerification.' payment(s) to verify'" icon="banknotes" :delay="4" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card rise rise-2 p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="section-title">Occupancy</h2>
                    <p class="mt-0.5 text-xs text-muted">{{ $bedsTotal }} total beds · {{ $roomsTotal }} rooms</p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-center">
                <div class="relative h-44 w-44">
                    <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="var(--hms-line)" stroke-width="10" />
                        <circle cx="60" cy="60" r="52" fill="none" stroke="var(--hms-brand-600)" stroke-width="10"
                                class="ring-draw"
                                stroke-linecap="round" stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $dashOffset }}" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="num text-3xl font-semibold tracking-tight text-ink" data-count="{{ $occupancyRate }}%">{{ $occupancyRate }}%</span>
                        <span class="text-[10px] font-semibold uppercase tracking-[0.16em] text-muted">Occupied</span>
                    </div>
                </div>
            </div>

            <dl class="mt-6 grid grid-cols-3 gap-2 border-t border-line pt-4 text-center">
                <div>
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Beds</dt>
                    <dd class="num mt-1 text-sm font-semibold text-ink">{{ $bedsTotal }}</dd>
                </div>
                <div class="border-x border-line">
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Occupied</dt>
                    <dd class="num mt-1 text-sm font-semibold text-ink">{{ $bedsOccupied }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Free</dt>
                    <dd class="num mt-1 text-sm font-semibold text-ink">{{ max(0, $bedsTotal - $bedsOccupied) }}</dd>
                </div>
            </dl>
        </section>

        <section class="card rise rise-3 p-6 xl:col-span-2">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="section-title">Fee collections</h2>
                    <p class="mt-0.5 text-xs text-muted">Verified payments over the last six months</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-medium uppercase tracking-[0.14em] text-muted">This month</p>
                    <p class="num text-lg font-semibold tracking-tight text-ink" data-count="{{ $currency($collected) }}">{{ $currency($collected) }}</p>
                </div>
            </div>

            <div class="mt-8 flex items-end gap-3 sm:gap-5">
                @foreach ($monthly as $month)
                    @php
                        $height = $monthlyMax > 0 ? max(4, round($month['total'] / $monthlyMax * 100)) : 4;
                        $isCurrent = $loop->last;
                    @endphp
                    <div class="group flex flex-1 flex-col items-center gap-2">
                        <div class="relative h-36 w-full overflow-hidden rounded-lg bg-canvas ring-1 ring-line/80">
                            <div class="bar-rise absolute inset-x-0 bottom-0 rounded-t-md {{ $isCurrent ? 'bg-brand-600' : 'bg-brand-300 group-hover:bg-brand-500' }}"
                                 style="height: {{ $height }}%"
                                 title="{{ $month['label'] }}: {{ $currency($month['total']) }}"></div>
                        </div>
                        <span class="text-[10px] font-medium uppercase tracking-wide {{ $isCurrent ? 'font-semibold text-ink' : 'text-muted' }}">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 grid grid-cols-3 gap-3 border-t border-line pt-4">
                <div>
                    <p class="text-[10px] font-medium uppercase tracking-wide text-muted">Collected this month</p>
                    <p class="num mt-1 text-sm font-semibold text-ink">{{ $currency($collected) }}</p>
                </div>
                <div class="border-x border-line px-3">
                    <p class="text-[10px] font-medium uppercase tracking-wide text-muted">Outstanding</p>
                    <p class="num mt-1 text-sm font-semibold text-ink">{{ $currency($outstanding) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-medium uppercase tracking-wide text-muted">To verify</p>
                    <p class="num mt-1 text-sm font-semibold text-ink">{{ $pendingVerification }} payment(s)</p>
                </div>
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card rise rise-3 overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-6 py-4">
                <div>
                    <h2 class="section-title">Bookings to review</h2>
                    <p class="text-xs text-muted">Approve or reject room requests</p>
                </div>
                <a href="{{ route('bookings.index') }}" class="text-xs font-medium text-ink-soft hover:text-ink">View all</a>
            </div>

            @if ($pendingBookingList->isEmpty())
                <div class="p-6">
                    <x-empty illustration="ticket" title="All caught up" message="There are no pending booking requests right now." />
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($pendingBookingList as $booking)
                        <li class="flex items-center gap-3 px-6 py-4">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-paper text-[11px] font-semibold uppercase text-ink">
                                {{ str($booking->student?->name ?? '?')->explode(' ')->take(2)->map(fn ($p) => str($p)->substr(0, 1))->implode('') }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ $booking->student?->name }}</p>
                                <p class="truncate text-xs text-muted">
                                    Room {{ $booking->room?->room_number }} · {{ $booking->room?->hostel?->name }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <form method="POST" action="{{ route('bookings.approve', $booking) }}">
                                    @csrf
                                    <button class="btn btn-primary btn-sm">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('bookings.reject', $booking) }}">
                                    @csrf
                                    <button class="btn btn-secondary btn-sm">Reject</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="card rise rise-4 overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-6 py-4">
                <div>
                    <h2 class="section-title">Complaint queue</h2>
                    <p class="text-xs text-muted">Newest unresolved tickets</p>
                </div>
                <a href="{{ route('complaints.index') }}" class="text-xs font-medium text-ink-soft hover:text-ink">View all</a>
            </div>

            @if ($recentComplaints->isEmpty())
                <div class="p-6">
                    <x-empty illustration="ticket" title="No open tickets" message="Every complaint has been resolved. Nice work." />
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($recentComplaints as $complaint)
                        <li>
                            <a href="{{ route('complaints.show', $complaint) }}" class="flex items-center justify-between gap-3 px-6 py-4 transition hover:bg-canvas/70">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-ink">{{ ucfirst($complaint->category) }} · {{ $complaint->student?->name }}</p>
                                    <p class="truncate text-xs text-muted">{{ $complaint->ticket_no }} · Room {{ $complaint->room?->room_number }}</p>
                                </div>
                                <x-status :value="$complaint->priority" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="card rise rise-4 overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="section-title">Low stock</h2>
                <a href="{{ route('inventory.index', ['low_stock' => 1]) }}" class="text-xs font-medium text-ink-soft hover:text-ink">Inventory</a>
            </div>
            <ul class="divide-y divide-line">
                @forelse ($lowStockItems as $item)
                    <li class="flex items-center justify-between gap-3 px-5 py-3.5">
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $item->name }}</p>
                            <p class="text-[11px] text-muted">{{ $item->hostel?->name ?? 'All blocks' }}</p>
                        </div>
                        <span class="num text-xs font-semibold text-amber-700 dark:text-amber-300">
                            {{ $item->quantity }} <span class="font-normal text-muted">/ {{ $item->threshold }}</span>
                        </span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted">Stock levels look healthy.</li>
                @endforelse
            </ul>
        </section>

        <section class="card rise rise-5 overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="section-title">Facilities</h2>
                <a href="{{ route('facilities.index') }}" class="text-xs font-medium text-ink-soft hover:text-ink">Manage</a>
            </div>
            <ul class="divide-y divide-line">
                @forelse ($facilityIssues as $facility)
                    <li class="flex items-center gap-3 px-5 py-3.5">
                        <img src="{{ $facility->image_url ?? asset('images/facilities/common.jpg') }}"
                             alt="{{ $facility->name }}"
                             loading="lazy"
                             class="h-8 w-8 shrink-0 rounded-lg object-cover ring-1 ring-line">
                        <p class="min-w-0 flex-1 truncate text-[13px] font-medium text-ink">{{ $facility->name }}</p>
                        <x-status :value="$facility->status" />
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted">All facilities are operational.</li>
                @endforelse
            </ul>
            <div class="border-t border-line px-5 py-3">
                <p class="num text-[11px] text-muted">{{ $pendingSwaps }} swap · {{ $pendingRequests }} leave request(s) pending</p>
            </div>
        </section>

        <section class="card rise rise-5 overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="section-title">New residents</h2>
                <a href="{{ route('students.index') }}" class="text-xs font-medium text-ink-soft hover:text-ink">Directory</a>
            </div>
            <ul class="divide-y divide-line">
                @forelse ($recentStudents as $student)
                    <li>
                        <a href="{{ route('students.show', $student) }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-canvas/70">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-paper text-[10px] font-semibold uppercase text-ink">
                                {{ str($student->name)->explode(' ')->take(2)->map(fn ($p) => str($p)->substr(0, 1))->implode('') }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-medium text-ink">{{ $student->name }}</p>
                                <p class="truncate text-[11px] text-muted">{{ $student->studentProfile?->registration_number ?? 'No registration' }}</p>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted">No students registered yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
