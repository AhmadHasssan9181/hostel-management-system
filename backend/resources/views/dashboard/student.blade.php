@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $firstName = str($user->name)->before(' ');
        $currency = fn ($value) => 'Rs '.number_format((float) $value);
    @endphp

    <x-page-header
        :title="$greeting.', '.$firstName"
        subtitle="Here is what is happening with your stay today."
        eyebrow="Student portal">
        <a href="{{ route('rooms.index') }}" class="btn btn-primary">
            <x-icon name="bed" class="h-4 w-4" />
            {{ $booking ? 'Browse rooms' : 'Find a room' }}
        </a>
        <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
            <x-icon name="wrench" class="h-4 w-4" />
            Report an issue
        </a>
    </x-page-header>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Current room" :value="$booking?->room?->room_number ?? '—'"
                :hint="$booking?->room?->hostel?->name ?? 'No active allocation'" icon="bed" :delay="1" />
        <x-stat label="Outstanding dues" :value="$currency($outstanding)"
                :hint="$nextDue ? 'Next due '.$nextDue->due_date->format('d M') : 'Nothing pending'" icon="wallet" :delay="2" />
        <x-stat label="Open complaints" :value="$openComplaints" hint="Tickets awaiting resolution" icon="wrench" :delay="3" />
        <x-stat label="Notifications" :value="$unread" hint="Unread updates for you" icon="bell" :delay="4" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card rise rise-2 overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-6 py-4">
                    <div>
                        <h2 class="section-title">Your room</h2>
                        <p class="text-xs text-muted">Allocation details for this semester</p>
                    </div>
                    @if ($booking)
                        <x-status :value="$booking->status" />
                    @endif
                </div>

                @if ($booking && $booking->room)
                    <div class="relative h-44 overflow-hidden bg-paper">
                        <img src="{{ $booking->room->image_url ?? asset('images/rooms/standard.jpg') }}"
                             alt="Room {{ $booking->room->room_number }}"
                             loading="lazy"
                             class="h-full w-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/5 to-transparent"></div>
                        <div class="absolute inset-x-6 bottom-4 flex items-end justify-between gap-4">
                            <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-white/80 [text-shadow:0_1px_2px_rgba(0,0,0,0.55)]">
                                {{ $booking->room->hostel?->name }} · Floor {{ $booking->room->floor }}
                            </p>
                            <p class="num text-[11px] font-medium text-white/75">{{ $booking->room->room_type }}</p>
                        </div>
                    </div>
                @endif

                @if ($booking && $booking->room)
                    <div class="grid gap-6 p-6 sm:grid-cols-[1.2fr_1fr]">
                        <div>
                            <p class="text-[11px] font-medium text-muted">
                                {{ $booking->room->hostel?->name ?? 'Hostel' }}
                                <span class="meta-sep capitalize">{{ $booking->room->room_type }}</span>
                            </p>
                            <p class="num mt-1 text-4xl font-semibold tracking-tight text-ink">Room {{ $booking->room->room_number }}</p>
                            <p class="mt-1 text-xs text-muted">
                                Floor {{ $booking->room->floor }}
                                <span class="meta-sep">{{ $booking->room->capacity }} beds</span>
                                <span class="meta-sep">{{ $booking->room->occupied_beds }} occupied</span>
                            </p>

                            <div class="mt-5">
                                <div class="flex items-center justify-between text-xs text-muted">
                                    <span>Bed occupancy</span>
                                    <span class="num font-medium text-ink">{{ $booking->room->occupied_beds }} / {{ $booking->room->capacity }}</span>
                                </div>
                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-paper">
                                    <div class="bar-grow h-full rounded-full bg-brand-600"
                                         style="width: {{ $booking->room->capacity ? round($booking->room->occupied_beds / $booking->room->capacity * 100) : 0 }}%"></div>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <a href="{{ route('bookings.index') }}" class="btn btn-secondary btn-sm">Booking history</a>
                                <a href="{{ route('swaps.index') }}" class="btn btn-ghost btn-sm">
                                    Request a swap
                                    <x-icon name="swap" class="h-3.5 w-3.5" />
                                </a>
                            </div>
                        </div>

                        <dl class="grid grid-cols-2 gap-4 rounded-xl bg-canvas p-4 text-sm ring-1 ring-line">
                            <div>
                                <dt class="text-[11px] text-muted">Monthly rent</dt>
                                <dd class="num mt-1 font-semibold text-ink">{{ $currency($booking->room->monthly_rent) }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-muted">Check-in</dt>
                                <dd class="mt-1 font-semibold capitalize text-ink">{{ str_replace('_', ' ', $user->studentProfile?->check_in_status ?? 'pending') }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-muted">Allocated on</dt>
                                <dd class="mt-1 font-semibold text-ink">{{ $booking->reviewed_at?->format('d M Y') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-muted">Semester</dt>
                                <dd class="num mt-1 font-semibold text-ink">{{ $user->studentProfile?->semester ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                @else
                    <div class="p-6">
                        <x-empty illustration="room" title="You do not have a room yet"
                                 message="Browse the available rooms and submit a booking request. The hostel office will review it shortly.">
                            <a href="{{ route('rooms.index') }}" class="btn btn-primary btn-sm">Browse available rooms</a>
                        </x-empty>
                    </div>
                @endif
            </section>

            <section class="card rise rise-3 overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-6 py-4">
                    <div>
                        <h2 class="section-title">Recent complaints</h2>
                        <p class="text-xs text-muted">Track what you have reported</p>
                    </div>
                    <a href="{{ route('complaints.index') }}" class="text-xs font-medium text-ink-soft hover:text-ink">View all</a>
                </div>

                @if ($recentComplaints->isEmpty())
                    <div class="p-6">
                        <x-empty illustration="ticket" title="No complaints filed"
                                 message="Everything looks good. If something breaks, raise a ticket and track it here.">
                            <a href="{{ route('complaints.index') }}" class="btn btn-secondary btn-sm">File a complaint</a>
                        </x-empty>
                    </div>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($recentComplaints as $complaint)
                            <li>
                                <a href="{{ route('complaints.show', $complaint) }}" class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-canvas/70">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-ink">
                                            {{ ucfirst($complaint->category) }} issue
                                            <span class="meta-sep font-normal text-muted">{{ ucfirst($complaint->priority->value) }} priority</span>
                                        </p>
                                        <p class="mt-0.5 truncate text-xs text-muted">{{ $complaint->ticket_no }} · {{ $complaint->created_at->diffForHumans() }}</p>
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
            <section class="card rise rise-3 overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="section-title">Fees</h2>
                    <a href="{{ route('fees.index') }}" class="text-xs font-medium text-ink-soft hover:text-ink">Open</a>
                </div>

                <div class="px-5 pb-3 pt-4">
                    <p class="text-xs text-muted">Outstanding balance</p>
                    <p class="num mt-1 text-2xl font-semibold tracking-tight text-ink">{{ $currency($outstanding) }}</p>
                    @if ($nextDue)
                        <p class="mt-1 text-xs text-muted">Due {{ $nextDue->due_date->format('d M Y') }} · {{ $nextDue->reference }}</p>
                    @endif
                </div>

                <ul class="divide-y divide-line border-t border-line">
                    @forelse ($recentFees as $fee)
                        <li class="flex items-center justify-between gap-3 px-5 py-3.5">
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-medium text-ink">{{ ucfirst(str_replace('_', ' ', $fee->type)) }}</p>
                                <p class="mt-0.5 text-[11px] text-muted">{{ $fee->period ?? $fee->due_date->format('M Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="num text-[13px] font-semibold text-ink">{{ $currency($fee->total()) }}</p>
                                <x-status :value="$fee->status" class="mt-0.5 justify-end" />
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">No invoices yet.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card rise rise-4 overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="section-title">Facilities</h2>
                    <a href="{{ route('facilities.index') }}" class="text-xs font-medium text-ink-soft hover:text-ink">All</a>
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($facilities->take(5) as $facility)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <img src="{{ $facility->image_url ?? asset('images/facilities/common.jpg') }}"
                                 alt="{{ $facility->name }}"
                                 loading="lazy"
                                 class="h-9 w-9 shrink-0 rounded-lg object-cover ring-1 ring-line">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-medium text-ink">{{ $facility->name }}</p>
                                <p class="num text-[11px] text-muted">
                                    @if ($facility->opens_at)
                                        {{ substr($facility->opens_at, 0, 5) }} – {{ substr($facility->closes_at, 0, 5) }}
                                    @else
                                        Open all day
                                    @endif
                                </p>
                                @if ($facility->status !== \App\Enums\FacilityStatus::Operational)
                                    <p class="text-[10px] font-medium text-amber-600 dark:text-amber-300">
                                        {{ $facility->status === \App\Enums\FacilityStatus::Maintenance ? 'Under maintenance' : 'Temporarily closed' }}
                                    </p>
                                @endif
                            </div>
                            <x-icon name="chevron-right" class="h-4 w-4 text-muted" />
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">No facilities listed.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card rise rise-5 overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="section-title">Updates</h2>
                    <a href="{{ route('notifications.index') }}" class="text-xs font-medium text-ink-soft hover:text-ink">All</a>
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($notifications as $notification)
                        <li class="px-5 py-3">
                            <div class="flex items-start gap-2.5">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full {{ $notification->read_at ? 'bg-line-strong' : 'bg-brand-600' }}"></span>
                                <div class="min-w-0">
                                    <p class="text-[13px] font-medium leading-5 text-ink">{{ $notification->title }}</p>
                                    <p class="mt-0.5 text-[11px] text-muted">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">No updates yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
@endsection
