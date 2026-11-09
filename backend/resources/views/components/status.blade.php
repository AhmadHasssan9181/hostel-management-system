@props(['value', 'label' => null, 'onImage' => false])

@php
    $key = $value instanceof \BackedEnum ? $value->value : (string) $value;

    $bars = [
        'approved' => 'bg-emerald-500',
        'paid' => 'bg-emerald-500',
        'resolved' => 'bg-emerald-500',
        'operational' => 'bg-emerald-500',
        'good' => 'bg-emerald-500',
        'active' => 'bg-emerald-500',
        'available' => 'bg-emerald-500',
        'checked_in' => 'bg-emerald-500',
        'clear' => 'bg-emerald-500',

        'pending' => 'bg-amber-500',
        'pending_verification' => 'bg-sky-500',
        'open' => 'bg-sky-500',
        'in_progress' => 'bg-indigo-500',
        'maintenance' => 'bg-amber-500',
        'damaged' => 'bg-amber-500',
        'high' => 'bg-amber-500',
        'warning' => 'bg-amber-500',
        'unpaid' => 'bg-amber-500',

        'rejected' => 'bg-rose-500',
        'overdue' => 'bg-rose-500',
        'emergency' => 'bg-rose-500',
        'broken' => 'bg-rose-500',
        'suspended' => 'bg-rose-500',
        'urgent' => 'bg-rose-500',
        'escalated' => 'bg-rose-500',
        'probation' => 'bg-rose-500',

        'full' => 'bg-zinc-500',
    ];

    $labels = [
        'pending_verification' => 'Awaiting verification',
        'checked_in' => 'Checked in',
        'checked_out' => 'Checked out',
        'in_progress' => 'In progress',
    ];

    $bar = $bars[$key] ?? 'bg-zinc-400';
    $text = $label ?? $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
@endphp

<span {{ $attributes->merge(['class' => 'status']) }}>
    <span class="status-bar {{ $bar }}"></span>
    <span class="status-label {{ $onImage ? 'text-white/90' : '' }}">{{ $text }}</span>
</span>
