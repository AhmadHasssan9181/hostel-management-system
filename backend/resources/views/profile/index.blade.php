@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    @php($profile = $user->studentProfile)

    <x-page-header
        title="Profile & settings"
        subtitle="Keep your contact details current so the hostel office can reach you."
        eyebrow="Account" />

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <section class="card rise p-6 text-center">
                <span class="mx-auto inline-flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-brand-200 text-2xl font-semibold uppercase text-brand-800">
                    {{ str($user->name)->explode(' ')->take(2)->map(fn ($p) => str($p)->substr(0, 1))->implode('') }}
                </span>
                <h2 class="mt-4 text-lg font-semibold tracking-tight text-ink">{{ $user->name }}</h2>
                <p class="mt-1 text-sm text-muted">{{ $user->email }}</p>
                <div class="mt-3 flex items-center justify-center gap-3 text-xs text-muted">
                    <span class="font-medium capitalize text-ink">{{ $user->role->value }}</span>
                    <span>·</span>
                    <x-status :value="$user->status" />
                </div>

                <dl class="mt-6 space-y-3 border-t border-line pt-5 text-left text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Phone</dt>
                        <dd class="font-medium text-ink">{{ $user->phone ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Member since</dt>
                        <dd class="font-medium text-ink">{{ $user->created_at->format('M Y') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Last login</dt>
                        <dd class="font-medium text-ink">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</dd>
                    </div>
                    @if ($user->isStudent())
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted">Registration</dt>
                            <dd class="text-right font-medium text-ink">{{ $profile?->registration_number ?? 'Pending' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted">Room</dt>
                            <dd class="text-right font-medium text-ink">
                                {{ $user->activeBooking?->room?->room_number ?? 'Not allocated' }}
                                @if ($user->activeBooking?->room)
                                    <span class="block text-[11px] font-normal text-muted">{{ $user->activeBooking->room->hostel?->name }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>
        </div>

        <div class="space-y-6 lg:col-span-2">
            <section class="card rise rise-2 overflow-hidden">
                <div class="border-b border-line px-6 py-4">
                    <h2 class="section-title">Personal information</h2>
                    <p class="text-xs text-muted">Your name and contact details</p>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4 p-6">
                    @csrf
                    @method('PATCH')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Full name</label>
                            <input name="name" value="{{ old('name', $user->name) }}" class="input" required>
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input value="{{ $user->email }}" class="input bg-paper text-muted" disabled>
                            <p class="hint">Contact the office to change your email.</p>
                        </div>
                        <div>
                            <label class="label">Phone</label>
                            <input name="phone" value="{{ old('phone', $user->phone) }}" class="input" placeholder="0311-0000000">
                        </div>
                        @if ($user->isStudent())
                            <div>
                                <label class="label">Semester</label>
                                <input value="{{ $profile?->semester }}" class="input bg-paper text-muted" disabled>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label">Address</label>
                                <input name="address" value="{{ old('address', $profile?->address) }}" class="input" placeholder="Rawalpindi, Punjab">
                            </div>
                            <div>
                                <label class="label">Guardian name</label>
                                <input name="guardian_name" value="{{ old('guardian_name', $profile?->guardian_name) }}" class="input">
                            </div>
                            <div>
                                <label class="label">Guardian phone</label>
                                <input name="guardian_phone" value="{{ old('guardian_phone', $profile?->guardian_phone) }}" class="input">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label">Medical notes <span class="font-normal text-muted">(allergies, conditions)</span></label>
                                <textarea name="medical_notes" class="textarea" rows="3">{{ old('medical_notes', $profile?->medical_notes) }}</textarea>
                            </div>
                        @endif
                    </div>
                    <div class="flex justify-end border-t border-line pt-4">
                        <button class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </section>

            <section class="card rise rise-3 overflow-hidden">
                <div class="border-b border-line px-6 py-4">
                    <h2 class="section-title">Password</h2>
                    <p class="text-xs text-muted">Use at least 8 characters with letters, numbers and a symbol</p>
                </div>

                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4 p-6">
                    @csrf
                    @method('PATCH')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="label">Current password</label>
                            <input name="current_password" type="password" class="input" required autocomplete="current-password">
                            @error('current_password') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">New password</label>
                            <input name="password" type="password" class="input" required autocomplete="new-password">
                            @error('password') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Confirm new password</label>
                            <input name="password_confirmation" type="password" class="input" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-line pt-4">
                        <button class="btn btn-secondary">Update password</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
