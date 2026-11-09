@php
    $user = auth()->user();
    $isStudent = $user->isStudent();
    $isAdmin = $user->isAdmin();
    $active = fn (string $pattern) => request()->routeIs($pattern) ? 'nav-link-active' : '';
    $unreadCount = $user->appNotifications()->whereNull('read_at')->count();
@endphp

<div class="flex h-full flex-col">
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-line px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <span class="brand-mark inline-flex h-8 w-8 items-center justify-center rounded-lg">
                <x-icon name="home" class="h-4 w-4" />
            </span>
            <span class="leading-tight">
                <span class="block text-sm font-semibold tracking-tight text-ink">HMS</span>
                <span class="block text-[10px] font-medium text-muted">Hostel Management</span>
            </span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 pb-4"
         x-on:click="if ($event.target.closest('a')) sidebar = false">

        <p class="px-3 pb-1.5 pt-4 text-[10px] font-semibold uppercase tracking-[0.16em] text-muted/75">Overview</p>
        <a href="{{ route('dashboard') }}" class="nav-link {{ $active('dashboard') }}">
            <x-icon name="dashboard" class="nav-icon" />
            Dashboard
        </a>

        <p class="px-3 pb-1.5 pt-5 text-[10px] font-semibold uppercase tracking-[0.16em] text-muted/75">Residence</p>
        @if ($isStudent)
            <a href="{{ route('rooms.index') }}" class="nav-link {{ $active('rooms.*') }}">
                <x-icon name="bed" class="nav-icon" />
                Browse rooms
            </a>
        @else
            <a href="{{ route('students.index') }}" class="nav-link {{ $active('students.*') }}">
                <x-icon name="users" class="nav-icon" />
                Students
            </a>
            <a href="{{ route('hostels.index') }}" class="nav-link {{ $active('hostels.*') }}">
                <x-icon name="building" class="nav-icon" />
                Hostel blocks
            </a>
            <a href="{{ route('rooms.index') }}" class="nav-link {{ $active('rooms.*') }}">
                <x-icon name="bed" class="nav-icon" />
                Rooms
            </a>
        @endif
        <a href="{{ route('bookings.index') }}" class="nav-link {{ $active('bookings.*') }}">
            <x-icon name="clipboard" class="nav-icon" />
            {{ $isStudent ? 'My bookings' : 'Bookings' }}
        </a>
        <a href="{{ route('swaps.index') }}" class="nav-link {{ $active('swaps.*') }}">
            <x-icon name="swap" class="nav-icon" />
            Room swaps
        </a>

        <p class="px-3 pb-1.5 pt-5 text-[10px] font-semibold uppercase tracking-[0.16em] text-muted/75">Services</p>
        <a href="{{ route('complaints.index') }}" class="nav-link {{ $active('complaints.*') }}">
            <x-icon name="wrench" class="nav-icon" />
            Complaints
        </a>
        <a href="{{ route('fees.index') }}" class="nav-link {{ $active('fees.*') }}">
            <x-icon name="wallet" class="nav-icon" />
            {{ $isStudent ? 'Fees & payments' : 'Fees' }}
        </a>
        <a href="{{ route('facilities.index') }}" class="nav-link {{ $active('facilities.*') }}">
            <x-icon name="storefront" class="nav-icon" />
            Facilities
        </a>
        @unless ($isStudent)
            <a href="{{ route('inventory.index') }}" class="nav-link {{ $active('inventory.*') }}">
                <x-icon name="box" class="nav-icon" />
                Inventory
            </a>
        @endunless
        <a href="{{ route('requests.index') }}" class="nav-link {{ $active('requests.*') }}">
            <x-icon name="calendar" class="nav-icon" />
            {{ $isStudent ? 'My requests' : 'Requests' }}
        </a>

        <p class="px-3 pb-1.5 pt-5 text-[10px] font-semibold uppercase tracking-[0.16em] text-muted/75">Account</p>
        <a href="{{ route('notifications.index') }}" class="nav-link {{ $active('notifications.*') }}">
            <x-icon name="bell" class="nav-icon" />
            Notifications
            @if ($unreadCount > 0)
                <span class="num ml-auto rounded-md bg-paper px-1.5 py-0.5 text-[11px] font-semibold text-ink">{{ $unreadCount }}</span>
            @endif
        </a>
        <a href="{{ route('profile.index') }}" class="nav-link {{ $active('profile.*') }}">
            <x-icon name="user" class="nav-icon" />
            Profile
        </a>
        @if ($isAdmin)
            <a href="{{ route('users.index') }}" class="nav-link {{ $active('users.*') }}">
                <x-icon name="key" class="nav-icon" />
                User accounts
            </a>
        @endif
    </nav>

    <div class="shrink-0 border-t border-line p-3">
        <div class="flex items-center gap-2.5 rounded-xl px-2 py-1.5">
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-paper text-[11px] font-semibold uppercase text-ink">
                {{ str($user->name)->explode(' ')->take(2)->map(fn ($part) => str($part)->substr(0, 1))->implode('') }}
            </span>
            <div class="min-w-0 flex-1 leading-tight">
                <p class="truncate text-[13px] font-semibold text-ink">{{ $user->name }}</p>
                <p class="truncate text-[11px] capitalize text-muted">{{ $user->role->value }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="icon-btn" title="Sign out" aria-label="Sign out">
                    <x-icon name="logout" class="h-4 w-4" />
                </button>
            </form>
        </div>
    </div>
</div>
