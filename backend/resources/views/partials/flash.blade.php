@if (session('success'))
    <div class="fixed bottom-5 right-5 z-[60] w-[min(92vw,380px)]"
         x-data="{ show: true }" x-init="setTimeout(() => show = false, 5200)" x-show="show" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-surface px-4 py-3.5 shadow-lift">
            <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-canvas text-emerald-700 dark:text-emerald-300 ring-1 ring-line">
                <x-icon name="check" class="h-4 w-4" />
            </span>
            <p class="flex-1 text-sm font-medium leading-6 text-ink">{{ session('success') }}</p>
            <button type="button" class="icon-btn -mr-1 -mt-1 h-7 w-7" x-on:click="show = false" aria-label="Dismiss">
                <x-icon name="x" class="h-3.5 w-3.5" />
            </button>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="fixed bottom-5 right-5 z-[60] w-[min(92vw,380px)]"
         x-data="{ show: true }" x-init="setTimeout(() => show = false, 6500)" x-show="show" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-surface px-4 py-3.5 shadow-lift">
            <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-canvas text-rose-700 dark:text-rose-300 ring-1 ring-line">
                <x-icon name="alert" class="h-4 w-4" />
            </span>
            <p class="flex-1 text-sm font-medium leading-6 text-ink">{{ session('error') }}</p>
            <button type="button" class="icon-btn -mr-1 -mt-1 h-7 w-7" x-on:click="show = false" aria-label="Dismiss">
                <x-icon name="x" class="h-3.5 w-3.5" />
            </button>
        </div>
    </div>
@endif

@if (session('status'))
    <div class="fixed bottom-5 right-5 z-[60] w-[min(92vw,380px)]"
         x-data="{ show: true }" x-init="setTimeout(() => show = false, 5200)" x-show="show" x-cloak>
        <div class="flex items-start gap-3 rounded-xl border border-sky-200 bg-surface px-4 py-3.5 shadow-lift">
            <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-canvas text-sky-700 dark:text-sky-300 ring-1 ring-line">
                <x-icon name="bell" class="h-4 w-4" />
            </span>
            <p class="flex-1 text-sm font-medium leading-6 text-ink">{{ session('status') }}</p>
        </div>
    </div>
@endif
