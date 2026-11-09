@extends('layouts.app')

@section('title', 'Rooms')

@section('content')
    @php($isStaff = auth()->user()->isStaff())

    <x-page-header
        :title="$isStaff ? 'Rooms' : 'Browse rooms'"
        :subtitle="$isStaff ? 'Manage bed capacity, rent and availability across every block.' : 'Pick a room that suits you and send a request to the hostel office.'"
        eyebrow="Residence">
        @if ($isStaff)
            <button type="button" class="btn btn-primary" x-on:click="$dispatch('open-modal', 'room-create')">
                <x-icon name="plus" class="h-4 w-4" />
                Add room
            </button>
        @endif
        <a href="{{ route('hostels.index') }}" class="btn btn-secondary {{ $isStaff ? '' : 'hidden' }}">
            <x-icon name="building" class="h-4 w-4" />
            Blocks
        </a>
    </x-page-header>

    @if (! $isStaff)
        <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <x-stat label="Available rooms" :value="$stats['available']" hint="Across active blocks" icon="bed" :delay="1" />
            <x-stat label="Total beds" :value="$stats['beds']" hint="Hostel capacity" icon="building" :delay="2" />
            <x-stat label="Free beds" :value="$stats['free']" hint="Ready to allocate" icon="check" :delay="3" />
            <x-stat label="Hostel blocks" :value="$stats['hostels']" hint="Boys and girls" icon="storefront" :delay="4" />
        </div>

        @if ($activeBooking)
            <div class="mt-6 flex flex-col gap-3 rounded-2xl border border-line bg-surface px-5 py-4 shadow-soft sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-ink">
                        You are allocated to room {{ $activeBooking->room?->room_number }} · {{ $activeBooking->room?->hostel?->name }}
                    </p>
                    <p class="text-xs text-muted">Browse below, but you already hold an active allocation.</p>
                </div>
                <a href="{{ route('bookings.index') }}" class="btn btn-secondary btn-sm shrink-0">View booking</a>
            </div>
        @elseif ($hasActiveRequest)
            <div class="mt-6 rounded-2xl border border-line bg-surface px-5 py-4 shadow-soft">
                <p class="text-sm font-medium text-ink">You already have a pending room request</p>
                <p class="text-xs text-muted">Wait for the hostel office to review it before requesting another room.</p>
            </div>
        @endif
    @else
        <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <x-stat label="Rooms" :value="$stats['total']" hint="All blocks" icon="bed" :delay="1" />
            <x-stat label="With free beds" :value="$stats['available']" hint="Ready to allocate" icon="check" :delay="2" />
            <x-stat label="Full" :value="$stats['full']" hint="At capacity" icon="users" :delay="3" />
            <x-stat label="Maintenance" :value="$stats['maintenance']" hint="Out of service" icon="wrench" :delay="4" />
        </div>
    @endif

    <form method="GET" action="{{ route('rooms.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <div class="min-w-[180px] flex-1">
            <label class="label" for="search">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" class="input" placeholder="Room number or block">
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
        <div class="w-full sm:w-40">
            <label class="label" for="room_type">Type</label>
            <select id="room_type" name="room_type" class="select">
                <option value="">All types</option>
                @foreach (['standard', 'deluxe', 'single', 'shared'] as $type)
                    <option value="{{ $type }}" @selected(request('room_type') === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>
        @if ($isStaff)
            <div class="w-full sm:w-40">
                <label class="label" for="status">Status</label>
                <select id="status" name="status" class="select">
                    <option value="">Any status</option>
                    @foreach (['available', 'full', 'maintenance'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <button class="btn btn-secondary">
            <x-icon name="filter" class="h-4 w-4" />
            Filter
        </button>
        @if (request()->hasAny(['search', 'hostel_id', 'room_type', 'status']))
            <a href="{{ route('rooms.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>

    @if ($isStaff)
        <div class="table-shell mt-5 rise rise-2">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Block</th>
                            <th>Type</th>
                            <th>Beds</th>
                            <th>Rent</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rooms as $room)
                            <tr>
                                <td>
                                    <a href="{{ route('rooms.show', $room) }}" class="flex items-center gap-3">
                                        <img src="{{ $room->image_url ?? asset('images/rooms/standard.jpg') }}"
                                             alt="Room {{ $room->room_number }}"
                                             loading="lazy"
                                             class="h-10 w-14 shrink-0 rounded-lg object-cover ring-1 ring-line">
                                        <div>
                                            <p class="num font-semibold text-ink hover:text-brand-700 dark:hover:text-brand-300">{{ $room->room_number }}</p>
                                            <p class="text-xs text-muted">Floor {{ $room->floor }}</p>
                                        </div>
                                    </a>
                                </td>
                                <td class="text-ink-soft">{{ $room->hostel?->name }}</td>
                                <td class="capitalize text-ink-soft">{{ $room->room_type }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-16 overflow-hidden rounded-full bg-paper">
                                            <div class="bar-grow h-full rounded-full {{ $room->occupied_beds >= $room->capacity ? 'bg-rose-500' : 'bg-brand-600' }}"
                                                 style="width: {{ $room->capacity ? round($room->occupied_beds / $room->capacity * 100) : 0 }}%"></div>
                                        </div>
                                        <span class="num text-xs text-muted">{{ $room->occupied_beds }}/{{ $room->capacity }}</span>
                                    </div>
                                </td>
                                <td class="num text-ink-soft">Rs {{ number_format((float) $room->monthly_rent) }}</td>
                                <td><x-status :value="$room->status" /></td>
                                <td>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" class="btn btn-secondary btn-sm"
                                                x-on:click="$dispatch('open-modal', 'room-edit-{{ $room->id }}')">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('rooms.destroy', $room) }}"
                                              onsubmit="return confirm('Delete room {{ $room->room_number }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-ghost btn-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 hover:text-rose-700 dark:hover:text-rose-300">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-10 text-center text-sm text-muted">No rooms match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @foreach ($rooms as $room)
            <x-modal :name="'room-edit-'.$room->id" title="Edit room {{ $room->room_number }}">
                <form method="POST" action="{{ route('rooms.update', $room) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="label">Hostel block</label>
                            <select name="hostel_id" class="select">
                                @foreach ($hostels as $hostel)
                                    <option value="{{ $hostel->id }}" @selected($room->hostel_id === $hostel->id)>{{ $hostel->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Room number</label>
                            <input name="room_number" value="{{ $room->room_number }}" class="input" required>
                        </div>
                        <div>
                            <label class="label">Type</label>
                            <select name="room_type" class="select">
                                @foreach (['standard', 'deluxe', 'single', 'shared'] as $type)
                                    <option value="{{ $type }}" @selected($room->room_type === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Capacity</label>
                            <input name="capacity" type="number" min="{{ max(1, $room->occupied_beds) }}" max="20" value="{{ $room->capacity }}" class="input" required>
                        </div>
                        <div>
                            <label class="label">Floor</label>
                            <input name="floor" type="number" min="0" value="{{ $room->floor }}" class="input">
                        </div>
                        <div>
                            <label class="label">Monthly rent (Rs)</label>
                            <input name="monthly_rent" type="number" min="0" step="100" value="{{ (int) $room->monthly_rent }}" class="input">
                        </div>
                        <div>
                            <label class="label">Status</label>
                            <select name="status" class="select">
                                @foreach (['available', 'full', 'maintenance'] as $status)
                                    <option value="{{ $status }}" @selected($room->status->value === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Room photo</label>
                            @if ($room->image_url)
                                <img src="{{ $room->image_url }}" alt="" class="mb-2 h-24 w-full max-w-[220px] rounded-lg object-cover ring-1 ring-line">
                            @endif
                            <input type="file" name="image" accept="image/*" class="input py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-paper file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-ink">
                            <p class="hint">JPG or PNG, up to 4 MB. Leave empty to keep the current photo.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                        <button class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </x-modal>
        @endforeach

        <x-modal name="room-create" title="Add a room" description="Create a new room inside an existing hostel block.">
            <form method="POST" action="{{ route('rooms.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Hostel block</label>
                        <select name="hostel_id" class="select" required>
                            @foreach ($hostels as $hostel)
                                <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Room number</label>
                        <input name="room_number" class="input" placeholder="205" required>
                    </div>
                    <div>
                        <label class="label">Type</label>
                        <select name="room_type" class="select">
                            <option value="standard">Standard</option>
                            <option value="deluxe">Deluxe</option>
                            <option value="single">Single</option>
                            <option value="shared">Shared</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Capacity</label>
                        <input name="capacity" type="number" min="1" max="20" value="2" class="input" required>
                    </div>
                    <div>
                        <label class="label">Floor</label>
                        <input name="floor" type="number" min="0" value="1" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Monthly rent (Rs)</label>
                        <input name="monthly_rent" type="number" min="0" step="100" value="6500" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Room photo</label>
                        <input type="file" name="image" accept="image/*" class="input py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-paper file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-ink">
                        <p class="hint">JPG or PNG, up to 4 MB.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button class="btn btn-primary">Create room</button>
                </div>
            </form>
        </x-modal>
    @else
        @if ($rooms->isEmpty())
            <div class="mt-6">
                <x-empty illustration="room" title="No rooms match your filters" message="Try a different block or room type.">
                    <a href="{{ route('rooms.index') }}" class="btn btn-secondary btn-sm">Clear filters</a>
                </x-empty>
            </div>
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 2xl:grid-cols-3">
                @foreach ($rooms as $room)
                    <article class="card card-hover rise group flex flex-col overflow-hidden">
                        <div class="relative" x-data="{ active: 0, images: @js($room->image_urls) }">
                            <a href="{{ route('rooms.show', $room) }}"
                               class="relative block aspect-[4/3] overflow-hidden bg-paper"
                               aria-label="View room {{ $room->room_number }}">
                                <template x-for="(image, index) in images" :key="index">
                                    <img :src="image" x-show="active === index"
                                         x-transition.opacity.duration.500ms
                                         alt="Room {{ $room->room_number }} photo"
                                         loading="lazy"
                                         class="absolute inset-0 h-full w-full object-cover">
                                </template>

                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-black/0"></div>

                                <div class="pointer-events-none absolute inset-x-5 bottom-4">
                                    <p class="text-[10px] font-medium uppercase tracking-[0.14em] text-white/80 [text-shadow:0_1px_2px_rgba(0,0,0,0.55)]">{{ $room->hostel?->name }}</p>
                                    <p class="num mt-0.5 text-2xl font-semibold tracking-tight text-white [text-shadow:0_1px_3px_rgba(0,0,0,0.45)]">Room {{ $room->room_number }}</p>
                                </div>
                            </a>

                            @if (count($room->image_urls) > 1)
                                <div class="absolute right-4 top-4 z-10 flex items-center gap-1.5">
                                    <template x-for="(image, index) in images" :key="'dot-' + index">
                                        <button type="button"
                                                x-on:click.prevent="active = index"
                                                class="h-1.5 rounded-full ring-1 ring-black/10 transition-all duration-300"
                                                :class="active === index ? 'w-4 bg-white' : 'w-1.5 bg-white/60 hover:bg-white'"
                                                :aria-label="'Photo ' + (index + 1)"></button>
                                    </template>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <div class="flex items-center justify-between gap-3">
                                <x-status :value="$room->status" />
                                <p class="num text-[11px] text-muted">{{ $room->available_beds }} of {{ $room->capacity }} free</p>
                            </div>

                            <dl class="mt-4 grid grid-cols-3 gap-2 text-sm">
                                <div>
                                    <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Type</dt>
                                    <dd class="mt-0.5 font-medium capitalize text-ink">{{ $room->room_type }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Floor</dt>
                                    <dd class="num mt-0.5 font-medium text-ink">{{ $room->floor }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Capacity</dt>
                                    <dd class="num mt-0.5 font-medium text-ink">{{ $room->capacity }} beds</dd>
                                </div>
                            </dl>

                            <div class="mt-4">
                                <div class="flex items-center justify-between text-[11px] text-muted">
                                    <span>Occupancy</span>
                                    <span class="num">{{ $room->occupied_beds }}/{{ $room->capacity }}</span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-paper">
                                    <div class="bar-grow h-full rounded-full bg-brand-600"
                                         style="width: {{ $room->capacity ? round($room->occupied_beds / $room->capacity * 100) : 0 }}%"></div>
                                </div>
                            </div>

                            <div class="mt-auto flex items-end justify-between gap-3 border-t border-line pt-4">
                                <div>
                                    <p class="text-[10px] font-medium uppercase tracking-wide text-muted">Monthly rent</p>
                                    <p class="num mt-0.5 text-sm font-semibold text-ink">Rs {{ number_format((float) $room->monthly_rent) }}</p>
                                </div>
                                @if ($activeBooking)
                                    <a href="{{ route('bookings.index') }}" class="btn btn-secondary btn-sm">View booking</a>
                                @elseif ($hasActiveRequest)
                                    <span class="text-[11px] text-muted">Request pending</span>
                                @else
                                    <form method="POST" action="{{ route('bookings.store') }}">
                                        @csrf
                                        <input type="hidden" name="room_id" value="{{ $room->id }}">
                                        <button class="btn btn-primary btn-sm" @disabled($room->available_beds < 1)>
                                            Request room
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">{{ $rooms->links() }}</div>
        @endif
    @endif
@endsection
