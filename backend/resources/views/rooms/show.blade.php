@extends('layouts.app')

@section('title', 'Room '.$room->room_number)

@section('content')
    @php
        $gallery = $room->image_urls;
        $currency = fn ($value) => 'Rs '.number_format((float) $value);
    @endphp

    <div class="mb-5 flex flex-wrap items-center gap-1.5 text-xs text-muted">
        <a href="{{ route('rooms.index') }}" class="font-medium text-ink-soft hover:text-ink">Rooms</a>
        <x-icon name="chevron-right" class="h-3.5 w-3.5" />
        <span>{{ $room->hostel?->name }}</span>
        <x-icon name="chevron-right" class="h-3.5 w-3.5" />
        <span>Room {{ $room->room_number }}</span>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1.55fr_1fr]">
        <div>
            <div x-data="{ active: 0, images: @js($gallery) }">
                <div class="relative aspect-[16/10] overflow-hidden rounded-2xl bg-paper ring-1 ring-line">
                    <template x-for="(image, index) in images" :key="index">
                        <img :src="image" x-show="active === index"
                             x-transition.opacity.duration.500ms
                             alt="Room {{ $room->room_number }}"
                             class="absolute inset-0 h-full w-full object-cover">
                    </template>

                    @if (count($gallery) > 1)
                        <div class="absolute bottom-4 left-5 z-10 flex items-center gap-2">
                            <template x-for="(image, index) in images" :key="'dot-' + index">
                                <button type="button"
                                        x-on:click.prevent="active = index"
                                        class="h-2 rounded-full ring-1 ring-black/30 transition-all duration-300"
                                        :class="active === index ? 'w-6 bg-white' : 'w-2 bg-white/60 hover:bg-white'"
                                        :aria-label="'Photo ' + (index + 1)"></button>
                            </template>
                        </div>
                    @endif
                </div>

                @if (count($gallery) > 1)
                    <div class="mt-3 grid gap-3" style="grid-template-columns: repeat({{ count($gallery) }}, minmax(0, 1fr));">
                        <template x-for="(image, index) in images" :key="'thumb-' + index">
                            <button type="button" x-on:click.prevent="active = index"
                                    class="relative aspect-[4/3] overflow-hidden rounded-xl ring-1 transition duration-200"
                                    :class="active === index ? 'ring-2 ring-ink opacity-100' : 'ring-line opacity-75 hover:opacity-100'">
                                <img :src="image" alt="" class="h-full w-full object-cover">
                            </button>
                        </template>
                    </div>
                @endif
            </div>

            <section class="mt-8">
                <h2 class="text-lg font-semibold tracking-tight text-ink">About this room</h2>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-ink-soft">
                    A {{ $room->room_type }} room on floor {{ $room->floor }} of {{ $room->hostel?->name }},
                    with space for {{ $room->capacity }} {{ $room->capacity === 1 ? 'resident' : 'residents' }}.
                    {{ $room->notes ?: 'Located close to the mess, laundry and study areas, and managed directly by the hostel office.' }}
                </p>
            </section>

            <section class="mt-7">
                <h2 class="text-lg font-semibold tracking-tight text-ink">What this room includes</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        ['bed', 'Bed and mattress'],
                        ['clipboard', 'Study desk and chair'],
                        ['box', 'Wardrobe storage'],
                        ['bell', 'Wi-Fi access'],
                        ['sparkles', 'Housekeeping'],
                        ['lock', '24/7 security'],
                    ] as [$icon, $label])
                        <div class="flex items-center gap-3 rounded-xl border border-line bg-surface px-4 py-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-paper text-ink">
                                <x-icon :name="$icon" class="h-4 w-4" />
                            </span>
                            <span class="text-sm text-ink-soft">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="card p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-muted">{{ $room->hostel?->name }}</p>
                        <h1 class="num mt-1 text-3xl font-semibold tracking-tight text-ink">Room {{ $room->room_number }}</h1>
                        <p class="mt-1 text-sm capitalize text-muted">{{ $room->room_type }} · Floor {{ $room->floor }}</p>
                    </div>
                    <x-status :value="$room->status" />
                </div>

                <div class="mt-5 border-t border-line pt-5">
                    <p class="text-xs text-muted">Monthly rent</p>
                    <p class="num mt-1 text-3xl font-semibold tracking-tight text-ink">{{ $currency($room->monthly_rent) }}</p>
                    <p class="mt-0.5 text-xs text-muted">per bed · billed monthly</p>
                </div>

                <dl class="mt-5 grid grid-cols-3 gap-2 border-y border-line py-4 text-center">
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Beds</dt>
                        <dd class="num mt-1 text-sm font-semibold text-ink">{{ $room->capacity }}</dd>
                    </div>
                    <div class="border-x border-line">
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Occupied</dt>
                        <dd class="num mt-1 text-sm font-semibold text-ink">{{ $room->occupied_beds }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Free</dt>
                        <dd class="num mt-1 text-sm font-semibold text-ink">{{ $room->available_beds }}</dd>
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

                <div class="mt-5">
                    @if ($isStaff)
                        <button type="button" class="btn btn-primary w-full"
                                x-on:click="$dispatch('open-modal', 'room-detail-edit')">
                            Edit this room
                        </button>
                        <a href="{{ route('rooms.index') }}" class="mt-2 block text-center text-xs font-medium text-muted hover:text-ink">
                            Back to room manager
                        </a>
                    @elseif ($activeBooking)
                        <a href="{{ route('bookings.index') }}" class="btn btn-secondary w-full">View my booking</a>
                    @elseif ($hasActiveRequest)
                        <p class="rounded-xl border border-line bg-canvas px-4 py-3 text-center text-xs text-muted">
                            You already have a pending room request.
                        </p>
                    @else
                        <form method="POST" action="{{ route('bookings.store') }}">
                            @csrf
                            <input type="hidden" name="room_id" value="{{ $room->id }}">
                            <button class="btn btn-primary w-full" @disabled($room->available_beds < 1 || $room->status->value === 'maintenance')>
                                Request this room
                            </button>
                        </form>
                    @endif

                    <p class="mt-3 flex items-start gap-2 text-[11px] leading-5 text-muted">
                        <x-icon name="lock" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                        Requests are reviewed by the hostel office. You will be notified once your allocation is confirmed.
                    </p>
                </div>

                <div class="mt-5 space-y-2 border-t border-line pt-4 text-xs text-muted">
                    <p class="flex items-center gap-2">
                        <x-icon name="pin" class="h-3.5 w-3.5" />
                        {{ $room->hostel?->location ?? 'Campus accommodation' }}
                    </p>
                    <p class="flex items-center gap-2">
                        <x-icon name="clock" class="h-3.5 w-3.5" />
                        Check-in from 09:00 · Office hours 09:00–17:00
                    </p>
                </div>
            </div>
        </aside>
    </div>

    @if ($similarRooms->isNotEmpty())
        <section class="mt-12">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-ink">More rooms in this block</h2>
                    <p class="mt-0.5 text-sm text-muted">Compare other available rooms in {{ $room->hostel?->name }}.</p>
                </div>
                <a href="{{ route('rooms.index') }}" class="text-xs font-medium text-ink-soft hover:text-ink">View all rooms</a>
            </div>

            <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($similarRooms as $similar)
                    <a href="{{ route('rooms.show', $similar) }}" class="card card-hover group overflow-hidden">
                        <div class="relative aspect-[4/3] overflow-hidden bg-paper">
                            <img src="{{ $similar->image_url ?? asset('images/rooms/standard.jpg') }}"
                                 alt="Room {{ $similar->room_number }}"
                                 loading="lazy"
                                 class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.04]">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/45 to-transparent"></div>
                            <p class="num absolute bottom-3 left-4 text-xl font-semibold text-white [text-shadow:0_1px_3px_rgba(0,0,0,0.45)]">Room {{ $similar->room_number }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-3 p-4">
                            <div>
                                <p class="text-sm font-medium capitalize text-ink">{{ $similar->room_type }}</p>
                                <p class="text-xs text-muted">{{ $similar->available_beds }} of {{ $similar->capacity }} free</p>
                            </div>
                            <p class="num text-sm font-semibold text-ink">{{ $currency($similar->monthly_rent) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
    @if ($isStaff)
        <x-modal name="room-detail-edit" title="Edit room {{ $room->room_number }}" maxWidth="max-w-xl">
            <form method="POST" action="{{ route('rooms.update', $room) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="hostel_id" value="{{ $room->hostel_id }}">
                <div class="grid gap-4 sm:grid-cols-2">
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
                        <label class="label">Notes</label>
                        <textarea name="notes" class="textarea" rows="2">{{ $room->notes }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Room photo</label>
                        @if ($room->image_url)
                            <img src="{{ $room->image_url }}" alt="" class="mb-2 h-24 w-40 rounded-lg object-cover ring-1 ring-line">
                        @endif
                        <input type="file" name="image" accept="image/*" class="input py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-paper file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-ink">
                        <p class="hint">JPG or PNG, up to 4 MB. Leave empty to keep the current photo.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                    <button class="btn btn-primary">Save room</button>
                </div>
            </form>
        </x-modal>
    @endif
@endsection
