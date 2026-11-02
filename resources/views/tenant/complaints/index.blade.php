<x-layouts.tenant title="Komplain & Pengaduan — HUNI" activeNav="complaints">
    <div class="space-y-6">
        <div class="sr-only border-2 border-[#171717] bg-[#F7F3EA]" aria-hidden="true"></div>
        <!-- Header Banner Card -->
        <div class="relative bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)] overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div>
                    <div class="flex items-center gap-2 text-xs font-medium text-[#5E6470] mb-2">
                        <a href="{{ route('tenant.dashboard') }}" class="hover:text-[#121417] transition-colors">Beranda</a>
                        <span>›</span>
                        <span class="text-[#121417] font-semibold">Komplain & Pengaduan</span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-bold text-[#121417] tracking-tight uppercase">
                        KOMPLAIN & TIKET
                    </h1>
                    <p class="text-sm text-[#5E6470] mt-1">
                        Laporkan masalah fasilitas, kamar, atau lingkungan kos langsung ke pemilik dengan pelacakan transparan.
                    </p>
                </div>

                <!-- CTA Button -->
                <div>
                    <a
                        href="{{ route('tenant.complaints.create') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs transition-all shadow-[0_1px_2px_rgba(18,20,23,0.08)] cursor-pointer"
                    >
                        <x-ui.icon name="plus" class="w-4 h-4 text-[#C7F36B]" />
                        <span>Buat Pengaduan Baru</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <x-ui.alert type="success">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if(session('error'))
            <x-ui.alert type="error">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <!-- Total Tiket -->
            <div class="bg-white border border-[#DEDAD1] rounded-xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#5E6470]">Total Tiket</span>
                    <span class="w-8 h-8 rounded-lg bg-[#EFF6FF] border border-[#BFDBFE] flex items-center justify-center text-blue-700">
                        <x-ui.icon name="document-text" class="w-4 h-4" />
                    </span>
                </div>
                <p class="text-2xl font-bold font-mono text-[#121417]">
                    {{ $stats['total'] }}
                </p>
                <p class="text-xs text-[#5E6470] mt-1">
                    Semua riwayat pengaduan
                </p>
            </div>

            <!-- Sedang Ditangani -->
            <div class="bg-white border border-[#DEDAD1] rounded-xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#5E6470]">Aktif Diproses</span>
                    <span class="w-8 h-8 rounded-lg bg-[#FEF3C7] border border-[#FDE68A] flex items-center justify-center text-amber-700">
                        <x-ui.icon name="clock" class="w-4 h-4" />
                    </span>
                </div>
                <p class="text-2xl font-bold font-mono {{ $stats['active_count'] > 0 ? 'text-[#B45309]' : 'text-[#121417]' }}">
                    {{ $stats['active_count'] }}
                </p>
                <p class="text-xs text-[#5E6470] mt-1">
                    Baru / Dalam penanganan
                </p>
            </div>

            <!-- Selesai -->
            <div class="bg-white border border-[#DEDAD1] rounded-xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#5E6470]">Terselesaikan</span>
                    <span class="w-8 h-8 rounded-lg bg-[#E8F5E9] border border-[#C8E6C9] flex items-center justify-center text-[#15803D]">
                        <x-ui.icon name="check-circle" class="w-4 h-4" />
                    </span>
                </div>
                <p class="text-2xl font-bold font-mono text-[#121417]">
                    {{ $stats['resolved_count'] }}
                </p>
                <p class="text-xs text-[#5E6470] mt-1">
                    Telah selesai diperbaiki
                </p>
            </div>

            <!-- Ditutup -->
            <div class="bg-white border border-[#DEDAD1] rounded-xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#5E6470]">Ditutup</span>
                    <span class="w-8 h-8 rounded-lg bg-[#F4F1EA] text-[#5E6470] flex items-center justify-center">
                        <x-ui.icon name="lock-closed" class="w-4 h-4" />
                    </span>
                </div>
                <p class="text-2xl font-bold font-mono text-[#121417]">
                    {{ $stats['closed_count'] }}
                </p>
                <p class="text-xs text-[#5E6470] mt-1">
                    Tiket ditutup permanen
                </p>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="bg-white border border-[#DEDAD1] rounded-xl p-4 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <form method="GET" action="{{ route('tenant.complaints.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <!-- Search Box -->
                <div class="sm:col-span-4">
                    <label for="search_q" class="sr-only">Cari No. Tiket atau Judul</label>
                    <div class="relative">
                        <x-ui.icon name="magnifying-glass" class="w-4 h-4 text-[#5E6470] absolute left-3 top-3 pointer-events-none" />
                        <input
                            type="text"
                            name="q"
                            id="search_q"
                            value="{{ $searchQuery }}"
                            placeholder="Cari no. tiket atau kata kunci..."
                            class="w-full pl-9 pr-3 py-2 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] placeholder-[#5E6470] focus:outline-none focus:bg-white focus:border-[#121417] transition-all"
                        />
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="sm:col-span-3">
                    <label for="filter_status" class="sr-only">Filter Status</label>
                    <select
                        name="status"
                        id="filter_status"
                        class="w-full px-3 py-2 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-none focus:bg-white focus:border-[#121417] transition-all cursor-pointer"
                    >
                        <option value="">Semua Status</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" {{ $selectedStatus === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="sm:col-span-3">
                    <label for="filter_category" class="sr-only">Filter Kategori</label>
                    <select
                        name="category"
                        id="filter_category"
                        class="w-full px-3 py-2 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-none focus:bg-white focus:border-[#121417] transition-all cursor-pointer"
                    >
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->value }}" {{ $selectedCategory === $cat->value ? 'selected' : '' }}>
                                {{ $cat->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit & Reset Buttons -->
                <div class="sm:col-span-2 flex items-center gap-2">
                    <button
                        type="submit"
                        class="flex-1 py-2 px-3 rounded-lg bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs transition-all cursor-pointer text-center shadow-[0_1px_2px_rgba(18,20,23,0.08)]"
                    >
                        Filter
                    </button>

                    @if($searchQuery || $selectedStatus || $selectedCategory || $selectedPriority)
                        <a
                            href="{{ route('tenant.complaints.index') }}"
                            class="py-2 px-3 rounded-lg border border-[#DEDAD1] bg-white text-xs font-semibold text-[#5E6470] hover:text-[#121417] hover:bg-[#F4F1EA] transition-all text-center shrink-0 cursor-pointer"
                            title="Reset Filter"
                        >
                            <x-ui.icon name="x-mark" class="w-3.5 h-3.5" />
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Complaint List -->
        @if($complaints->isEmpty())
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-8 sm:p-12 text-center shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                <div class="w-12 h-12 rounded-xl bg-[#F4F1EA] text-[#5E6470] flex items-center justify-center mx-auto">
                    <x-ui.icon name="chat-bubble-left-right" class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-[#121417]">
                        @if($searchQuery || $selectedStatus || $selectedCategory)
                            Pengaduan Tidak Ditemukan
                        @else
                            Belum Ada Pengaduan
                        @endif
                    </h3>
                    <p class="text-xs text-[#5E6470] mt-1 max-w-md mx-auto">
                        @if($searchQuery || $selectedStatus || $selectedCategory)
                            Tidak ada pengaduan yang sesuai dengan kriteria filter pencarianmu. Coba sesuaikan kata kunci atau reset filter.
                        @else
                            Fasilitas kamar atau kos mengalami gangguan? Jangan ragu laporkan kepada pemilik kos agar segera diperbaiki!
                        @endif
                    </p>
                </div>

                <div class="pt-2">
                    <a
                        href="{{ route('tenant.complaints.create') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-[0_1px_2px_rgba(18,20,23,0.08)] transition-all cursor-pointer"
                    >
                        <x-ui.icon name="plus" class="w-4 h-4 text-[#C7F36B]" />
                        <span>Buat Pengaduan Sekarang</span>
                    </a>
                </div>
            </div>
        @else
            <div class="space-y-3">
                @foreach($complaints as $complaint)
                    @php
                        $statusBadgeVariant = match($complaint->status) {
                            \App\Enums\ComplaintStatus::Baru->value => 'amber',
                            \App\Enums\ComplaintStatus::Diproses->value => 'blue',
                            \App\Enums\ComplaintStatus::MenungguTanggapan->value => 'purple',
                            \App\Enums\ComplaintStatus::Selesai->value => 'emerald',
                            \App\Enums\ComplaintStatus::Ditutup->value => 'neutral',
                            default => 'neutral',
                        };

                        $priorityBadgeVariant = match($complaint->priority) {
                            \App\Enums\ComplaintPriority::Darurat->value => 'rose',
                            \App\Enums\ComplaintPriority::Tinggi->value => 'amber',
                            \App\Enums\ComplaintPriority::Sedang->value => 'blue',
                            \App\Enums\ComplaintPriority::Rendah->value => 'neutral',
                            default => 'neutral',
                        };
                    @endphp

                    <div class="bg-white border border-[#DEDAD1] rounded-xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] hover:border-[#CDC8BD] transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <!-- Left: Ticket Details -->
                            <div class="space-y-2 flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs font-semibold text-[#121417] bg-[#F9F8F5] px-2.5 py-0.5 rounded-md border border-[#DEDAD1]">
                                        {{ $complaint->ticket_number }}
                                    </span>

                                    <!-- Status Badge -->
                                    <x-ui.badge :variant="$statusBadgeVariant" size="sm">
                                        {{ $complaint->statusLabel() }}
                                    </x-ui.badge>

                                    <!-- Category Badge -->
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium border border-[#DEDAD1] bg-[#F9F8F5] text-[#5E6470]">
                                        {{ $complaint->categoryLabel() }}
                                    </span>

                                    <!-- Priority Badge -->
                                    <x-ui.badge :variant="$priorityBadgeVariant" size="sm">
                                        {{ $complaint->priorityLabel() }}
                                    </x-ui.badge>

                                    @if($complaint->photo_url)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium border border-[#BFDBFE] bg-[#EFF6FF] text-blue-700" title="Memiliki Lampiran Foto">
                                            <x-ui.icon name="photo" class="w-3 h-3 text-blue-600" />
                                            <span>Foto</span>
                                        </span>
                                    @endif
                                </div>

                                <div>
                                    <h3 class="text-base font-bold text-[#121417] truncate">
                                        {{ $complaint->title }}
                                    </h3>
                                    <p class="text-xs text-[#5E6470] line-clamp-2 mt-0.5">
                                        {{ $complaint->description }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 text-xs text-[#5E6470] pt-0.5 font-mono">
                                    <span>
                                        Lokasi: <strong class="text-[#121417] font-semibold">{{ $complaint->property?->name ?? 'Kos' }}</strong> — Kamar <strong class="text-[#121417] font-semibold">{{ $complaint->room?->room_number ?? '-' }}</strong>
                                    </span>
                                    <span>•</span>
                                    <span>Diajukan: <strong class="text-[#121417] font-semibold">{{ $complaint->created_at->format('d/m/Y H:i') }}</strong></span>
                                    @if($complaint->isResolved() && $complaint->resolved_at)
                                        <span>•</span>
                                        <span class="text-[#15803D]">Diselesaikan: <strong>{{ $complaint->resolved_at->format('d/m/Y') }}</strong></span>
                                    @endif
                                </div>
                            </div>

                            <!-- Right: Action Button -->
                            <div class="flex items-center sm:self-center pt-3 md:pt-0 border-t md:border-t-0 border-[#DEDAD1] shrink-0">
                                <a
                                    href="{{ route('tenant.complaints.show', $complaint) }}"
                                    class="w-full sm:w-auto px-3.5 py-2 rounded-lg bg-white hover:bg-[#F4F1EA] text-[#121417] border border-[#DEDAD1] font-semibold text-xs shadow-[0_1px_2px_rgba(18,20,23,0.04)] transition-all text-center inline-flex items-center justify-center gap-1.5 cursor-pointer"
                                >
                                    <span>Lihat Percakapan</span>
                                    <x-ui.icon name="arrow-right" class="w-3.5 h-3.5 text-[#5E6470]" />
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Pagination Links -->
                <div class="pt-4">
                    {{ $complaints->links() }}
                </div>
            </div>
        @endif
    </div>
</x-layouts.tenant>
