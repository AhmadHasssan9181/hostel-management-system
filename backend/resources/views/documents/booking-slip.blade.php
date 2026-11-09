@extends('layouts.print')

@section('doc-title', 'Allocation slip · '.$booking->student?->name)

@section('content')
    @php
        $profile = $booking->student?->studentProfile;
        $room = $booking->room;
    @endphp

    <div class="print-page mx-auto w-full max-w-[190mm] rounded-xl bg-surface p-8 shadow-lift">
        <div class="flex items-start justify-between gap-6 border-b border-ink/15 pb-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-700 text-white">
                    <x-icon name="home" class="h-5 w-5" />
                </span>
                <div class="leading-tight">
                    <p class="text-[12px] font-bold uppercase tracking-[0.1em]">Federal Urdu University of Arts, Science &amp; Technology</p>
                    <p class="text-[9px] uppercase tracking-[0.18em] text-ink/55">Hostel Management System · Islamabad</p>
                </div>
            </div>
            <div class="text-right">
                <p class="whitespace-nowrap text-[11px] font-bold uppercase tracking-[0.12em]">Room Allocation Slip</p>
                <p class="mt-1 text-[9px] text-ink/55">Ref: HMS-BK-{{ str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT) }}</p>
                <p class="text-[9px] text-ink/55">Issued: {{ now()->format('d M Y') }}</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-6 text-[10px]">
            <div>
                <p class="mb-2 text-[8px] font-bold uppercase tracking-[0.18em] text-ink/45">Resident</p>
                <p class="text-[13px] font-bold text-ink">{{ $booking->student?->name }}</p>
                <p class="mt-1 text-ink/65">{{ $profile?->registration_number ?? 'Registration pending' }}</p>
                <p class="text-ink/65">{{ $profile?->department ?? '—' }} · Semester {{ $profile?->semester ?? '—' }}</p>
                <p class="text-ink/65">{{ $booking->student?->email }}</p>
                <p class="text-ink/65">{{ $booking->student?->phone ?? '—' }}</p>
            </div>
            <div class="rounded-lg bg-ink/[0.035] p-4">
                <p class="mb-2 text-[8px] font-bold uppercase tracking-[0.18em] text-ink/45">Allocation</p>
                <div class="flex items-end gap-3">
                    <p class="text-3xl font-bold leading-none">Room {{ $room?->room_number ?? '—' }}</p>
                    <span class="rounded border border-ink/20 px-1.5 py-0.5 text-[8px] font-semibold uppercase tracking-wide">
                        {{ $room?->room_type ?? '—' }}
                    </span>
                </div>
                <p class="mt-1 text-ink/65">{{ $room?->hostel?->name }}</p>
                <div class="mt-3 grid grid-cols-2 gap-2 text-[9px]">
                    <div>
                        <p class="uppercase tracking-wide text-ink/45">Floor</p>
                        <p class="font-semibold">{{ $room?->floor ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="uppercase tracking-wide text-ink/45">Monthly rent</p>
                        <p class="font-semibold">Rs {{ number_format((float) ($room?->monthly_rent ?? 0)) }}</p>
                    </div>
                    <div>
                        <p class="uppercase tracking-wide text-ink/45">Beds</p>
                        <p class="font-semibold">{{ $room?->occupied_beds ?? 0 }} / {{ $room?->capacity ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="uppercase tracking-wide text-ink/45">Status</p>
                        <p class="font-semibold capitalize">{{ str_replace('_', ' ', $booking->status->value) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-3 gap-3 text-[9px]">
            <div class="rounded border border-ink/12 p-3">
                <p class="uppercase tracking-wide text-ink/45">Requested from</p>
                <p class="mt-1 font-semibold">{{ $booking->requested_from?->format('d M Y') ?? '—' }}</p>
            </div>
            <div class="rounded border border-ink/12 p-3">
                <p class="uppercase tracking-wide text-ink/45">Reviewed on</p>
                <p class="mt-1 font-semibold">{{ $booking->reviewed_at?->format('d M Y') ?? 'Pending' }}</p>
            </div>
            <div class="rounded border border-ink/12 p-3">
                <p class="uppercase tracking-wide text-ink/45">Approved by</p>
                <p class="mt-1 font-semibold">{{ $booking->reviewer?->name ?? '—' }}</p>
            </div>
        </div>

        <div class="mt-6 text-[9px] leading-5 text-ink/65">
            <p class="font-bold uppercase tracking-wide text-ink/70">Terms of allocation</p>
            <ol class="mt-1 list-decimal space-y-0.5 pl-4">
                <li>This slip confirms a bed in the room shown above for the current semester.</li>
                <li>Hostel dues must be cleared by the due date printed on the fee voucher.</li>
                <li>Residents must follow hostel discipline rules; violations may lead to cancellation.</li>
                <li>Report any damage to hostel property to the office immediately.</li>
            </ol>
        </div>

        <div class="mt-10 flex items-end justify-between gap-8 text-[9px]">
            <div class="w-1/3 border-t border-dotted border-ink/40 pt-1 text-center">Resident signature</div>
            <div class="w-1/3 border-t border-dotted border-ink/40 pt-1 text-center">Warden / Hostel office</div>
        </div>

        <p class="mt-6 border-t border-ink/10 pt-2 text-[8px] uppercase tracking-[0.16em] text-ink/35">
            Generated by HMS on {{ now()->format('d M Y, H:i') }} · This is a computer generated document.
        </p>
    </div>
@endsection
