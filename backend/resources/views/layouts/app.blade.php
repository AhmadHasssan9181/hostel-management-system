<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#faf9f5">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
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
<body class="min-h-screen bg-canvas font-sans text-ink antialiased" x-data="{ sidebar: false }">

    <div x-show="sidebar" x-cloak class="fixed inset-0 z-40 bg-ink/30 backdrop-blur-[2px] lg:hidden"
         x-transition.opacity x-on:click="sidebar = false"></div>

    <aside class="fixed inset-y-0 left-0 z-50 w-[260px] border-r border-line bg-surface/95 backdrop-blur-xl transition-transform duration-300 ease-out lg:translate-x-0"
           :class="sidebar ? 'translate-x-0' : '-translate-x-full'">
        @include('partials.sidebar')
    </aside>

    <div class="flex min-h-screen flex-col lg:pl-[260px]">
        @include('partials.topbar')

        <main class="flex-1 px-4 pb-20 pt-8 sm:px-6 lg:px-9 xl:px-12">
            <div class="mx-auto w-full max-w-[1320px]">
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-surface px-5 py-4 shadow-soft rise">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-canvas text-rose-600 dark:text-rose-400 ring-1 ring-line">
                                <x-icon name="alert" class="h-3.5 w-3.5" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-ink">Please check the form</p>
                                <ul class="mt-1 list-disc space-y-0.5 pl-4 text-xs text-muted">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <footer class="border-t border-line/70 px-4 py-6 sm:px-6 lg:px-9 xl:px-12">
            <div class="mx-auto flex w-full max-w-[1320px] flex-col gap-2 text-xs text-muted sm:flex-row sm:items-center sm:justify-between">
                <p>© {{ date('Y') }} Hostel Management System · Federal Urdu University of Arts, Science &amp; Technology</p>
                <p class="num">v1.0.0 · Islamabad Campus</p>
            </div>
        </footer>
    </div>

    @include('partials.flash')
</body>
</html>
