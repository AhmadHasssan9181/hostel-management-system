@extends('layouts.app')

@section('title', 'Facilities')

@section('content')
    @php
        $isStaff = auth()->user()->isStaff();
    @endphp

    <x-page-header
        :title="$isStaff ? 'Facilities' : 'Shared facilities'"
        :subtitle="$isStaff ? 'Publish opening hours and keep residents informed about closures.' : 'Check what is open, the timings, and whether a facility is under maintenance.'"
        eyebrow="Services">
        @if ($isStaff)
            <button type="button" class="btn btn-primary" x-on:click="$dispatch('open-modal', 'facility-create')">
                <x-icon name="plus" class="h-4 w-4" />
                Add facility
            </button>
        @endif
    </x-page-header>

    @if ($isStaff)
        <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <x-stat label="Facilities" :value="$stats['total']" hint="Published services" icon="storefront" tone="brand" :delay="1" />
            <x-stat label="Operational" :value="$stats['operational']" hint="Open as usual" icon="check" tone="emerald" :delay="2" />
            <x-stat label="Needs attention" :value="$stats['attention']" hint="Maintenance or closed" icon="wrench" tone="amber" :delay="3" />
            <x-stat label="Hidden" :value="$stats['hidden']" hint="Not visible to students" icon="bell" tone="zinc" :delay="4" />
        </div>

        <form method="GET" action="{{ route('facilities.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-48">
                <label class="label" for="type">Type</label>
                <select id="type" name="type" class="select">
                    <option value="">All types</option>
                    @foreach ($facilities->pluck('type')->unique() as $type)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-48">
                <label class="label" for="status">Status</label>
                <select id="status" name="status" class="select">
                    <option value="">Any status</option>
                    @foreach (['operational', 'maintenance', 'emergency', 'closed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
            @if (request()->hasAny(['type', 'status']))
                <a href="{{ route('facilities.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>
    @endif

    @if ($facilities->isEmpty())
        <div class="mt-6">
            <x-empty illustration="building" title="No facilities listed"
                     message="Add the mess, gym, laundry and other shared services so residents know what is available." />
        </div>
    @else
        <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($facilities as $facility)
                <article class="card card-hover rise group flex flex-col overflow-hidden">
                    <div class="h-36 overflow-hidden bg-paper">
                        <img src="{{ $facility->image_url ?? asset('images/facilities/common.jpg') }}"
                             alt="{{ $facility->name }}"
                             loading="lazy"
                             class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.04]">
                    </div>

                    <div class="flex flex-1 flex-col p-6">
                    <h2 class="text-base font-semibold tracking-tight text-ink">{{ $facility->name }}</h2>
                    <p class="mt-0.5 text-xs capitalize text-muted">{{ str_replace('_', ' ', $facility->type) }}</p>

                    @if ($facility->status !== \App\Enums\FacilityStatus::Operational)
                        @php
                            $statusNote = match ($facility->status) {
                                \App\Enums\FacilityStatus::Maintenance => 'Under maintenance',
                                \App\Enums\FacilityStatus::Emergency => 'Temporarily closed',
                                default => 'Closed',
                            };
                        @endphp
                        <p class="mt-1.5 text-xs font-medium {{ $facility->status === \App\Enums\FacilityStatus::Maintenance ? 'text-amber-600 dark:text-amber-300' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $statusNote }}
                        </p>
                    @endif

                    @if ($facility->description)
                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-ink-soft">{{ $facility->description }}</p>
                    @endif

                    <dl class="mt-4 space-y-2 border-t border-line pt-4 text-xs">
                        <div class="flex items-center gap-2 text-muted">
                            <x-icon name="clock" class="h-4 w-4 shrink-0" />
                            @if ($facility->opens_at)
                                Open {{ substr($facility->opens_at, 0, 5) }} – {{ substr($facility->closes_at, 0, 5) }}
                            @else
                                Open 24 hours
                            @endif
                        </div>
                        <div class="flex items-center gap-2 text-muted">
                            <x-icon name="pin" class="h-4 w-4 shrink-0" />
                            {{ $facility->location ?? 'Location not set' }}
                        </div>
                        @if ($facility->capacity)
                            <div class="flex items-center gap-2 text-muted">
                                <x-icon name="users" class="h-4 w-4 shrink-0" />
                                Capacity {{ $facility->capacity }}
                            </div>
                        @endif
                    </dl>

                    @if ($isStaff)
                        <div class="mt-auto flex items-center justify-end gap-1.5 border-t border-line pt-4">
                            <button type="button" class="btn btn-secondary btn-sm"
                                    x-on:click="$dispatch('open-modal', 'facility-edit-{{ $facility->id }}')">
                                Manage
                            </button>
                            @if (auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('facilities.destroy', $facility) }}"
                                      onsubmit="return confirm('Delete {{ $facility->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-ghost btn-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10">Delete</button>
                                </form>
                            @endif
                        </div>
                    @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if ($isStaff)
            @foreach ($facilities as $facility)
                <x-modal :name="'facility-edit-'.$facility->id" title="Manage {{ $facility->name }}" maxWidth="max-w-xl">
                    <form method="POST" action="{{ route('facilities.update', $facility) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="label">Name</label>
                                <input name="name" value="{{ $facility->name }}" class="input" required>
                            </div>
                            <div>
                                <label class="label">Type</label>
                                <input name="type" value="{{ $facility->type }}" class="input" required>
                            </div>
                            <div>
                                <label class="label">Capacity</label>
                                <input name="capacity" type="number" min="0" value="{{ $facility->capacity }}" class="input">
                            </div>
                            <div>
                                <label class="label">Opens at</label>
                                <input name="opens_at" type="time" value="{{ $facility->opens_at ? substr($facility->opens_at, 0, 5) : '' }}" class="input">
                            </div>
                            <div>
                                <label class="label">Closes at</label>
                                <input name="closes_at" type="time" value="{{ $facility->closes_at ? substr($facility->closes_at, 0, 5) : '' }}" class="input">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label">Location</label>
                                <input name="location" value="{{ $facility->location }}" class="input">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label">Description</label>
                                <textarea name="description" class="textarea" rows="3">{{ $facility->description }}</textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label">Photo</label>
                                @if ($facility->image_url)
                                    <img src="{{ $facility->image_url }}" alt="" class="mb-2 h-24 w-full max-w-[220px] rounded-lg object-cover ring-1 ring-line">
                                @endif
                                <input type="file" name="image" accept="image/*" class="input py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-paper file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-ink">
                                <p class="hint">JPG or PNG, up to 4 MB. Leave empty to keep the current photo.</p>
                            </div>
                            <div>
                                <label class="label">Status</label>
                                <select name="status" class="select">
                                    @foreach (['operational' => 'Operational', 'maintenance' => 'Maintenance', 'emergency' => 'Emergency (notifies all students)', 'closed' => 'Closed'] as $value => $label)
                                        <option value="{{ $value }}" @selected($facility->status->value === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <label class="flex items-center gap-2.5 self-end text-sm text-ink-soft">
                                <input type="hidden" name="is_visible" value="0">
                                <input type="checkbox" name="is_visible" value="1" @checked($facility->is_visible)
                                       class="h-4 w-4 rounded border-line-strong text-brand-700 focus:ring-brand-500">
                                Visible to students
                            </label>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                            <button class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </x-modal>
            @endforeach

            <x-modal name="facility-create" title="Add a facility" description="Publish a shared service and its opening hours.">
                <form method="POST" action="{{ route('facilities.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="label">Name</label>
                            <input name="name" class="input" placeholder="Study Hall" required>
                        </div>
                        <div>
                            <label class="label">Type</label>
                            <select name="type" class="select">
                                <option value="mess">Mess</option>
                                <option value="gym">Gym</option>
                                <option value="laundry">Laundry</option>
                                <option value="study">Study</option>
                                <option value="common">Common room</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Capacity</label>
                            <input name="capacity" type="number" min="0" class="input" placeholder="40">
                        </div>
                        <div>
                            <label class="label">Opens at</label>
                            <input name="opens_at" type="time" class="input">
                        </div>
                        <div>
                            <label class="label">Closes at</label>
                            <input name="closes_at" type="time" class="input">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Location</label>
                            <input name="location" class="input" placeholder="Block B, First Floor">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Description</label>
                            <textarea name="description" class="textarea" rows="3" placeholder="Quiet study space with Wi-Fi"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Photo</label>
                            <input type="file" name="image" accept="image/*" class="input py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-paper file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-ink">
                            <p class="hint">JPG or PNG, up to 4 MB.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button class="btn btn-primary">Create facility</button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endif
@endsection
