@extends('layouts.print')

@section('doc-title', 'Fee vouchers · '.str_replace('_', ' ', $status))

@section('content')
    @forelse ($fees as $fee)
        <div class="print-page mx-auto mb-6 w-full max-w-[190mm] rounded-xl bg-surface p-4 shadow-lift">
            @foreach (['Student copy', 'Bank copy', 'Office copy'] as $copy)
                @include('documents._fee-voucher-copy', ['fee' => $fee, 'copy' => $copy])

                @unless ($loop->last)
                    <div class="mb-3 flex items-center gap-2">
                        <span class="h-px flex-1 border-t border-dashed border-ink/25"></span>
                        <span class="text-[8px] uppercase tracking-[0.2em] text-ink/30">Cut here</span>
                        <span class="h-px flex-1 border-t border-dashed border-ink/25"></span>
                    </div>
                @endunless
            @endforeach
        </div>
    @empty
        <div class="mx-auto max-w-[190mm] rounded-xl bg-surface p-10 text-center shadow-lift">
            <p class="text-sm font-semibold text-ink">No vouchers to print</p>
            <p class="mt-1 text-sm text-muted">There are no invoices matching the selected filters.</p>
        </div>
    @endforelse
@endsection
