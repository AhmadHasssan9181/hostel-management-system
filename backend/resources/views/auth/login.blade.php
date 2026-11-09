@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <div class="rise" x-data="{ email: '', password: '', show: false }">
        <p class="eyebrow">Welcome back</p>
        <h1 class="mt-2 text-[30px] font-semibold tracking-[-0.02em] text-ink">Sign in to HMS</h1>
        <p class="mt-2 text-sm leading-6 text-muted">
            Use your hostel account to manage rooms, complaints, fees and requests.
        </p>

        @if (session('success'))
            <div class="mt-5 flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft">
                <x-icon name="check" class="h-4 w-4 text-emerald-600" />
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-4" x-on:submit="$el.querySelector('button[type=submit]').disabled = true">
            @csrf

            <div>
                <label for="email" class="label">Email address</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-muted">
                        <x-icon name="envelope" class="h-4 w-4" />
                    </span>
                    <input id="email" name="email" type="email" autocomplete="email" required autofocus
                           x-model="email" value="{{ old('email') }}"
                           class="input pl-10" placeholder="you@hms.test">
                </div>
                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="label">Password</label>
                    <a href="{{ route('password.request') }}" class="text-[12px] font-semibold text-brand-700 hover:text-brand-900">Forgot password?</a>
                </div>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-muted">
                        <x-icon name="lock" class="h-4 w-4" />
                    </span>
                    <input id="password" name="password" :type="show ? 'text' : 'password'" autocomplete="current-password" required
                           x-model="password" class="input pl-10 pr-11" placeholder="••••••••">
                    <button type="button" x-on:click="show = !show"
                            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-muted transition hover:text-ink"
                            :aria-label="show ? 'Hide password' : 'Show password'">
                        <x-icon name="key" class="h-4 w-4" />
                    </button>
                </div>
                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2.5 text-[13px] text-ink-soft">
                <input type="checkbox" name="remember" value="1"
                       class="h-4 w-4 rounded border-line-strong text-brand-700 focus:ring-brand-500">
                Keep me signed in on this device
            </label>

            <button type="submit" class="btn btn-primary w-full py-3">
                Sign in
                <x-icon name="arrow-right" class="h-4 w-4" />
            </button>
        </form>

        <div class="my-7 flex items-center gap-3">
            <span class="h-px flex-1 bg-line"></span>
            <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-muted">Quick sign-in</span>
            <span class="h-px flex-1 bg-line"></span>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <button type="button" x-on:click="email = 'student1@hms.test'; password = 'Student@123'"
                    class="group rounded-xl border border-line bg-surface px-3 py-3 text-left transition hover:border-brand-300 hover:bg-brand-50/40">
                <span class="block text-[10px] font-semibold uppercase tracking-[0.12em] text-muted group-hover:text-brand-700 dark:group-hover:text-brand-300">Student</span>
                <span class="mt-1 block truncate text-[11px] font-medium text-ink">student1</span>
            </button>
            <button type="button" x-on:click="email = 'warden@hms.test'; password = 'Staff@1234'"
                    class="group rounded-xl border border-line bg-surface px-3 py-3 text-left transition hover:border-brand-300 hover:bg-brand-50/40">
                <span class="block text-[10px] font-semibold uppercase tracking-[0.12em] text-muted group-hover:text-brand-700 dark:group-hover:text-brand-300">Warden</span>
                <span class="mt-1 block truncate text-[11px] font-medium text-ink">warden</span>
            </button>
            <button type="button" x-on:click="email = 'admin@hms.test'; password = 'Admin@1234'"
                    class="group rounded-xl border border-line bg-surface px-3 py-3 text-left transition hover:border-brand-300 hover:bg-brand-50/40">
                <span class="block text-[10px] font-semibold uppercase tracking-[0.12em] text-muted group-hover:text-brand-700 dark:group-hover:text-brand-300">Admin</span>
                <span class="mt-1 block truncate text-[11px] font-medium text-ink">admin</span>
            </button>
        </div>
        <p class="mt-2 text-center text-[11px] text-muted">Tap a role to fill the credentials, then sign in.</p>

        <div class="mt-6 rounded-xl border border-line bg-canvas/60 px-4 py-3.5 text-center">
            <p class="text-xs font-medium text-ink-soft">Accounts are issued by the hostel office</p>
            <p class="mt-1 text-xs text-muted">
                Need access? Contact
                <a href="mailto:hostel@fuuast.edu.pk" class="font-medium text-ink-soft underline decoration-line-strong underline-offset-2 hover:text-ink">hostel@fuuast.edu.pk</a>
            </p>
        </div>
    </div>
@endsection
