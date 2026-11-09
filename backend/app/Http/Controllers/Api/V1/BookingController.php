<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function index(Request $request): JsonResponse
    {
        $query = Booking::query()->with(['student.studentProfile', 'room.hostel', 'reviewer']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->integer('room_id'));
        }

        if ($request->filled('hostel_id')) {
            $query->whereHas('room', fn ($r) => $r->where('hostel_id', $request->integer('hostel_id')));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->whereHas('student', fn ($s) => $s->where('name', 'like', $search)->orWhere('email', 'like', $search));
        }

        return response()->json($query->latest()->paginate($this->perPage($request)));
    }

    public function mine(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->where('student_id', $request->user()->id)
            ->with('room.hostel', 'reviewer')
            ->latest()
            ->get();

        return response()->json(['bookings' => $bookings]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'requested_from' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = $this->bookings->request($request->user(), $data);

        return response()->json([
            'message' => 'Booking request submitted. It is now pending approval.',
            'booking' => $booking->load('room.hostel'),
        ], 201);
    }

    public function approve(Request $request, Booking $booking): JsonResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);

        $booking = $this->bookings->approve($booking, $request->user(), $data['review_note'] ?? null);

        return response()->json(['message' => 'Booking approved and room allocated.', 'booking' => $booking]);
    }

    public function reject(Request $request, Booking $booking): JsonResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);

        $booking = $this->bookings->reject($booking, $request->user(), $data['review_note'] ?? null);

        return response()->json(['message' => 'Booking rejected.', 'booking' => $booking]);
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($booking->student_id === $request->user()->id, 403, 'This booking does not belong to you.');

        $booking = $this->bookings->cancel($booking);

        return response()->json(['message' => 'Booking cancelled.', 'booking' => $booking]);
    }

    public function checkOut(Request $request, Booking $booking): JsonResponse
    {
        $booking = $this->bookings->checkOut($booking, $request->user());

        return response()->json(['message' => 'Resident checked out and bed released.', 'booking' => $booking]);
    }

    public function checkIn(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->status !== BookingStatus::Approved) {
            return response()->json(['message' => 'Only approved bookings can be checked in.'], 422);
        }

        $booking->student->studentProfile?->update(['check_in_status' => 'checked_in']);

        return response()->json([
            'message' => 'Resident checked in.',
            'booking' => $booking->fresh('student.studentProfile', 'room.hostel'),
        ]);
    }

    public function availableRooms(Request $request): JsonResponse
    {
        $query = Room::query()
            ->with('hostel')
            ->withAvailability()
            ->orderBy('hostel_id')
            ->orderBy('room_number');

        if ($request->filled('hostel_id')) {
            $query->where('hostel_id', $request->integer('hostel_id'));
        }

        if ($request->filled('room_type')) {
            $query->where('room_type', $request->string('room_type'));
        }

        return response()->json(['rooms' => $query->get()]);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
