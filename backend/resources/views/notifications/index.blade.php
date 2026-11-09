@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <x-page-header
        title="Notifications"
        subtitle="Everything the hostel office has sent your way."
        eyebrow="Account">
        @if ($unread > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="btn btn-secondary">
                    <x-icon name="check" class="h-4 w-4" />
                    Mark all as read
                </button>
            </form>
        @endif
    </x-page-header>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex rounded-xl border border-line bg-surface p-1">
            <a href="{{ route('notifications.index') }}"
               class="rounded-lg px-3.5 py-1.5 text-[13px] font-semibold transition {{ request()->boolean('unread_only') ? 'text-muted hover:text-ink' : 'bg-paper text-ink' }}">
                All
            </a>
            <a href="{{ route('notifications.index', ['unread_only' => 1]) }}"
               class="rounded-lg px-3.5 py-1.5 text-[13px] font-semibold transition {{ request()->boolean('unread_only') ? 'bg-paper text-ink' : 'text-muted hover:text-ink' }}">
                Unread
                @if ($unread > 0)
                    <span class="ml-1 rounded-full bg-accent-100 px-1.5 py-0.5 text-[10px] font-bold text-accent-700">{{ $unread }}</span>
                @endif
            </a>
        </div>
        <p class="text-xs text-muted">{{ $notifications->total() }} notification(s)</p>
    </div>

    @if ($notifications->isEmpty())
        <div class="mt-5">
            <x-empty illustration="bell" title="You are all caught up" message="New updates about bookings, fees and complaints will appear here." />
        </div>
    @else
        <div class="card mt-5 divide-y divide-line overflow-hidden">
            @foreach ($notifications as $notification)
                @php
                    $icon = match ($notification->type) {
                        'fee' => 'wallet',
                        'complaint', 'complaint_escalation' => 'wrench',
                        'booking' => 'clipboard',
                        'room_swap' => 'swap',
                        'facility' => 'storefront',
                        'request' => 'calendar',
                        default => 'bell',
                    };
                    $unreadNotification = $notification->read_at === null;
                @endphp
                <div class="flex items-start gap-4 px-5 py-4 transition hover:bg-paper/60 {{ $unreadNotification ? 'bg-canvas/70' : '' }}">
                    <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $unreadNotification ? 'bg-paper text-ink' : 'bg-paper text-muted ring-1 ring-line' }}">
                        <x-icon :name="$icon" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-ink">{{ $notification->title }}</p>
                            @if ($unreadNotification)
                                <span class="h-1.5 w-1.5 rounded-full bg-accent-500"></span>
                            @endif
                        </div>
                        @if ($notification->body)
                            <p class="mt-1 text-sm leading-6 text-ink-soft">{{ $notification->body }}</p>
                        @endif
                        <p class="mt-1.5 text-[11px] text-muted">{{ $notification->created_at->format('d M Y, H:i') }} · {{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @if ($unreadNotification)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                @csrf
                                <button class="btn btn-ghost btn-sm">Mark read</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('notifications.destroy', $notification) }}"
                              onsubmit="return confirm('Delete this notification?');">
                            @csrf
                            @method('DELETE')
                            <button class="icon-btn h-8 w-8 text-muted hover:text-rose-600 dark:text-rose-400" title="Delete" aria-label="Delete notification">
                                <x-icon name="x" class="h-3.5 w-3.5" />
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
@endsection
