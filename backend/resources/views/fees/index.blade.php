@extends('layouts.app')

@section('title', 'Fees')

@section('content')
    @php($currency = fn ($value) => 'Rs '.number_format((float) $value))

    <x-page-header
        :title="$isStaff ? 'Fees & invoices' : 'Fees & payments'"
        :subtitle="$isStaff ? 'Generate invoices, verify receipts and track outstanding balances.' : 'Review your hostel invoices and upload payment receipts for verification.'"
        eyebrow="Services">
        @if ($isStaff)
            <a href="{{ route('fees.vouchers', request()->only('status', 'type', 'period', 'search')) }}" target="_blank" class="btn btn-secondary">
                <x-icon name="clipboard" class="h-4 w-4" />
                Print vouchers
            </a>
            <button type="button" class="btn btn-primary" x-on:click="$dispatch('open-modal', 'fee-create')">
                <x-icon name="plus" class="h-4 w-4" />
                Generate invoice
            </button>
        @else
            <a href="{{ route('profile.index') }}" class="btn btn-secondary">
                <x-icon name="user" class="h-4 w-4" />
                Billing profile
            </a>
        @endif
    </x-page-header>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Outstanding" :value="$currency($stats['outstanding'])" hint="Unpaid and overdue" icon="banknotes" tone="amber" :delay="1" />
        <x-stat label="Paid" :value="$currency($stats['paid'])" hint="Verified payments" icon="check" tone="emerald" :delay="2" />
        <x-stat label="Invoices" :value="$stats['invoices']" hint="All time" icon="wallet" tone="brand" :delay="3" />
        <x-stat label="Overdue" :value="$stats['overdue']" hint="Past the due date" icon="alert" tone="rose" :delay="4" />
    </div>

    @if ($isStaff)
        <form method="GET" action="{{ route('fees.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
            <div class="min-w-[200px] flex-1">
                <label class="label" for="search">Search</label>
                <input id="search" name="search" value="{{ request('search') }}" class="input" placeholder="Reference or student name">
            </div>
            <div class="w-full sm:w-44">
                <label class="label" for="status">Status</label>
                <select id="status" name="status" class="select">
                    <option value="">Any status</option>
                    @foreach (['unpaid', 'pending_verification', 'paid', 'overdue', 'waived'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-44">
                <label class="label" for="type">Type</label>
                <select id="type" name="type" class="select">
                    <option value="">All types</option>
                    @foreach (['hostel_fee', 'mess_fee', 'fine', 'security_deposit', 'other'] as $type)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
            @if (request()->hasAny(['search', 'status', 'type']))
                <a href="{{ route('fees.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="table-shell mt-5 rise rise-2">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Student</th>
                            <th>Amount</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fees as $fee)
                            <tr>
                                <td>
                                    <p class="text-sm font-semibold text-ink">{{ $fee->reference }}</p>
                                    <p class="text-[11px] capitalize text-muted">{{ str_replace('_', ' ', $fee->type) }} · {{ $fee->period }}</p>
                                </td>
                                <td>
                                    <p class="text-sm text-ink">{{ $fee->student?->name }}</p>
                                    <p class="text-[11px] text-muted">{{ $fee->student?->studentProfile?->registration_number }}</p>
                                </td>
                                <td>
                                    <p class="text-sm font-semibold text-ink">{{ $currency($fee->total()) }}</p>
                                    @if ((float) $fee->fine > 0)
                                        <p class="text-[11px] text-rose-600 dark:text-rose-400">incl. {{ $currency($fee->fine) }} fine</p>
                                    @endif
                                </td>
                                <td class="text-xs text-ink-soft">{{ $fee->due_date->format('d M Y') }}</td>
                                <td><x-status :value="$fee->status" /></td>
                                <td>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('fees.voucher', $fee) }}" target="_blank"
                                           class="icon-btn h-8 w-8" title="Print voucher / receipt" aria-label="Print voucher">
                                            <x-icon name="clipboard" class="h-4 w-4" />
                                        </a>
                                        @if (! in_array($fee->status->value, ['paid', 'waived'], true))
                                            <button type="button" class="btn btn-ghost btn-sm"
                                                    x-on:click="$dispatch('open-modal', 'fee-edit-{{ $fee->id }}')">
                                                Edit
                                            </button>
                                        @endif
                                        @if ($fee->status->value === 'pending_verification')
                                            <button type="button" class="btn btn-primary btn-sm"
                                                    x-on:click="$dispatch('open-modal', 'fee-verify-{{ $fee->id }}')">
                                                Review
                                            </button>
                                        @elseif (in_array($fee->status->value, ['unpaid', 'overdue'], true))
                                            <form method="POST" action="{{ route('fees.waive', $fee) }}"
                                                  onsubmit="return confirm('Waive invoice {{ $fee->reference }}?');">
                                                @csrf
                                                <button class="btn btn-ghost btn-sm">Waive</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-muted">Settled</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-10 text-center text-sm text-muted">No invoices match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $fees->links() }}</div>

        @foreach ($fees as $fee)
            @if (! in_array($fee->status->value, ['paid', 'waived'], true))
                <x-modal :name="'fee-edit-'.$fee->id" title="Edit invoice" :description="$fee->reference">
                    <form method="POST" action="{{ route('fees.update', $fee) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label">Type</label>
                                <select name="type" class="select">
                                    @foreach (['hostel_fee', 'mess_fee', 'fine', 'security_deposit', 'other'] as $type)
                                        <option value="{{ $type }}" @selected($fee->type === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Period</label>
                                <input name="period" value="{{ $fee->period }}" class="input" placeholder="2026-11">
                            </div>
                            <div>
                                <label class="label">Amount (Rs)</label>
                                <input name="amount" type="number" min="0" step="1" value="{{ (int) $fee->amount }}" class="input" required>
                            </div>
                            <div>
                                <label class="label">Fine (Rs)</label>
                                <input name="fine" type="number" min="0" step="1" value="{{ (int) $fee->fine }}" class="input">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label">Due date</label>
                                <input name="due_date" type="date" value="{{ $fee->due_date->toDateString() }}" class="input" required>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                            <button class="btn btn-primary">Save invoice</button>
                        </div>
                    </form>
                </x-modal>
            @endif
        @endforeach

        @foreach ($fees as $fee)
            @if ($fee->status->value === 'pending_verification')
                <x-modal :name="'fee-verify-'.$fee->id" title="Review payment" :description="$fee->reference.' · '.$currency($fee->total())">
                    <div class="space-y-4">
                        <div class="rounded-xl bg-paper p-4 ring-1 ring-line">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted">Student</span>
                                <span class="font-semibold text-ink">{{ $fee->student?->name }}</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-sm">
                                <span class="text-muted">Method</span>
                                <span class="font-medium capitalize text-ink">{{ str_replace('_', ' ', $fee->payment_method ?? '—') }}</span>
                            </div>
                            @if ($fee->receipt_url)
                                <a href="{{ $fee->receipt_url }}" target="_blank" class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-brand-700 hover:text-brand-900">
                                    <x-icon name="clipboard" class="h-4 w-4" />
                                    View uploaded receipt
                                </a>
                            @else
                                <p class="mt-3 text-xs text-muted">No receipt uploaded.</p>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('fees.verify', $fee) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="label">Decision</label>
                                <select name="approved" class="select">
                                    <option value="1">Approve payment</option>
                                    <option value="0">Reject payment</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Note <span class="font-normal text-muted">(optional)</span></label>
                                <textarea name="note" class="textarea" rows="2" placeholder="Reason for rejection, or a reference number"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-1">
                                <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                                <button class="btn btn-primary">Submit review</button>
                            </div>
                        </form>
                    </div>
                </x-modal>
            @endif
        @endforeach

        <x-modal name="fee-create" title="Generate an invoice" description="Create a fee record and send it to the student portal.">
            <form method="POST" action="{{ route('fees.store') }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Student</label>
                        <select name="student_id" class="select" required>
                            <option value="">Choose a student</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }} · {{ $student->studentProfile?->registration_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Type</label>
                        <select name="type" class="select">
                            <option value="hostel_fee">Hostel fee</option>
                            <option value="mess_fee">Mess fee</option>
                            <option value="fine">Fine</option>
                            <option value="security_deposit">Security deposit</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Period</label>
                        <input name="period" value="{{ now()->format('Y-m') }}" class="input" placeholder="2026-11">
                    </div>
                    <div>
                        <label class="label">Amount (Rs)</label>
                        <input name="amount" type="number" min="0" step="1" value="6500" class="input" required>
                    </div>
                    <div>
                        <label class="label">Fine (Rs)</label>
                        <input name="fine" type="number" min="0" step="1" value="0" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Due date</label>
                        <input name="due_date" type="date" value="{{ now()->addDays(10)->toDateString() }}" class="input" required>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button class="btn btn-primary">Generate invoice</button>
                </div>
            </form>
        </x-modal>
    @else
        @if ($fees->isEmpty())
            <div class="mt-6">
                <x-empty illustration="wallet" title="No invoices yet"
                         message="Once the hostel office generates your fee invoice, it will show up here with payment options." />
            </div>
        @else
            <div class="mt-6 space-y-4">
                @foreach ($fees as $fee)
                    <article class="card rise overflow-hidden">
                        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-start gap-4">
                                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-paper text-ink ring-1 ring-line">
                                    <x-icon name="wallet" class="h-6 w-6" />
                                </span>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-ink">{{ $fee->reference }}</p>
                                        <x-status :value="$fee->status" />
                                    </div>
                                    <p class="mt-0.5 text-sm capitalize text-muted">
                                        {{ str_replace('_', ' ', $fee->type) }} · {{ $fee->period }}
                                    </p>
                                    <p class="mt-1 text-xs text-muted">Due {{ $fee->due_date->format('d M Y') }}</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                                <p class="text-lg font-semibold tracking-tight text-ink">{{ $currency($fee->total()) }}</p>
                                @if (in_array($fee->status->value, ['unpaid', 'overdue', 'pending_verification'], true))
                                    @if ($fee->status->value === 'pending_verification')
                                        <span class="text-[11px] font-medium text-sky-700 dark:text-sky-300">Awaiting verification</span>
                                    @else
                                        <button type="button" class="btn btn-primary btn-sm"
                                                x-on:click="$dispatch('open-modal', 'fee-pay-{{ $fee->id }}')">
                                            Pay now
                                        </button>
                                    @endif
                                @endif
                                <a href="{{ route('fees.voucher', $fee) }}" target="_blank" class="btn btn-ghost btn-sm">
                                    <x-icon name="clipboard" class="h-3.5 w-3.5" />
                                    {{ $fee->status->value === 'paid' ? 'Receipt' : 'Voucher' }}
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">{{ $fees->links() }}</div>

            @foreach ($fees as $fee)
                @if (in_array($fee->status->value, ['unpaid', 'overdue'], true))
                    <x-modal :name="'fee-pay-'.$fee->id" title="Pay invoice" :description="$fee->reference.' · '.$currency($fee->total())">
                        <form method="POST" action="{{ route('fees.pay', $fee) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div>
                                <label class="label">Payment method</label>
                                <select name="payment_method" class="select">
                                    <option value="bank_transfer">Bank transfer</option>
                                    <option value="easypaisa">Easypaisa</option>
                                    <option value="jazzcash">JazzCash</option>
                                    <option value="cash">Cash at office</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Receipt <span class="font-normal text-muted">(jpg, png or pdf · max 5 MB)</span></label>
                                <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" class="input py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-paper file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-ink">
                            </div>
                            <div class="rounded-xl bg-paper px-4 py-3 text-xs leading-5 text-muted ring-1 ring-line">
                                The office verifies the receipt before marking this invoice as paid. You will receive a notification when it is confirmed.
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" class="btn btn-ghost" x-on:click="open = false">Cancel</button>
                                <button class="btn btn-primary">Submit payment</button>
                            </div>
                        </form>
                    </x-modal>
                @endif
            @endforeach
        @endif
    @endif
@endsection
