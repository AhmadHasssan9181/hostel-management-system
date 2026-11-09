@php
    $currency = fn ($value) => number_format((float) $value);
    $isPaid = $fee->status === \App\Enums\FeeStatus::Paid;
    $isWaived = $fee->status === \App\Enums\FeeStatus::Waived;
    $room = $fee->booking?->room;
@endphp

<section class="voucher-copy relative mb-2.5 overflow-hidden rounded-lg border border-ink/15 bg-surface px-5 py-3 text-ink">
    @if ($isPaid || $isWaived)
        <div class="pointer-events-none absolute inset-0 flex items-center justify-center" aria-hidden="true">
            <span class="rotate-[-16deg] text-[58px] font-black uppercase tracking-[0.28em] {{ $isPaid ? 'text-emerald-700/[0.07]' : 'text-zinc-600/[0.07]' }}">
                {{ $isPaid ? 'Paid' : 'Waived' }}
            </span>
        </div>
    @endif

    <div class="relative flex items-start justify-between gap-4 border-b border-ink/15 pb-2">
        <div class="flex items-center gap-2.5">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-700 text-white">
                <x-icon name="home" class="h-3.5 w-3.5" />
            </span>
            <div class="leading-tight">
                <p class="text-[10px] font-bold uppercase tracking-[0.1em]">Federal Urdu University of Arts, Science &amp; Technology</p>
                <p class="text-[8px] uppercase tracking-[0.16em] text-ink/55">Hostel Management System · Islamabad</p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-[12px] font-bold uppercase tracking-[0.12em]">Hostel Fee Voucher</p>
            <p class="mt-0.5 inline-flex rounded border border-ink/20 px-1.5 py-px text-[7.5px] font-semibold uppercase tracking-[0.14em]">
                {{ $copy }}
            </p>
        </div>
    </div>

    <div class="relative mt-2 grid grid-cols-4 gap-2 text-[8.5px]">
        <div>
            <p class="uppercase tracking-wide text-ink/45">Voucher no.</p>
            <p class="mt-0.5 text-[9.5px] font-bold">{{ $fee->reference }}</p>
        </div>
        <div>
            <p class="uppercase tracking-wide text-ink/45">Issue date</p>
            <p class="mt-0.5 text-[9.5px] font-semibold">{{ $fee->created_at->format('d M Y') }}</p>
        </div>
        <div>
            <p class="uppercase tracking-wide text-ink/45">Due date</p>
            <p class="mt-0.5 text-[9.5px] font-semibold">{{ $fee->due_date->format('d M Y') }}</p>
        </div>
        <div class="text-right">
            <p class="uppercase tracking-wide text-ink/45">Status</p>
            <p class="mt-0.5 inline-flex rounded border px-1.5 py-px text-[7.5px] font-bold uppercase tracking-[0.12em]
                {{ $isPaid ? 'border-emerald-600 text-emerald-700 dark:text-emerald-300' : ($isWaived ? 'border-zinc-500 text-zinc-600' : 'border-amber-600 text-amber-700 dark:text-amber-300') }}">
                {{ str_replace('_', ' ', $fee->status->value) }}
            </p>
        </div>
    </div>

    <div class="relative mt-2 grid grid-cols-4 gap-2 rounded-md bg-ink/[0.035] px-3 py-1.5 text-[8.5px]">
        <div class="col-span-2">
            <p class="uppercase tracking-wide text-ink/45">Student</p>
            <p class="mt-0.5 text-[9.5px] font-semibold">{{ $fee->student?->name }}</p>
        </div>
        <div>
            <p class="uppercase tracking-wide text-ink/45">Registration</p>
            <p class="mt-0.5 text-[9.5px] font-medium">{{ $fee->student?->studentProfile?->registration_number ?? '—' }}</p>
        </div>
        <div class="text-right">
            <p class="uppercase tracking-wide text-ink/45">Room</p>
            <p class="mt-0.5 text-[9.5px] font-medium">
                {{ $room?->room_number ?? '—' }}{{ $room ? ' · '.$room->hostel?->name : '' }}
            </p>
        </div>
    </div>

    <table class="relative mt-2 w-full border-collapse text-[8.5px]">
        <thead>
            <tr class="border-y border-ink/15 text-left uppercase tracking-wide text-ink/50">
                <th class="py-1 pr-2 font-semibold">Particulars</th>
                <th class="py-1 pr-2 font-semibold">Period</th>
                <th class="py-1 pr-2 text-right font-semibold">Amount</th>
                <th class="py-1 pr-2 text-right font-semibold">Fine</th>
                <th class="py-1 text-right font-semibold">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-ink/10">
                <td class="py-1 pr-2 font-medium capitalize">{{ str_replace('_', ' ', $fee->type) }}</td>
                <td class="py-1 pr-2">{{ $fee->period ?? '—' }}</td>
                <td class="py-1 pr-2 text-right">Rs {{ $currency($fee->amount) }}</td>
                <td class="py-1 pr-2 text-right">Rs {{ $currency($fee->fine) }}</td>
                <td class="py-1 text-right font-bold">Rs {{ $currency($fee->total()) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="relative mt-2 grid grid-cols-[1.6fr_1fr] gap-4">
        <div class="text-[8px] leading-3.5 text-ink/60">
            <p class="font-bold uppercase tracking-wide text-ink/70">Payment instructions</p>
            <p>Habib Bank · A/C: FUUAST Hostel Islamabad · 1234-5678901-01 · IBAN PK00 HABB 0000 1234 5678 9010.
                Easypaisa / JazzCash 0300-1234567. Quote the voucher number in the reference.</p>
            <p class="mt-0.5">Fees once paid are non-refundable. Late payment may attract a fine.</p>
            @if ($isPaid)
                <p class="mt-0.5 font-medium text-emerald-700 dark:text-emerald-300">
                    Verified {{ $fee->paid_at?->format('d M Y') }} by {{ $fee->verifier?->name ?? 'hostel office' }}
                    @if ($fee->payment_method) · {{ str_replace('_', ' ', $fee->payment_method) }} @endif
                </p>
            @endif
        </div>
        <div class="flex flex-col justify-end text-center text-[8px]">
            <div class="border-t border-dotted border-ink/40 pt-0.5">Authorised signature</div>
            <p class="mt-0.5 text-[7.5px] uppercase tracking-wide text-ink/45">Hostel Office · FUUAST</p>
        </div>
    </div>
</section>
