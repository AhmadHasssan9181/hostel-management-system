@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
])

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="rise min-w-0">
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="page-title mt-1">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-sub max-w-2xl">{{ $subtitle }}</p>
        @endif
    </div>

    @if (! $slot->isEmpty())
        <div class="rise rise-1 flex flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
