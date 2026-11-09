<?php

namespace App\Http\Controllers\Web;

use App\Enums\FeeStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\User;
use App\Services\FeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FeeController extends Controller
{
    public function __construct(private readonly FeeService $fees) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Fee::query()->with(['student.studentProfile', 'booking.room', 'verifier:id,name']);

        if ($user->isStudent()) {
            $query->where('student_id', $user->id);

            $own = Fee::query()->where('student_id', $user->id);

            return view('fees.index', [
                'fees' => $query->latest()->paginate(10),
                'isStaff' => false,
                'students' => collect(),
                'stats' => [
                    'outstanding' => (float) (clone $own)->outstanding()->sum(DB::raw('amount + fine')),
                    'paid' => (float) (clone $own)->where('status', FeeStatus::Paid->value)->sum(DB::raw('amount + fine')),
                    'invoices' => (clone $own)->count(),
                    'overdue' => (clone $own)->where('status', FeeStatus::Overdue->value)->count(),
                ],
            ]);
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

        return view('fees.index', [
            'fees' => $query->latest()->paginate(15)->withQueryString(),
            'isStaff' => true,
            'students' => User::query()
                ->where('role', Role::Student->value)
                ->with('studentProfile:id,user_id,registration_number')
                ->orderBy('name')
                ->get(['id', 'name']),
            'stats' => [
                'outstanding' => (float) Fee::query()->outstanding()->sum(DB::raw('amount + fine')),
                'paid' => (float) Fee::query()->where('status', FeeStatus::Paid->value)->sum(DB::raw('amount + fine')),
                'invoices' => Fee::count(),
                'overdue' => Fee::query()->where('status', FeeStatus::Overdue->value)->count(),
            ],
        ]);
    }

    public function voucher(Request $request, Fee $fee): View
    {
        if ($request->user()->isStudent()) {
            abort_unless($fee->student_id === $request->user()->id, 403);
        }

        return view('documents.fee-voucher', [
            'fee' => $fee->load('student.studentProfile', 'booking.room.hostel', 'verifier'),
        ]);
    }

    public function vouchers(Request $request): View
    {
        $query = Fee::query()->with('student.studentProfile', 'booking.room.hostel');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        } else {
            $query->whereIn('status', [FeeStatus::Unpaid->value, FeeStatus::Overdue->value]);
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

        return view('documents.fee-vouchers', [
            'fees' => $query->orderBy('due_date')->limit(60)->get(),
            'status' => $request->input('status', 'unpaid'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],            'booking_id' => ['nullable', 'exists:bookings,id'],
            'type' => ['sometimes', Rule::in(['hostel_fee', 'mess_fee', 'fine', 'security_deposit', 'other'])],
            'period' => ['nullable', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:0'],
            'fine' => ['sometimes', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
        ]);

        $student = User::findOrFail($data['student_id']);
        $fee = $this->fees->generate($student, $data);

        return back()->with('success', "Invoice {$fee->reference} generated.");
    }

    public function update(Request $request, Fee $fee): RedirectResponse
    {
        if (in_array($fee->status, [FeeStatus::Paid, FeeStatus::Waived], true) && ! $request->user()->isAdmin()) {
            return back()->with('error', 'Paid or waived invoices can only be edited by an administrator.');
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(['hostel_fee', 'mess_fee', 'fine', 'security_deposit', 'other'])],
            'period' => ['nullable', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:0'],
            'fine' => ['sometimes', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
        ]);

        $fee->update($data);

        return back()->with('success', "Invoice {$fee->reference} updated.");
    }

    public function pay(Request $request, Fee $fee): RedirectResponse
    {
        abort_unless($fee->student_id === $request->user()->id, 403);

        $data = $request->validate([
            'payment_method' => ['sometimes', 'string', 'max:30'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        try {
            $this->fees->submitPayment($fee, $request->user(), $data, $request->file('receipt'));
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment submitted. The office will verify your receipt.');
    }

    public function verify(Request $request, Fee $fee): RedirectResponse
    {
        $data = $request->validate([
            'approved' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->fees->verify($fee, $request->user(), (bool) $data['approved'], $data['note'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $data['approved'] ? 'Payment verified.' : 'Payment rejected.');
    }

    public function waive(Request $request, Fee $fee): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $this->fees->waive($fee, $request->user(), $data['note'] ?? null);

        return back()->with('success', "Invoice {$fee->reference} waived.");
    }
}
