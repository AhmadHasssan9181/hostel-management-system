@extends('layouts.app')

@section('title', 'Access denied')

@section('content')
    <div class="mx-auto max-w-lg py-16">
        <div class="card rise p-8 text-center">
            <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-paper text-ink ring-1 ring-line">
                <x-icon name="lock" class="h-6 w-6" />
            </span>
            <h1 class="mt-5 text-2xl font-semibold tracking-tight text-ink">Access denied</h1>
            <p class="mt-2 text-sm leading-6 text-muted">
                Your account does not have permission to view this page. If you think this is a mistake, contact the hostel office.
            </p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary mt-6">
                <x-icon name="arrow-right" class="h-4 w-4" />
                Back to dashboard
            </a>
        </div>
    </div>
@endsection
