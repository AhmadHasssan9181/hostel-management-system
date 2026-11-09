<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InventoryCondition;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::query()->with('hostel');

        if ($request->filled('hostel_id')) {
            $query->where('hostel_id', $request->integer('hostel_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->string('condition'));
        }

        if ($request->boolean('low_stock', false)) {
            $query->whereColumn('quantity', '<=', 'threshold');
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        return response()->json($query->orderBy('name')->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hostel_id' => ['nullable', 'exists:hostels,id'],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['sometimes', 'string', 'max:40'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['sometimes', 'string', 'max:20'],
            'threshold' => ['sometimes', 'integer', 'min:0'],
            'condition' => ['sometimes', Rule::enum(InventoryCondition::class)],
            'notes' => ['nullable', 'string'],
        ]);

        $item = InventoryItem::create($data);

        return response()->json(['message' => 'Inventory item created.', 'item' => $item->load('hostel')], 201);
    }

    public function update(Request $request, InventoryItem $item): JsonResponse
    {
        $data = $request->validate([
            'hostel_id' => ['sometimes', 'nullable', 'exists:hostels,id'],
            'name' => ['sometimes', 'string', 'max:120'],
            'category' => ['sometimes', 'string', 'max:40'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'unit' => ['sometimes', 'string', 'max:20'],
            'threshold' => ['sometimes', 'integer', 'min:0'],
            'condition' => ['sometimes', Rule::enum(InventoryCondition::class)],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $item->update($data);

        return response()->json(['message' => 'Inventory item updated.', 'item' => $item->fresh('hostel')]);
    }

    public function adjustStock(Request $request, InventoryItem $item): JsonResponse
    {
        $data = $request->validate([
            'delta' => ['required', 'integer', 'not_in:0'],
            'condition' => ['sometimes', Rule::enum(InventoryCondition::class)],
            'notes' => ['nullable', 'string'],
        ]);

        $newQuantity = $item->quantity + $data['delta'];

        if ($newQuantity < 0) {
            return response()->json(['message' => 'Stock cannot go below zero.'], 422);
        }

        $item->update([
            'quantity' => $newQuantity,
            'condition' => $data['condition'] ?? $item->condition,
            'notes' => $data['notes'] ?? $item->notes,
        ]);

        return response()->json([
            'message' => 'Stock updated.',
            'item' => $item->fresh('hostel'),
            'low_stock' => $item->isLowStock(),
        ]);
    }

    public function destroy(Request $request, InventoryItem $item): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Only administrators can delete inventory items.');

        $item->delete();

        return response()->json(['message' => 'Inventory item deleted.']);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 20), 1), 100);
    }
}
