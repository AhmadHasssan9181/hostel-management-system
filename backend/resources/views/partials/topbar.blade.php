@php
    $user = auth()->user();
    $unread = $user->appNotifications()->whereNull('read_at')->count();
@endphp

<header class="sticky top-0 z-30 border-b border-line/80 bg-canvas/85 backdrop-blur-xl">
    <div class="mx-auto flex h-16 w-full max-w-[1320px] items-center justify-between gap-3 px-4 sm:px-6 lg:px-9 xl:px-12">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button" class="icon-btn lg:hidden" x-on:click="sidebar = true" aria-label="Open navigation">
                <x-icon name="menu" class="h-5 w-5" />
            </button>

            <div class="hidden items-center gap-2 text-xs text-muted sm:flex">
                <span class="font-medium text-ink-soft">{{ $user->isStudent() ? 'Student portal' : 'Staff workspace' }}</span>
                <span class="text-line-strong">·</span>
                <span>{{ now()->format('D, d M Y') }}</span>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <button type="button" class="icon-btn" data-theme-toggle aria-label="Toggle dark mode" title="Toggle theme">
                <x-icon name="moon" class="h-[18px] w-[18px] dark:hidden" />
                <x-icon name="sun" class="hidden h-[18px] w-[18px] dark:block" />
            </button>

            <a href="{{ route('notifications.index') }}" class="icon-btn relative" aria-label="Notifications">
                <x-icon name="bell" class="h-[18px] w-[18px]" />
                @if ($unread > 0)
                    <span class="absolute right-2 top-2 h-1.5 w-1.5 rounded-full bg-brand-600 ring-2 ring-canvas"></span>
                @endif
            </a>

            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                <button type="button" x-on:click="open = !open"
                        class="flex items-center gap-2 rounded-xl border border-transparent p-1 pr-2 transition hover:border-line hover:bg-surface">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-paper text-[11px] font-semibold uppercase text-ink">
                        {{ str($user->name)->explode(' ')->take(2)->map(fn ($part) => str($part)->substr(0, 1))->implode('') }}
                    </span>
                    <span class="hidden leading-tight sm:block">
                        <span class="block max-w-[140px] truncate text-[13px] font-medium text-ink">{{ $user->name }}</span>
                        <span class="block text-[10px] capitalize text-muted">{{ $user->role->value }}</span>
                    </span>
                    <x-icon name="chevron-down" class="hidden h-3.5 w-3.5 text-muted sm:block" />
                </button>

                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-line bg-surface p-1.5 shadow-lift">
                    <div class="px-2.5 pb-2 pt-1.5">
                        <p class="truncate text-[13px] font-semibold text-ink">{{ $user->name }}</p>
                        <p class="truncate text-[11px] text-muted">{{ $user->email }}</p>
                    </div>
                    <div class="my-1 h-px bg-line"></div>
                    <a href="{{ route('profile.index') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-medium text-ink-soft transition hover:bg-paper hover:text-ink">
                        <x-icon name="user" class="h-4 w-4" /> My profile
                    </a>
                    <a href="{{ route('notifications.index') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-medium text-ink-soft transition hover:bg-paper hover:text-ink">
                        <x-icon name="bell" class="h-4 w-4" /> Notifications
                        @if ($unread > 0)
                            <span class="num ml-auto text-xs font-semibold text-ink">{{ $unread }}</span>
                        @endif
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-medium text-rose-600 dark:text-rose-400 transition hover:bg-rose-50 dark:hover:bg-rose-500/10">
                            <x-icon name="logout" class="h-4 w-4" /> Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
