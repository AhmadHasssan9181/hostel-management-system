@extends('layouts.auth')

@section('title', 'Reset password')

@section('content')
    <div class="rise">
        <p class="eyebrow">Choose a new password</p>
        <h1 class="mt-2 text-[30px] font-semibold tracking-[-0.02em] text-ink">Reset your password</h1>
        <p class="mt-2 text-sm leading-6 text-muted">
            Set a new password for <span class="font-medium text-ink">{{ $email }}</span>.
        </p>

        <form method="POST" action="{{ route('password.store') }}" class="mt-7 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="label">Email address</label>
                <input id="email" name="email" type="email" required value="{{ old('email', $email) }}" class="input">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">New password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       class="input" placeholder="••••••••">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="label">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       autocomplete="new-password" class="input" placeholder="••••••••">
            </div>

            <p class="hint">At least 8 characters, including letters, numbers and a symbol.</p>

            <button type="submit" class="btn btn-primary w-full py-3">Reset password</button>
        </form>
    </div>
@endsection
