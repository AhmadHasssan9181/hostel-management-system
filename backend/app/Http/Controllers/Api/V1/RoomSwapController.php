<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RoomSwapRequest;
use App\Services\RoomSwapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomSwapController extends Controller
{
    public function __construct(private readonly RoomSwapService $swaps) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = RoomSwapRequest::query()->with([
            'student.studentProfile',
            'currentRoom.hostel',
            'requestedRoom.hostel',
            'reviewer',
        ]);

        if ($user->isStudent()) {
            $query->where('student_id', $user->id);
        } elseif ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->latest()->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'requested_room_id' => ['required', 'exists:rooms,id'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $swap = $this->swaps->request($request->user(), $data);

        return response()->json([
            'message' => 'Room swap request submitted.',
            'swap_request' => $swap->load('currentRoom.hostel', 'requestedRoom.hostel'),
        ], 201);
    }

    public function show(Request $request, RoomSwapRequest $swapRequest): JsonResponse
    {
        if ($request->user()->isStudent()) {
            abort_unless($swapRequest->student_id === $request->user()->id, 403, 'This request does not belong to you.');
        }

        return response()->json([
            'swap_request' => $swapRequest->load([
                'student.studentProfile',
                'currentRoom.hostel',
                'requestedRoom.hostel',
                'reviewer',
            ]),
        ]);
    }

    public function approve(Request $request, RoomSwapRequest $swapRequest): JsonResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);

        $swap = $this->swaps->approve($swapRequest, $request->user(), $data['review_note'] ?? null);

        return response()->json(['message' => 'Room swap approved and allocation updated.', 'swap_request' => $swap]);
    }

    public function reject(Request $request, RoomSwapRequest $swapRequest): JsonResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);

        $swap = $this->swaps->reject($swapRequest, $request->user(), $data['review_note'] ?? null);

        return response()->json(['message' => 'Room swap rejected.', 'swap_request' => $swap]);
    }

    public function cancel(Request $request, RoomSwapRequest $swapRequest): JsonResponse
    {
        abort_unless($swapRequest->student_id === $request->user()->id, 403, 'This request does not belong to you.');

        $swap = $this->swaps->cancel($swapRequest);

        return response()->json(['message' => 'Room swap request cancelled.', 'swap_request' => $swap]);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
