<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Room::query()->with('hostel');

        if ($request->filled('hostel_id')) {
            $query->where('hostel_id', $request->integer('hostel_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('room_type')) {
            $query->where('room_type', $request->string('room_type'));
        }

        if ($request->boolean('available_only', false)) {
            $query->withAvailability();
        }

        return response()->json($query->orderBy('hostel_id')->orderBy('room_number')->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hostel_id' => ['required', 'exists:hostels,id'],
            'room_number' => [
                'required', 'string', 'max:20',
                Rule::unique('rooms')->where(fn ($q) => $q->where('hostel_id', $request->integer('hostel_id'))),
            ],
            'room_type' => ['sometimes', 'string', 'max:30'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'floor' => ['sometimes', 'integer', 'min:0'],
            'monthly_rent' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::enum(RoomStatus::class)],
            'notes' => ['nullable', 'string'],
        ]);

        $data['occupied_beds'] = 0;
        $data['status'] = $data['status'] ?? RoomStatus::Available;

        $room = Room::create($data);

        return response()->json(['message' => 'Room created.', 'room' => $room->load('hostel')], 201);
    }

    public function show(Room $room): JsonResponse
    {
        return response()->json([
            'room' => $room->load(['hostel', 'occupants.student.studentProfile']),
        ]);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $data = $request->validate([
            'hostel_id' => ['sometimes', 'exists:hostels,id'],
            'room_number' => [
                'sometimes', 'string', 'max:20',
                Rule::unique('rooms')
                    ->where(fn ($q) => $q->where('hostel_id', $request->integer('hostel_id', $room->hostel_id)))
                    ->ignore($room->id),
            ],
            'room_type' => ['sometimes', 'string', 'max:30'],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'floor' => ['sometimes', 'integer', 'min:0'],
            'monthly_rent' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::enum(RoomStatus::class)],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if (isset($data['capacity']) && $data['capacity'] < $room->occupied_beds) {
            return response()->json([
                'message' => 'Capacity cannot be lower than the number of occupied beds.',
            ], 422);
        }

        $room->update($data);
        $room->refresh()->syncStatus();

        return response()->json(['message' => 'Room updated.', 'room' => $room->fresh('hostel')]);
    }

    public function destroy(Room $room): JsonResponse
    {
        if ($room->occupied_beds > 0 || $room->bookings()->where('status', 'approved')->exists()) {
            return response()->json(['message' => 'Cannot delete a room that is occupied.'], 422);
        }

        $room->delete();

        return response()->json(['message' => 'Room deleted.']);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 20), 1), 100);
    }
}
