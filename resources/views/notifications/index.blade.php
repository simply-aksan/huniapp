@php
    $user = auth()->user();
    $layout = $user?->isOwner() ? 'layouts.owner' : 'layouts.tenant';
    $totalCount = $totalCount ?? 0;
@endphp

<x-dynamic-component :component="$layout" title="HUNI — Pusat Notifikasi" activeNav="notifications">
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-[#E8F5E9] border border-[#C8E6C9] flex items-center justify-center text-[#15803D]">
                        <x-ui.icon name="bell" class="w-4 h-4 text-[#15803D]" />
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-[#121417] tracking-tight">Pusat Notifikasi</h1>
                        <p class="text-xs text-[#5E6470]">Riwayat seluruh pemberitahuan tagihan, kontrak, dan pengaduan Anda</p>
                    </div>
                </div>
            </div>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}" class="shrink-0">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-[#DEDAD1] bg-white text-xs font-semibold text-[#121417] hover:bg-[#F4F1EA] transition-all cursor-pointer shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                    >
                        <x-ui.icon name="check" class="w-3.5 h-3.5 text-[#5E6470]" />
                        <span>Tandai Semua Dibaca ({{ $unreadCount }})</span>
                    </button>
                </form>
            @endif
        </div>

        <!-- Filter Bar Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-3 sm:p-4 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-center justify-between">
            <div class="inline-flex items-center gap-1 bg-[#F9F8F5] p-1 rounded-xl border border-[#DEDAD1] text-xs">
                <a
                    href="{{ route('notifications.index', ['filter' => 'all']) }}"
                    class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-2 {{ $currentFilter === 'all' ? 'bg-white text-[#121417] font-semibold shadow-[0_1px_2px_rgba(18,20,23,0.05)] border border-[#DEDAD1]' : 'text-[#5E6470] hover:text-[#121417] font-medium hover:bg-white/60' }}"
                >
                    <span>Semua</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono {{ $currentFilter === 'all' ? 'bg-[#F4F1EA] text-[#121417]' : 'bg-[#E8E5DF] text-[#5E6470]' }}">
                        {{ $totalCount }}
                    </span>
                </a>

                <a
                    href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                    class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-2 {{ $currentFilter === 'unread' ? 'bg-white text-[#121417] font-semibold shadow-[0_1px_2px_rgba(18,20,23,0.05)] border border-[#DEDAD1]' : 'text-[#5E6470] hover:text-[#121417] font-medium hover:bg-white/60' }}"
                >
                    <span>Belum Dibaca</span>
                    @if ($unreadCount > 0)
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono bg-[#E8F5E9] text-[#15803D] font-semibold">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </a>
            </div>

            <span class="text-xs text-[#767C85] hidden sm:block">
                Menampilkan {{ $notifications->count() }} pemberitahuan
            </span>
        </div>

        <!-- Notification Cards -->
        <div class="space-y-3">
            @forelse ($notifications as $notification)
                @php
                    $isUnread = $notification->unread();
                    $data = (array) $notification->data;
                    $title = $data['title'] ?? 'Pemberitahuan Sistem';
                    $message = $data['message'] ?? '';
                    $iconName = $data['icon'] ?? 'bell';
                    $targetUrl = $data['url'] ?? null;
                @endphp

                <div class="bg-white border rounded-xl p-4 sm:p-5 transition-all shadow-[0_1px_3px_rgba(18,20,23,0.04)] {{ $isUnread ? 'border-[#C8E6C9] bg-[#F9FCF9]' : 'border-[#DEDAD1] hover:border-[#CDC8BD]' }}">
                    <div class="flex items-start gap-4">
                        <!-- Icon Badge -->
                        <div class="w-9 h-9 rounded-lg shrink-0 flex items-center justify-center {{ $isUnread ? 'bg-[#E8F5E9] text-[#15803D] border border-[#C8E6C9]' : 'bg-[#F4F1EA] text-[#5E6470] border border-[#DEDAD1]' }}">
                            <x-ui.icon :name="$iconName" class="w-4 h-4" />
                        </div>

                        <!-- Main Body -->
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-[#121417]">
                                        {{ $title }}
                                    </h3>
                                    @if ($isUnread)
                                        <span class="px-2 py-0.5 text-[9px] font-semibold bg-[#E8F5E9] text-[#15803D] border border-[#C8E6C9] rounded-full">
                                            Baru
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-[#5E6470] font-mono">
                                    {{ $notification->created_at->translatedFormat('d M Y, H:i') }} ({{ $notification->created_at->diffForHumans() }})
                                </span>
                            </div>

                            <p class="text-xs text-[#5E6470] leading-relaxed">
                                {{ $message }}
                            </p>

                            <!-- Actions -->
                            <div class="mt-3 flex items-center gap-3">
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="inline">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#121417] hover:bg-[#20242B] text-white text-xs font-semibold shadow-[0_1px_2px_rgba(18,20,23,0.08)] transition-all cursor-pointer"
                                    >
                                        <span>Buka Detail</span>
                                        <x-ui.icon name="arrow-right" class="w-3.5 h-3.5 text-[#C7F36B]" />
                                    </button>
                                </form>

                                @if ($isUnread)
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="inline">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="text-xs font-medium text-[#5E6470] hover:text-[#121417] hover:underline cursor-pointer"
                                        >
                                            Tandai sudah dibaca
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <!-- Empty State -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-12 text-center shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                    <div class="w-12 h-12 mx-auto rounded-xl bg-[#F4F1EA] text-[#5E6470] flex items-center justify-center mb-3">
                        <x-ui.icon name="bell" class="w-6 h-6" />
                    </div>
                    <h3 class="text-sm font-bold text-[#121417]">
                        {{ $currentFilter === 'unread' ? 'Tidak Ada Notifikasi Baru' : 'Belum Ada Notifikasi' }}
                    </h3>
                    <p class="text-xs text-[#5E6470] max-w-sm mx-auto mt-1">
                        {{ $currentFilter === 'unread' ? 'Semua notifikasi sudah Anda baca. Bagus sekali!' : 'Seluruh aktivitas penagihan, pembayaran, kontrak, dan pengaduan akan muncul di sini.' }}
                    </p>
                    @if ($currentFilter === 'unread')
                        <div class="mt-4">
                            <a
                                href="{{ route('notifications.index', ['filter' => 'all']) }}"
                                class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-lg border border-[#DEDAD1] bg-white text-xs font-semibold text-[#121417] hover:bg-[#F4F1EA] transition-all shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                            >
                                Lihat Semua Riwayat
                            </a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if ($notifications->hasPages())
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-dynamic-component>
