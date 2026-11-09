<?php

namespace App\Http\Controllers\Web;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomSwapRequest;
use App\Services\RoomSwapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoomSwapController extends Controller
{
    public function __construct(private readonly RoomSwapService $swaps) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isStudent()) {
            $activeBooking = $user->activeBooking()->with('room.hostel')->first();

            return view('swaps.index', [
                'swaps' => RoomSwapRequest::query()
                    ->where('student_id', $user->id)
                    ->with('currentRoom.hostel', 'requestedRoom.hostel', 'reviewer:id,name')
                    ->latest()
                    ->paginate(8),
                'isStaff' => false,
                'activeBooking' => $activeBooking,
                'hasPending' => RoomSwapRequest::query()
                    ->where('student_id', $user->id)
                    ->where('status', RequestStatus::Pending->value)
                    ->exists(),
                'rooms' => $activeBooking
                    ? Room::query()
                        ->with('hostel')
                        ->withAvailability()
                        ->whereKeyNot($activeBooking->room_id)
                        ->orderBy('hostel_id')
                        ->orderBy('room_number')
                        ->get()
                    : collect(),
                'stats' => [
                    'total' => RoomSwapRequest::where('student_id', $user->id)->count(),
                    'pending' => RoomSwapRequest::where('student_id', $user->id)->where('status', RequestStatus::Pending->value)->count(),
                    'approved' => RoomSwapRequest::where('student_id', $user->id)->where('status', RequestStatus::Approved->value)->count(),
                ],
            ]);
        }

        $query = RoomSwapRequest::query()->with([
            'student.studentProfile',
            'currentRoom.hostel',
            'requestedRoom.hostel',
            'reviewer:id,name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->whereHas('student', fn ($s) => $s->where('name', 'like', $search));
        }

        return view('swaps.index', [
            'swaps' => $query->latest()->paginate(15)->withQueryString(),
            'isStaff' => true,
            'activeBooking' => null,
            'hasPending' => false,
            'rooms' => collect(),
            'stats' => [
                'total' => RoomSwapRequest::count(),
                'pending' => RoomSwapRequest::where('status', RequestStatus::Pending->value)->count(),
                'approved' => RoomSwapRequest::where('status', RequestStatus::Approved->value)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'requested_room_id' => ['required', 'exists:rooms,id'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $this->swaps->request($request->user(), $data);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', 'Room swap request submitted for review.');
    }

    public function cancel(Request $request, RoomSwapRequest $swapRequest): RedirectResponse
    {
        abort_unless($swapRequest->student_id === $request->user()->id, 403);

        try {
            $this->swaps->cancel($swapRequest);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Room swap request cancelled.');
    }

    public function approve(Request $request, RoomSwapRequest $swapRequest): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:500']]);

        try {
            $this->swaps->approve($swapRequest, $request->user(), $data['review_note'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Swap approved. The allocation has been moved.');
    }

    public function reject(Request $request, RoomSwapRequest $swapRequest): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:500']]);

        try {
            $this->swaps->reject($swapRequest, $request->user(), $data['review_note'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Room swap request rejected.');
    }
}
