<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Hostel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HostelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Hostel::query()->withCount(['rooms', 'inventoryItems']);

        if ($request->boolean('active_only', false)) {
            $query->where('is_active', true);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:hostels,name'],
            'type' => ['required', Rule::in(['boys', 'girls'])],
            'location' => ['nullable', 'string', 'max:190'],
            'total_rooms' => ['sometimes', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $hostel = Hostel::create($data);

        return response()->json(['message' => 'Hostel created.', 'hostel' => $hostel], 201);
    }

    public function show(Hostel $hostel): JsonResponse
    {
        return response()->json([
            'hostel' => $hostel->load('rooms')->loadCount(['rooms', 'inventoryItems']),
        ]);
    }

    public function update(Request $request, Hostel $hostel): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120', Rule::unique('hostels', 'name')->ignore($hostel->id)],
            'type' => ['sometimes', Rule::in(['boys', 'girls'])],
            'location' => ['sometimes', 'nullable', 'string', 'max:190'],
            'total_rooms' => ['sometimes', 'integer', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $hostel->update($data);

        return response()->json(['message' => 'Hostel updated.', 'hostel' => $hostel->fresh()]);
    }

    public function destroy(Hostel $hostel): JsonResponse
    {
        if ($hostel->rooms()->where('occupied_beds', '>', 0)->exists()) {
            return response()->json([
                'message' => 'Cannot delete a hostel that still has residents.',
            ], 422);
        }

        $hostel->delete();

        return response()->json(['message' => 'Hostel deleted.']);
    }
}
