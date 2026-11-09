@extends('layouts.print')

@section('doc-title', 'Fee voucher · '.$fee->reference)

@section('content')
    <div class="print-page mx-auto w-full max-w-[190mm] rounded-xl bg-surface p-4 shadow-lift">
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
@endsection
