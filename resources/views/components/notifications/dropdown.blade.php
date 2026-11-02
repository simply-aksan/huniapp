@php
    $user = auth()->user();
    $unreadCount = 0;
    $recentNotifications = collect();

    if ($user) {
        try {
            $unreadCount = $user->unreadNotifications()->count();
            $recentNotifications = $user->notifications()->take(5)->get();
        } catch (\Throwable $e) {
            $unreadCount = 0;
            $recentNotifications = collect();
        }
    }
@endphp

<div x-data="{ open: false }" class="relative">
    <!-- Notification Bell Trigger -->
    <button
        type="button"
        x-on:click="open = !open"
        x-on:click.outside="open = false"
        class="relative w-9 h-9 bg-[#1F2329] border border-[#2D323B] rounded-xl flex items-center justify-center text-white shadow-xs hover:border-[#4B5563] transition-all cursor-pointer focus:outline-none"
        aria-label="Pusat Notifikasi"
        :aria-expanded="open.toString()"
    >
        <x-ui.icon name="bell" class="w-4 h-4 text-white" />

        @if ($unreadCount > 0)
            <span class="absolute -top-1 -right-1 px-1 min-w-[18px] h-4.5 bg-[#15803D] text-white text-[10px] font-bold rounded-full border border-[#16191E] flex items-center justify-center leading-none shadow-xs">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown Panel -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
        class="absolute right-0 mt-2 w-80 sm:w-96 bg-[#181B20] border border-[#2D323B] rounded-2xl shadow-[0_10px_25px_-5px_rgba(0,0,0,0.5)] overflow-hidden z-50 divide-y divide-[#262A30]"
    >
        <!-- Header -->
        <div class="px-4 py-3 bg-[#1F2329] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-white">Notifikasi</span>
                @if ($unreadCount > 0)
                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-[#163820] text-[#86EFAC] border border-[#225422] rounded-full">
                        {{ $unreadCount }} baru
                    </span>
                @endif
            </div>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button
                        type="submit"
                        class="text-[11px] font-semibold text-[#86EFAC] hover:underline cursor-pointer"
                    >
                        Tandai semua dibaca
                    </button>
                </form>
            @endif
        </div>

        <!-- Notification List -->
        <div class="max-h-80 overflow-y-auto divide-y divide-[#262A30] huni-scrollbar">
            @forelse ($recentNotifications as $notification)
                @php
                    $isUnread = $notification->unread();
                    $data = (array) $notification->data;
                    $title = $data['title'] ?? 'Pemberitahuan Baru';
                    $message = $data['message'] ?? '';
                    $iconName = $data['icon'] ?? 'bell';
                @endphp

                <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="m-0 p-0">
                    @csrf
                    <button
                        type="submit"
                        class="w-full text-left p-3.5 flex items-start gap-3 transition-colors cursor-pointer {{ $isUnread ? 'bg-[#1F2329]/80 hover:bg-[#282D35]' : 'bg-[#181B20] hover:bg-[#1F2329]' }}"
                    >
                        <!-- Icon Avatar -->
                        <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center {{ $isUnread ? 'bg-[#163820] text-[#86EFAC] border border-[#225422]' : 'bg-[#282D35] text-[#9CA3AF]' }}">
                            <x-ui.icon :name="$iconName" class="w-4 h-4" />
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1">
                                <p class="text-xs font-semibold text-white truncate {{ $isUnread ? 'font-bold' : '' }}">
                                    {{ $title }}
                                </p>
                                @if ($isUnread)
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#15803D] shrink-0" title="Belum dibaca"></span>
                                @endif
                            </div>
                            <p class="text-[11px] text-[#9CA3AF] line-clamp-2 mt-0.5 leading-snug">
                                {{ $message }}
                            </p>
                            <p class="text-[10px] text-[#6B7280] mt-1 font-mono">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </button>
                </form>
            @empty
                <div class="py-8 px-4 text-center">
                    <div class="w-10 h-10 mx-auto rounded-xl bg-[#1F2329] border border-[#2D323B] flex items-center justify-center text-[#9CA3AF] mb-2.5">
                        <x-ui.icon name="bell" class="w-5 h-5" />
                    </div>
                    <p class="text-xs font-bold text-white">Tidak Ada Notifikasi</p>
                    <p class="text-[11px] text-[#9CA3AF] mt-0.5">Semua info terbaru akan muncul di sini.</p>
                </div>
            @endforelse
        </div>

        <!-- Footer Link -->
        <div class="p-2.5 bg-[#1F2329] text-center">
            <a
                href="{{ route('notifications.index') }}"
                class="inline-flex items-center justify-center gap-1.5 text-xs font-semibold text-[#E5E7EB] hover:text-white py-0.5 w-full transition-colors"
            >
                <span>Lihat Seluruh Notifikasi</span>
                <x-ui.icon name="arrow-right" class="w-3.5 h-3.5" />
            </a>
        </div>
    </div>
</div>
