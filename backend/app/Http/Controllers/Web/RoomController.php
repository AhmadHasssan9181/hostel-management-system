<?php

namespace App\Http\Controllers\Web;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Room::query()->with('hostel');

        if ($request->filled('hostel_id')) {
            $query->where('hostel_id', $request->integer('hostel_id'));
        }

        if ($request->filled('room_type')) {
            $query->where('room_type', $request->string('room_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('room_number', 'like', '%'.$request->string('search').'%')
                ->orWhereHas('hostel', fn ($h) => $h->where('name', 'like', '%'.$request->string('search').'%')));
        }

        if ($user->isStudent()) {
            $rooms = $query->withAvailability()
                ->orderBy('hostel_id')
                ->orderBy('room_number')
                ->paginate(12)
                ->withQueryString();

            return view('rooms.index', [
                'rooms' => $rooms,
                'hostels' => Hostel::query()->orderBy('name')->get(),
                'activeBooking' => $user->activeBooking()->with('room.hostel')->first(),
                'hasActiveRequest' => $user->bookings()
                    ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Approved->value])
                    ->exists(),
                'stats' => [
                    'available' => Room::query()->withAvailability()->count(),
                    'beds' => (int) Room::sum('capacity'),
                    'free' => (int) Room::sum('capacity') - (int) Room::sum('occupied_beds'),
                    'hostels' => Hostel::query()->where('is_active', true)->count(),
                ],
            ]);
        }

        $rooms = $query->orderBy('hostel_id')->orderBy('room_number')->paginate(15)->withQueryString();

        return view('rooms.index', [
            'rooms' => $rooms,
            'hostels' => Hostel::query()->orderBy('name')->get(),
            'activeBooking' => null,
            'hasActiveRequest' => false,
            'stats' => [
                'total' => Room::count(),
                'available' => Room::query()->where('status', RoomStatus::Available->value)->whereColumn('occupied_beds', '<', 'capacity')->count(),
                'full' => Room::query()->where('status', RoomStatus::Full->value)->count(),
                'maintenance' => Room::query()->where('status', RoomStatus::Maintenance->value)->count(),
            ],
        ]);
    }

    public function show(Request $request, Room $room): View
    {
        $user = $request->user();
        $room->load(['hostel', 'occupants.student.studentProfile']);

        return view('rooms.show', [
            'room' => $room,
            'isStaff' => $user->isStaff(),
            'activeBooking' => $user->activeBooking()->with('room.hostel')->first(),
            'hasActiveRequest' => $user->bookings()
                ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Approved->value])
                ->exists(),
            'similarRooms' => Room::query()
                ->with('hostel')
                ->where('hostel_id', $room->hostel_id)
                ->whereKeyNot($room->id)
                ->where('status', '!=', RoomStatus::Maintenance->value)
                ->inRandomOrder()
                ->limit(3)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
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
            'notes' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $image = $request->file('image');
        unset($data['image']);

        $data['occupied_beds'] = 0;
        $data['status'] ??= RoomStatus::Available;

        if ($image) {
            $data['image_path'] = $image->store('rooms', 'public');
        }

        Room::create($data);

        return back()->with('success', "Room {$data['room_number']} created.");
    }

    public function update(Request $request, Room $room): RedirectResponse
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
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $image = $request->file('image');
        unset($data['image']);

        if ($image) {
            if ($room->image_path && ! str_starts_with($room->image_path, 'images/')) {
                Storage::disk('public')->delete($room->image_path);
            }

            $data['image_path'] = $image->store('rooms', 'public');
        }

        if (isset($data['capacity']) && $data['capacity'] < $room->occupied_beds) {
            return back()->with('error', 'Capacity cannot be lower than the occupied beds.');
        }

        $room->update($data);
        $room->refresh()->syncStatus();

        return back()->with('success', "Room {$room->room_number} updated.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        if ($room->occupied_beds > 0) {
            return back()->with('error', 'Cannot delete a room that still has residents.');
        }

        $room->delete();

        return back()->with('success', 'Room deleted.');
    }
}
