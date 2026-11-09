@extends('layouts.auth')

@section('title', 'Forgot password')

@section('content')
    <div class="rise">
        <p class="eyebrow">Account recovery</p>
        <h1 class="mt-2 text-[30px] font-semibold tracking-[-0.02em] text-ink">Forgot your password?</h1>
        <p class="mt-2 text-sm leading-6 text-muted">
            Enter your account email and we will send you a link to reset it.
        </p>

        <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-4">
            @csrf
            <div>
                <label for="email" class="label">Email address</label>
                <input id="email" name="email" type="email" required autofocus
                       value="{{ old('email') }}" class="input" placeholder="you@hms.test">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full py-3">
                Send reset link
                <x-icon name="arrow-right" class="h-4 w-4" />
            </button>
        </form>

        <p class="mt-7 text-center text-sm text-muted">
            Remembered it?
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-900">Back to sign in</a>
        </p>
    </div>
@endsection
