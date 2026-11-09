@extends('layouts.app')

@section('title', 'Hostel blocks')

@section('content')
    <x-page-header
        title="Hostel blocks"
        subtitle="Keep the building structure, capacity and locations up to date."
        eyebrow="Residence">
        <button type="button" class="btn btn-primary" x-on:click="$dispatch('open-modal', 'hostel-create')">
            <x-icon name="plus" class="h-4 w-4" />
            Add block
        </button>
        <a href="{{ route('rooms.index') }}" class="btn btn-secondary">
            <x-icon name="bed" class="h-4 w-4" />
            Rooms
        </a>
    </x-page-header>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Blocks" :value="$stats['hostels']" hint="Boys and girls hostels" icon="building" :delay="1" />
        <x-stat label="Rooms" :value="$stats['rooms']" hint="Across all blocks" icon="bed" :delay="2" />
        <x-stat label="Beds" :value="$stats['beds']" hint="Total capacity" icon="users" :delay="3" />
        <x-stat label="Occupied" :value="$stats['occupied']" hint="Residents checked in" icon="home" :delay="4" />
    </div>

    @if ($hostels->isEmpty())
        <div class="mt-6">
            <x-empty illustration="building" title="No hostel blocks yet" message="Create the first block to start adding rooms.">
                <button type="button" class="btn btn-primary btn-sm" x-on:click="$dispatch('open-modal', 'hostel-create')">Add block</button>
            </x-empty>
        </div>
    @else
        <div class="mt-6 grid gap-5 lg:grid-cols-2">
            @foreach ($hostels as $hostel)
                @php
                    $capacity = (int) ($hostel->capacity_sum ?? 0);
                    $occupied = (int) ($hostel->occupied_sum ?? 0);
                    $rate = $capacity > 0 ? round($occupied / $capacity * 100) : 0;
                @endphp
                <article class="card card-hover rise group overflow-hidden">
                    @php($gallery = $hostel->image_urls)
                    @php($hasGallery = count($gallery) > 1)
                    <div class="grid h-52 gap-px overflow-hidden bg-line {{ $hasGallery ? 'grid-cols-3' : 'grid-cols-1' }}">
                        <div class="relative min-h-0 overflow-hidden bg-paper {{ $hasGallery ? 'col-span-2' : 'col-span-1' }}">
                            <img src="{{ $gallery[0] ?? asset('images/hostels/iqbal.jpg') }}"
                                 alt="{{ $hostel->name }}"
                                 loading="lazy"
                                 class="absolute inset-0 h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.03]">
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-black/15 to-black/0"></div>

                            @unless ($hostel->is_active)
                                <div class="absolute right-4 top-4">
                                    <x-status value="suspended" label="Inactive" :on-image="true" />
                                </div>
                            @endunless

                            <div class="absolute inset-x-5 bottom-4">
                                <p class="text-[10px] font-medium uppercase tracking-[0.14em] text-white/80 [text-shadow:0_1px_2px_rgba(0,0,0,0.55)]">
                                    {{ ucfirst($hostel->type) }} block
                                    @if ($hostel->location)
                                        · {{ $hostel->location }}
                                    @endif
                                </p>
                                <h2 class="mt-0.5 text-xl font-semibold tracking-tight text-white [text-shadow:0_1px_3px_rgba(0,0,0,0.45)]">{{ $hostel->name }}</h2>
                            </div>
                        </div>

                        @if ($hasGallery)
                        <div class="grid min-h-0 grid-rows-2 gap-px">
                            <div class="relative min-h-0 overflow-hidden bg-paper">
                                <img src="{{ $gallery[1] ?? $gallery[0] ?? asset('images/rooms/standard.jpg') }}"
                                     alt="{{ $hostel->name }} room"
                                     loading="lazy"
                                     class="absolute inset-0 h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.05]">
                            </div>
                            <div class="relative min-h-0 overflow-hidden bg-paper">
                                <img src="{{ $gallery[2] ?? $gallery[1] ?? asset('images/facilities/common.jpg') }}"
                                     alt="{{ $hostel->name }} facility"
                                     loading="lazy"
                                     class="absolute inset-0 h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.05]">
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="p-6">
                        @if ($hostel->description)
                            <p class="text-sm leading-6 text-ink-soft">{{ $hostel->description }}</p>
                        @endif

                        <div class="mt-5">
                            <div class="flex items-center justify-between text-xs text-muted">
                                <span>Occupancy</span>
                                <span class="num font-semibold text-ink">{{ $rate }}%</span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-paper">
                                <div class="bar-grow h-full rounded-full {{ $rate > 90 ? 'bg-rose-500' : 'bg-brand-600' }}" style="width: {{ $rate }}%"></div>
                            </div>
                        </div>

                        <dl class="mt-5 grid grid-cols-4 gap-2 border-t border-line pt-4 text-center">
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Rooms</dt>
                                <dd class="num mt-1 text-sm font-semibold text-ink">{{ $hostel->rooms_count }}</dd>
                            </div>
                            <div class="border-x border-line">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Beds</dt>
                                <dd class="num mt-1 text-sm font-semibold text-ink">{{ $capacity }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Occupied</dt>
                                <dd class="num mt-1 text-sm font-semibold text-ink">{{ $occupied }}</dd>
                            </div>
                            <div class="border-l border-line">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-muted">Free</dt>
                                <dd class="num mt-1 text-sm font-semibold text-ink">{{ max(0, $capacity - $occupied) }}</dd>
                            </div>
                        </dl>

                        <div class="mt-5 flex items-center justify-end gap-1.5">
                            <button type="button" class="btn btn-secondary btn-sm"
                                    x-on:click="$dispatch('open-modal', 'hostel-edit-{{ $hostel->id }}')">
                                Edit block
                            </button>
                            <form method="POST" action="{{ route('hostels.destroy', $hostel) }}"
                                  onsubmit="return confirm('Delete {{ $hostel->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost btn-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 hover:text-rose-700 dark:hover:text-rose-300">Delete</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @foreach ($hostels as $hostel)
            <x-modal :name="'hostel-edit-'.$hostel->id" title="Edit {{ $hostel->name }}">
                <form method="POST" action="{{ route('hostels.update', $hostel) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="label">Block name</label>
                            <input name="name" value="{{ $hostel->name }}" class="input" required>
                        </div>
                        <div>
                            <label class="label">Type</label>
                            <select name="type" class="select">
                                <option value="boys" @selected($hostel->type === 'boys')>Boys</option>
                                <option value="girls" @selected($hostel->type === 'girls')>Girls</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Total rooms</label>
                            <input name="total_rooms" type="number" min="0" value="{{ $hostel->total_rooms }}" class="input">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Location</label>
                            <input name="location" value="{{ $hostel->location }}" class="input">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Description</label>
                            <textarea name="description" class="textarea" rows="3">{{ $hostel->description }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Cover photo</label>
                            @if ($hostel->image_url)
                                <img src="{{ $hostel->image_url }}" alt="" class="mb-2 h-24 w-full max-w-[220px] rounded-lg object-cover ring-1 ring-line">
                            @endif
                            <input type="file" name="image" accept="image/*" class="input py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-paper file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-ink">
                            <p class="hint">JPG or PNG, up to 4 MB. Leave empty to keep the current photo.</p>
                        </div>
                        <label class="flex items-center gap-2.5 text-sm text-ink-soft sm:col-span-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($hostel->is_active)
                                   class="h-4 w-4 rounded border-line-strong text-brand-700 focus:ring-brand-500">
                            Block is active and accepting residents
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                        <button class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </x-modal>
        @endforeach
    @endif

    <x-modal name="hostel-create" title="Add a hostel block" description="Create a new building before adding rooms to it.">
        <form method="POST" action="{{ route('hostels.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label">Block name</label>
                    <input name="name" class="input" placeholder="Iqbal Hostel (Boys)" required>
                </div>
                <div>
                    <label class="label">Type</label>
                    <select name="type" class="select">
                        <option value="boys">Boys</option>
                        <option value="girls">Girls</option>
                    </select>
                </div>
                <div>
                    <label class="label">Total rooms</label>
                    <input name="total_rooms" type="number" min="0" value="0" class="input">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Location</label>
                    <input name="location" class="input" placeholder="FUUAST Islamabad, Block A">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Description</label>
                    <textarea name="description" class="textarea" rows="3" placeholder="Short note about this block"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Cover photo</label>
                    <input type="file" name="image" accept="image/*" class="input py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-paper file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-ink">
                    <p class="hint">JPG or PNG, up to 4 MB.</p>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button class="btn btn-primary">Create block</button>
            </div>
        </form>
    </x-modal>
@endsection
