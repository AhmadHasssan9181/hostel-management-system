@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
    <x-page-header
        title="Inventory"
        subtitle="Track furniture, appliances and mess supplies across every block."
        eyebrow="Services">
        <button type="button" class="btn btn-primary" x-on:click="$dispatch('open-modal', 'inventory-create')">
            <x-icon name="plus" class="h-4 w-4" />
            Add item
        </button>
    </x-page-header>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Item types" :value="$stats['items']" hint="Tracked lines" icon="box" tone="brand" :delay="1" />
        <x-stat label="Total units" :value="number_format($stats['units'])" hint="Across all blocks" icon="chart" tone="sky" :delay="2" />
        <x-stat label="Low stock" :value="$stats['low']" hint="At or below threshold" icon="alert" tone="amber" :delay="3" />
        <x-stat label="Damaged / broken" :value="$stats['broken']" hint="Needs replacement" icon="wrench" tone="rose" :delay="4" />
    </div>

    <form method="GET" action="{{ route('inventory.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <div class="min-w-[180px] flex-1">
            <label class="label" for="search">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" class="input" placeholder="Item name">
        </div>
        <div class="w-full sm:w-44">
            <label class="label" for="hostel_id">Block</label>
            <select id="hostel_id" name="hostel_id" class="select">
                <option value="">All blocks</option>
                @foreach ($hostels as $hostel)
                    <option value="{{ $hostel->id }}" @selected(request('hostel_id') == $hostel->id)>{{ $hostel->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-40">
            <label class="label" for="category">Category</label>
            <select id="category" name="category" class="select">
                <option value="">All</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ ucfirst($category) }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-40">
            <label class="label" for="condition">Condition</label>
            <select id="condition" name="condition" class="select">
                <option value="">Any</option>
                @foreach (['good', 'damaged', 'broken', 'lost'] as $condition)
                    <option value="{{ $condition }}" @selected(request('condition') === $condition)>{{ ucfirst($condition) }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2.5 text-sm text-ink-soft">
            <input type="checkbox" name="low_stock" value="1" @checked(request()->boolean('low_stock'))
                   class="h-4 w-4 rounded border-line-strong text-brand-700 focus:ring-brand-500">
            Low stock only
        </label>
        <button class="btn btn-secondary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
        @if (request()->hasAny(['search', 'hostel_id', 'category', 'condition', 'low_stock']))
            <a href="{{ route('inventory.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>

    <div class="table-shell mt-5 rise rise-2">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Block</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Condition</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php($low = $item->isLowStock())
                        <tr>
                            <td>
                                <p class="text-sm font-semibold text-ink">{{ $item->name }}</p>
                                @if ($item->notes)
                                    <p class="max-w-[220px] truncate text-[11px] text-muted">{{ $item->notes }}</p>
                                @endif
                            </td>
                            <td class="text-xs text-ink-soft">{{ $item->hostel?->name ?? 'All blocks' }}</td>
                            <td class="capitalize text-xs text-ink-soft">{{ $item->category }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold {{ $low ? 'text-amber-700 dark:text-amber-300' : 'text-ink' }}">{{ $item->quantity }}</span>
                                    <span class="text-[11px] text-muted">/ {{ $item->threshold }} min</span>
                                </div>
                                <div class="mt-1.5 h-1.5 w-24 overflow-hidden rounded-full bg-paper">
                                    <div class="h-full rounded-full {{ $low ? 'bg-amber-400' : 'bg-brand-500' }}"
                                         style="width: {{ $item->threshold > 0 ? min(100, round($item->quantity / max(1, $item->threshold) * 50)) : 100 }}%"></div>
                                </div>
                            </td>
                            <td><x-status :value="$item->condition" /></td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" class="btn btn-primary btn-sm"
                                            x-on:click="$dispatch('open-modal', 'inventory-adjust-{{ $item->id }}')">
                                        Adjust
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm"
                                            x-on:click="$dispatch('open-modal', 'inventory-edit-{{ $item->id }}')">
                                        Edit
                                    </button>
                                    @if (auth()->user()->isAdmin())
                                        <form method="POST" action="{{ route('inventory.destroy', $item) }}"
                                              onsubmit="return confirm('Delete {{ $item->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-ghost btn-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-sm text-muted">No inventory items match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $items->links() }}</div>

    @foreach ($items as $item)
        <x-modal :name="'inventory-adjust-'.$item->id" title="Adjust stock" :description="$item->name.' · currently '.$item->quantity.' '.$item->unit">
            <form method="POST" action="{{ route('inventory.adjust', $item) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="label">Change by</label>
                    <input name="delta" type="number" step="1" value="1" class="input" required>
                    <p class="hint">Use a negative number when items are removed or damaged.</p>
                </div>
                <div>
                    <label class="label">Condition</label>
                    <select name="condition" class="select">
                        @foreach (['good', 'damaged', 'broken', 'lost'] as $condition)
                            <option value="{{ $condition }}" @selected($item->condition->value === $condition)>{{ ucfirst($condition) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Note <span class="font-normal text-muted">(optional)</span></label>
                    <textarea name="notes" class="textarea" rows="2" placeholder="Received 10 new chairs from stores"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                    <button class="btn btn-primary">Update stock</button>
                </div>
            </form>
        </x-modal>

        <x-modal :name="'inventory-edit-'.$item->id" title="Edit {{ $item->name }}">
            <form method="POST" action="{{ route('inventory.update', $item) }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Name</label>
                        <input name="name" value="{{ $item->name }}" class="input" required>
                    </div>
                    <div>
                        <label class="label">Block</label>
                        <select name="hostel_id" class="select">
                            <option value="">All blocks</option>
                            @foreach ($hostels as $hostel)
                                <option value="{{ $hostel->id }}" @selected($item->hostel_id === $hostel->id)>{{ $hostel->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Category</label>
                        <input name="category" value="{{ $item->category }}" class="input">
                    </div>
                    <div>
                        <label class="label">Quantity</label>
                        <input name="quantity" type="number" min="0" value="{{ $item->quantity }}" class="input">
                    </div>
                    <div>
                        <label class="label">Unit</label>
                        <input name="unit" value="{{ $item->unit }}" class="input">
                    </div>
                    <div>
                        <label class="label">Low-stock threshold</label>
                        <input name="threshold" type="number" min="0" value="{{ $item->threshold }}" class="input">
                    </div>
                    <div>
                        <label class="label">Condition</label>
                        <select name="condition" class="select">
                            @foreach (['good', 'damaged', 'broken', 'lost'] as $condition)
                                <option value="{{ $condition }}" @selected($item->condition->value === $condition)>{{ ucfirst($condition) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Notes</label>
                        <textarea name="notes" class="textarea" rows="2">{{ $item->notes }}</textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                    <button class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </x-modal>
    @endforeach

    <x-modal name="inventory-create" title="Add an inventory item" description="Track a new asset or supply line.">
        <form method="POST" action="{{ route('inventory.store') }}" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label">Name</label>
                    <input name="name" class="input" placeholder="Steel beds" required>
                </div>
                <div>
                    <label class="label">Block</label>
                    <select name="hostel_id" class="select">
                        <option value="">All blocks</option>
                        @foreach ($hostels as $hostel)
                            <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Category</label>
                    <input name="category" value="furniture" class="input">
                </div>
                <div>
                    <label class="label">Quantity</label>
                    <input name="quantity" type="number" min="0" value="0" class="input" required>
                </div>
                <div>
                    <label class="label">Unit</label>
                    <input name="unit" value="pcs" class="input">
                </div>
                <div>
                    <label class="label">Low-stock threshold</label>
                    <input name="threshold" type="number" min="0" value="5" class="input">
                </div>
                <div>
                    <label class="label">Condition</label>
                    <select name="condition" class="select">
                        <option value="good">Good</option>
                        <option value="damaged">Damaged</option>
                        <option value="broken">Broken</option>
                        <option value="lost">Lost</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Notes</label>
                    <textarea name="notes" class="textarea" rows="2" placeholder="Optional notes"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button class="btn btn-primary">Add item</button>
            </div>
        </form>
    </x-modal>
@endsection
