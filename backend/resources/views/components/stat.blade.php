@props([
    'label',
    'value',
    'hint' => null,
    'icon' => 'chart',
    'tone' => 'brand',
    'delay' => null,
])

<div {{ $attributes->merge(['class' => 'card card-hover rise p-5 '.($delay ? 'rise-'.$delay : '')]) }}>
    <div class="flex items-center justify-between gap-2 text-muted">
        <p class="truncate text-xs font-medium">{{ $label }}</p>
        <x-icon :name="$icon" class="h-4 w-4 shrink-0 text-muted/70" />
    </div>
    <p class="num mt-3 text-[28px] font-semibold leading-none tracking-[-0.03em] text-ink" data-count="{{ $value }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-2 truncate text-xs text-muted">{{ $hint }}</p>
    @endif
</div>
