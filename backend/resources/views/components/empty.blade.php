@props([
    'icon' => 'sparkles',
    'illustration' => null,
    'title' => 'Nothing here yet',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border border-dashed border-line-strong bg-canvas/50 px-6 py-12 text-center']) }}>
    @if ($illustration)
        <x-illustration :name="$illustration" class="h-32 w-auto sm:h-36" />
    @else
        <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-surface text-brand-600 shadow-soft ring-1 ring-line">
            <x-icon :name="$icon" class="h-6 w-6" />
        </span>
    @endif

    <p class="mt-4 text-sm font-semibold text-ink">{{ $title }}</p>
    @if ($message)
        <p class="mt-1.5 max-w-sm text-sm leading-6 text-muted">{{ $message }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
