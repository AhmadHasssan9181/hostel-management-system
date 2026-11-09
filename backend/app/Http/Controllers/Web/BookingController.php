<?php

namespace App\Http\Controllers\Web;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hostel;
use App\Models\Room;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isStudent()) {
            $bookings = Booking::query()
                ->where('student_id', $user->id)
                ->with('room.hostel', 'reviewer')
                ->latest()
                ->paginate(10);

            return view('bookings.index', [
                'bookings' => $bookings,
                'hostels' => collect(),
                'isStaff' => false,
                'stats' => [
                    'total' => Booking::where('student_id', $user->id)->count(),
                    'pending' => Booking::where('student_id', $user->id)->where('status', BookingStatus::Pending->value)->count(),
                    'approved' => Booking::where('student_id', $user->id)->where('status', BookingStatus::Approved->value)->count(),
                ],
            ]);
        }

        $query = Booking::query()->with(['student.studentProfile', 'room.hostel', 'reviewer']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('hostel_id')) {
            $query->whereHas('room', fn ($r) => $r->where('hostel_id', $request->integer('hostel_id')));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->whereHas('student', fn ($s) => $s->where('name', 'like', $search)->orWhere('email', 'like', $search));
        }

        return view('bookings.index', [
            'bookings' => $query->latest()->paginate(15)->withQueryString(),
            'hostels' => Hostel::query()->orderBy('name')->get(),
            'rooms' => Room::query()->with('hostel')->orderBy('hostel_id')->orderBy('room_number')->get(),
            'isStaff' => true,
            'stats' => [
                'total' => Booking::count(),
                'pending' => Booking::where('status', BookingStatus::Pending->value)->count(),
                'approved' => Booking::where('status', BookingStatus::Approved->value)->count(),
                'checked_out' => Booking::where('status', BookingStatus::CheckedOut->value)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'requested_from' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->bookings->request($request->user(), $data);

        return redirect()->route('bookings.index')
            ->with('success', 'Room request submitted. You will be notified once it is reviewed.');
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->bookings->update($booking, $data, $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Booking updated.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->student_id === $request->user()->id, 403);

        $this->bookings->cancel($booking);

        return back()->with('success', 'Booking request cancelled.');
    }

    public function approve(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:500']]);

        try {
            $this->bookings->approve($booking, $request->user(), $data['review_note'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Booking approved and the room has been allocated.');
    }

    public function reject(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:500']]);

        try {
            $this->bookings->reject($booking, $request->user(), $data['review_note'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Booking rejected.');
    }

    public function slip(Request $request, Booking $booking): View
    {
        if ($request->user()->isStudent()) {
            abort_unless($booking->student_id === $request->user()->id, 403);
        }

        return view('documents.booking-slip', [
            'booking' => $booking->load('student.studentProfile', 'room.hostel', 'reviewer'),
        ]);
    }

    public function checkIn(Booking $booking): RedirectResponse
    {
        if ($booking->status !== BookingStatus::Approved) {
            return back()->with('error', 'Only approved bookings can be checked in.');
        }

        $booking->student->studentProfile?->update(['check_in_status' => 'checked_in']);

        return back()->with('success', 'Resident checked in.');
    }

    public function checkOut(Request $request, Booking $booking): RedirectResponse
    {
        try {
            $this->bookings->checkOut($booking, $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Resident checked out and the bed is free again.');
    }
}
