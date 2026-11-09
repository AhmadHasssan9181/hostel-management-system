<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FeeStatus;
use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\User;
use App\Services\FeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeeController extends Controller
{
    public function __construct(private readonly FeeService $fees) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Fee::query()->with(['student.studentProfile', 'booking.room.hostel', 'verifier']);

        if ($user->isStudent()) {
            $query->where('student_id', $user->id);
        } elseif ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('period')) {
            $query->where('period', $request->string('period'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->where('reference', 'like', $search)
                ->orWhereHas('student', fn ($s) => $s->where('name', 'like', $search)));
        }

        return response()->json($query->latest()->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'type' => ['sometimes', Rule::in(['hostel_fee', 'mess_fee', 'fine', 'security_deposit', 'other'])],
            'period' => ['nullable', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:0'],
            'fine' => ['sometimes', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
        ]);

        $student = User::findOrFail($data['student_id']);
        $fee = $this->fees->generate($student, $data);

        return response()->json(['message' => 'Invoice generated.', 'fee' => $fee->load('student')], 201);
    }

    public function show(Request $request, Fee $fee): JsonResponse
    {
        if ($request->user()->isStudent()) {
            abort_unless($fee->student_id === $request->user()->id, 403, 'This invoice does not belong to you.');
        }

        return response()->json(['fee' => $fee->load('student.studentProfile', 'booking.room.hostel', 'verifier')]);
    }

    public function pay(Request $request, Fee $fee): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => ['sometimes', 'string', 'max:30'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $fee = $this->fees->submitPayment($fee, $request->user(), $data, $request->file('receipt'));

        return response()->json([
            'message' => 'Payment submitted and is awaiting verification.',
            'fee' => $fee,
        ]);
    }

    public function verify(Request $request, Fee $fee): JsonResponse
    {
        $data = $request->validate([
            'approved' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $fee = $this->fees->verify($fee, $request->user(), $data['approved'], $data['note'] ?? null);

        return response()->json(['message' => 'Payment reviewed.', 'fee' => $fee]);
    }

    public function waive(Request $request, Fee $fee): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        $fee = $this->fees->waive($fee, $request->user(), $data['note'] ?? null);

        return response()->json(['message' => 'Invoice waived.', 'fee' => $fee]);
    }

    public function destroy(Request $request, Fee $fee): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Only administrators can delete invoices.');
        abort_if($fee->status === FeeStatus::Paid, 422, 'Paid invoices cannot be deleted.');

        $fee->delete();

        return response()->json(['message' => 'Invoice deleted.']);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
