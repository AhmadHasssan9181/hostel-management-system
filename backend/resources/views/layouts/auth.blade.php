<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#faf9f5">
    <title>@yield('title', 'Sign in') · {{ config('app.name') }}</title>
    <script>
        (() => {
            const stored = localStorage.getItem('hms-theme');
            const dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (dark) document.documentElement.classList.add('dark');
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        })();
    </script>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
    <div class="grid min-h-screen lg:grid-cols-[1.04fr_1fr]">

        <aside class="auth-brand relative hidden overflow-hidden px-10 py-12 text-white lg:flex lg:flex-col lg:justify-between xl:px-14 xl:py-14">
            <img src="{{ asset('images/hero/campus.jpg') }}" alt=""
                 class="absolute inset-0 h-full w-full object-cover" aria-hidden="true">
            <div class="absolute inset-0 bg-gradient-to-br from-[#04241f]/95 via-[#06332b]/88 to-[#041b17]/95" aria-hidden="true"></div>
            <div class="absolute inset-0 auth-grid" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -left-24 top-1/3 h-72 w-72 rounded-full bg-brand-300/15 glow" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -right-16 bottom-0 h-80 w-80 rounded-full bg-accent-400/10 glow" aria-hidden="true"></div>

            <a href="{{ route('login') }}" class="relative flex items-center gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/20 backdrop-blur">
                    <x-icon name="home" class="h-5 w-5 text-white" />
                </span>
                <span class="leading-tight">
                    <span class="block text-[15px] font-semibold tracking-tight">HMS</span>
                    <span class="block text-[11px] uppercase tracking-[0.18em] text-white/60">Hostel Management</span>
                </span>
            </a>

            <div class="relative max-w-md rise">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#a9e3d3]">Federal Urdu University</p>
                <h1 class="mt-4 text-balance text-[38px] font-semibold leading-[1.1] tracking-[-0.03em] xl:text-[44px]">
                    A calmer way to run your hostel.
                </h1>
                <p class="mt-5 text-[15px] leading-7 text-white/70">
                    Rooms, residents, complaints and fees — one clear workspace for students and hostel staff.
                </p>

                <ul class="mt-9 space-y-4 text-sm text-white/80">
                    <li class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15">
                            <x-icon name="bed" class="h-4 w-4" />
                        </span>
                        Live room availability and allocation
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15">
                            <x-icon name="wrench" class="h-4 w-4" />
                        </span>
                        Ticket-based complaint resolution
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15">
                            <x-icon name="wallet" class="h-4 w-4" />
                        </span>
                        Fee invoices, receipts and verification
                    </li>
                </ul>
            </div>

            <div class="relative flex items-center justify-between border-t border-white/10 pt-6 text-[11px] uppercase tracking-[0.18em] text-white/45">
                <span>Hostel Operations</span>
                <span>Islamabad Campus</span>
            </div>
        </aside>

        <main class="relative flex min-h-screen flex-col">
            <div class="absolute right-6 top-6 hidden lg:block">
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line bg-surface/80 text-muted shadow-soft backdrop-blur transition hover:text-ink"
                        data-theme-toggle aria-label="Toggle dark mode" title="Toggle theme">
                    <x-icon name="moon" class="h-[18px] w-[18px] dark:hidden" />
                    <x-icon name="sun" class="hidden h-[18px] w-[18px] dark:block" />
                </button>
            </div>

            <div class="flex items-center justify-between px-5 py-5 sm:px-8 lg:hidden">
                <span class="flex items-center gap-2.5">
                    <span class="brand-mark inline-flex h-9 w-9 items-center justify-center rounded-xl">
                        <x-icon name="home" class="h-4 w-4" />
                    </span>
                    <span class="text-sm font-semibold tracking-tight">HMS</span>
                </span>
                <div class="flex items-center gap-3">
                    <span class="hidden text-[11px] font-medium uppercase tracking-[0.16em] text-muted sm:inline">Hostel Management</span>
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line bg-surface text-muted transition hover:text-ink"
                            data-theme-toggle aria-label="Toggle dark mode" title="Toggle theme">
                        <x-icon name="moon" class="h-[18px] w-[18px] dark:hidden" />
                        <x-icon name="sun" class="hidden h-[18px] w-[18px] dark:block" />
                    </button>
                </div>
            </div>

            <div class="flex flex-1 items-center justify-center px-5 py-8 sm:px-8">
                <div class="w-full max-w-[430px]">
                    @yield('content')
                </div>
            </div>

            <p class="px-5 pb-6 text-center text-[11px] text-muted sm:px-8">
                © {{ date('Y') }} Hostel Management System · FUUAST Islamabad
            </p>
        </main>
    </div>
</body>
</html>
