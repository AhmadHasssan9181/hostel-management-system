<?php

namespace App\Http\Controllers\Web;

use App\Enums\InventoryCondition;
use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = InventoryItem::query()->with('hostel:id,name');

        if ($request->filled('hostel_id')) {
            $query->where('hostel_id', $request->integer('hostel_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->string('condition'));
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('quantity', '<=', 'threshold');
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        return view('inventory.index', [
            'items' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'hostels' => Hostel::query()->orderBy('name')->get(['id', 'name']),
            'categories' => InventoryItem::query()->distinct()->orderBy('category')->pluck('category'),
            'stats' => [
                'items' => InventoryItem::count(),
                'units' => (int) InventoryItem::sum('quantity'),
                'low' => InventoryItem::whereColumn('quantity', '<=', 'threshold')->count(),
                'broken' => InventoryItem::whereIn('condition', [
                    InventoryCondition::Broken->value,
                    InventoryCondition::Damaged->value,
                ])->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hostel_id' => ['nullable', 'exists:hostels,id'],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['sometimes', 'string', 'max:40'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['sometimes', 'string', 'max:20'],
            'threshold' => ['sometimes', 'integer', 'min:0'],
            'condition' => ['sometimes', Rule::enum(InventoryCondition::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        InventoryItem::create($data);

        return back()->with('success', 'Inventory item added.');
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $data = $request->validate([
            'hostel_id' => ['sometimes', 'nullable', 'exists:hostels,id'],
            'name' => ['sometimes', 'string', 'max:120'],
            'category' => ['sometimes', 'string', 'max:40'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'unit' => ['sometimes', 'string', 'max:20'],
            'threshold' => ['sometimes', 'integer', 'min:0'],
            'condition' => ['sometimes', Rule::enum(InventoryCondition::class)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $item->update($data);

        return back()->with('success', "{$item->name} updated.");
    }

    public function adjust(Request $request, InventoryItem $item): RedirectResponse
    {
        $data = $request->validate([
            'delta' => ['required', 'integer', 'not_in:0'],
            'condition' => ['sometimes', Rule::enum(InventoryCondition::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $newQuantity = $item->quantity + $data['delta'];

        if ($newQuantity < 0) {
            return back()->with('error', 'Stock cannot go below zero.');
        }

        $item->update([
            'quantity' => $newQuantity,
            'condition' => $data['condition'] ?? $item->condition,
            'notes' => $data['notes'] ?? $item->notes,
        ]);

        return back()->with('success', "{$item->name} stock updated to {$newQuantity} {$item->unit}.");
    }

    public function destroy(InventoryItem $item): RedirectResponse
    {
        $item->delete();

        return back()->with('success', 'Inventory item deleted.');
    }
}
