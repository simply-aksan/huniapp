@props([
    'userName' => null,
    'userRole' => null,
])

@php
    $user = auth()->user();
    $name = $userName ?? $user?->name ?? 'Penyewa';
    $role = $userRole ?? 'Penghuni Kos';
    $initials = collect(explode(' ', $name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('');
@endphp

<header class="h-16 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4 border-b border-[#262A30] bg-[#16191E] select-none sticky top-0 z-30 shrink-0">
    <div class="flex items-center gap-3 flex-1 max-w-xl">
        <!-- Mobile Drawer Toggle Button -->
        <button
            type="button"
            x-on:click="mobileOpen = !mobileOpen"
            class="lg:hidden w-9 h-9 bg-[#1F2329] border border-[#2D323B] rounded-xl flex items-center justify-center text-white shadow-xs hover:border-[#4B5563] transition-all cursor-pointer shrink-0"
            aria-label="Buka menu navigasi"
        >
            <x-ui.icon name="menu" class="w-4 h-4 text-white" />
        </button>

        <!-- Search Bar -->
        <div class="relative w-full max-w-md">
            <div class="bg-[#1F2329] border border-[#2D323B] rounded-xl px-3.5 py-1.5 flex items-center gap-2 shadow-xs transition-all focus-within:border-[#15803D] focus-within:ring-2 focus-within:ring-[#15803D]/20">
                <x-ui.icon name="search" class="w-3.5 h-3.5 text-[#9CA3AF] shrink-0" />
                <input
                    type="text"
                    placeholder="Cari tagihan, fasilitas, bantuan..."
                    class="w-full bg-transparent border-none text-xs font-medium text-white placeholder-[#6B7280] focus:outline-none"
                />
                <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold text-[#9CA3AF] bg-[#282D35] border border-[#374151] rounded-md font-mono">
                    Search
                </kbd>
            </div>
        </div>
    </div>

    <!-- Actions & Profile Menu -->
    <div class="flex items-center gap-2.5 shrink-0">
        <!-- Notification Dropdown -->
        <x-notifications.dropdown />

        <!-- User Profile Dropdown (Alpine.js) -->
        <div x-data="{ open: false }" class="relative">
            <button
                type="button"
                x-on:click="open = !open"
                x-on:click.outside="open = false"
                class="bg-[#1F2329] border border-[#2D323B] rounded-xl pl-1.5 pr-2.5 py-1 flex items-center gap-2 shadow-xs cursor-pointer hover:border-[#4B5563] transition-all"
                aria-expanded="false"
                :aria-expanded="open.toString()"
            >
                <div class="w-7 h-7 rounded-lg bg-[#1E293B] text-[#93C5FD] border border-[#334155] overflow-hidden flex items-center justify-center font-bold text-xs">
                    {{ $initials ?: 'BS' }}
                </div>
                <div class="text-left hidden sm:block">
                    <p class="text-xs font-semibold text-white leading-tight">{{ $name }}</p>
                    <p class="text-[10px] text-[#9CA3AF] leading-none">{{ $role }}</p>
                </div>
                <x-ui.icon name="chevron-down" class="w-3 h-3 text-[#9CA3AF] shrink-0" />
            </button>

            <!-- Dropdown Menu -->
            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                class="absolute right-0 mt-2 w-56 bg-[#181B20] border border-[#2D323B] rounded-2xl shadow-[0_10px_25px_-5px_rgba(0,0,0,0.5)] p-1.5 z-50 divide-y divide-[#262A30]"
            >
                <div class="px-3 py-2">
                    <p class="text-xs font-bold text-white">{{ $name }}</p>
                    <p class="text-[11px] text-[#9CA3AF] truncate">{{ $user?->email }}</p>
                    <div class="mt-1.5">
                        <x-ui.badge variant="info" size="sm">Penghuni Kos</x-ui.badge>
                    </div>
                </div>

                <div class="py-1">
                    <a
                        href="{{ route('tenant.dashboard') }}"
                        class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-[#E5E7EB] hover:bg-[#282D35] hover:text-white rounded-lg transition-colors"
                    >
                        <x-ui.icon name="home" class="w-4 h-4 text-[#9CA3AF]" />
                        <span>Beranda</span>
                    </a>
                </div>

                <div class="pt-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors cursor-pointer text-left"
                        >
                            <x-ui.icon name="log-out" class="w-4 h-4" />
                            <span>Keluar dari Akun</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
