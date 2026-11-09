@props([
    'name',
    'title',
    'description' => null,
    'maxWidth' => 'max-w-lg',
])

<div x-data="{ open: false }"
     x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
     x-on:keydown.escape.window="open = false"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50"
     role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-ink/40 backdrop-blur-[2px]"
         x-show="open"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-on:click="open = false"></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="w-full {{ $maxWidth }} overflow-hidden rounded-2xl bg-surface shadow-lift ring-1 ring-ink/5"
             x-show="open"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-[0.99]">
            <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4 sm:px-6">
                <div>
                    <h3 class="text-base font-semibold tracking-tight text-ink">{{ $title }}</h3>
                    @if ($description)
                        <p class="mt-1 text-sm text-muted">{{ $description }}</p>
                    @endif
                </div>
                <button type="button" class="icon-btn -mr-1" x-on:click="open = false" aria-label="Close dialog">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="max-h-[70vh] overflow-y-auto px-5 py-5 sm:px-6">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex items-center justify-end gap-2 border-t border-line bg-paper px-5 py-4 sm:px-6">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
