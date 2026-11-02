<x-layouts.owner title="Kelola Komplain & Pengaduan — HUNI" activeNav="complaints">
    <div class="space-y-6">
        <!-- Header Banner Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-2">
                        <a href="{{ route('owner.dashboard') }}" class="hover:text-[#121417] transition-colors">Dashboard</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <span class="text-[#121417] font-semibold">Komplain & Pengaduan</span>
                    </nav>

                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#C7F36B]/20 border border-[#C7F36B]/60 flex items-center justify-center shrink-0">
                            <x-ui.icon name="message-square" class="w-5 h-5 text-[#15803D]" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">
                                Kelola Komplain & Tiket
                            </h1>
                            <p class="text-xs sm:text-sm text-[#6B6B6B] mt-0.5 max-w-2xl">
                                Pantau keluhan fasilitas penyewa, delegasikan penanganan teknisi, dan perbarui status penyelesaian secara transparan.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] text-xs font-semibold text-[#121417]">
                    <span class="w-2 h-2 rounded-full bg-[#15803D]"></span>
                    <span>Respon Cepat & Tanggap</span>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <x-ui.alert type="success" :message="session('success')" dismissible="true" />
        @endif

        @if(session('error'))
            <x-ui.alert type="danger" :message="session('error')" dismissible="true" />
        @endif

        <!-- KPI Cards Grid (5 metrics) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <!-- Total Tiket -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-[#6B6B6B]">Total Tiket</span>
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#F4F2EC] border border-[#DEDAD1] flex items-center justify-center text-[#121417]">
                        <x-ui.icon name="file-text" class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-[#121417] font-mono tracking-tight">
                    {{ number_format($stats['total']) }}
                </p>
                <p class="text-[11px] text-[#6B6B6B] mt-0.5">
                    Semua pengaduan
                </p>
            </div>

            <!-- Tiket Baru -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-[#6B6B6B]">Tiket Baru</span>
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#FEF3C7] border border-[#FDE68A] flex items-center justify-center text-[#B45309]">
                        <x-ui.icon name="clock" class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold font-mono tracking-tight {{ $stats['baru_count'] > 0 ? 'text-[#B45309]' : 'text-[#121417]' }}">
                    {{ number_format($stats['baru_count']) }}
                </p>
                <p class="text-[11px] text-[#6B6B6B] mt-0.5">
                    Perlu ditinjau
                </p>
            </div>

            <!-- Sedang Diproses -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-[#6B6B6B]">Diproses</span>
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#EFF6FF] border border-[#BFDBFE] flex items-center justify-center text-[#1D4ED8]">
                        <x-ui.icon name="refresh" class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-[#121417] font-mono tracking-tight">
                    {{ number_format($stats['in_progress_count']) }}
                </p>
                <p class="text-[11px] text-[#6B6B6B] mt-0.5">
                    Dalam penanganan
                </p>
            </div>

            <!-- Menunggu Tanggapan -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-[#6B6B6B]">Menunggu Info</span>
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#FFEDD5] border border-[#FED7AA] flex items-center justify-center text-[#C2410C]">
                        <x-ui.icon name="message-square" class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-[#121417] font-mono tracking-tight">
                    {{ number_format($stats['pending_response_count']) }}
                </p>
                <p class="text-[11px] text-[#6B6B6B] mt-0.5">
                    Menanti balasan penyewa
                </p>
            </div>

            <!-- Selesai -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] col-span-2 sm:col-span-1">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-[#6B6B6B]">Selesai</span>
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#E8F8D0] border border-[#C7F36B]/60 flex items-center justify-center text-[#15803D]">
                        <x-ui.icon name="check-circle" class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-extrabold text-[#15803D] font-mono tracking-tight">
                    {{ number_format($stats['resolved_count']) }}
                </p>
                <p class="text-[11px] text-[#6B6B6B] mt-0.5">
                    Berhasil diperbaiki
                </p>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <form method="GET" action="{{ route('owner.complaints.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <!-- Search input -->
                    <div class="md:col-span-4">
                        <label for="q" class="block text-xs font-semibold text-[#121417] mb-1">Pencarian</label>
                        <input
                            type="text"
                            id="q"
                            name="q"
                            value="{{ $searchQuery }}"
                            placeholder="Nomor tiket, judul, nama penyewa..."
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                        />
                    </div>

                    <!-- Property filter -->
                    <div class="md:col-span-2">
                        <label for="property_id" class="block text-xs font-semibold text-[#121417] mb-1">Properti</label>
                        <select
                            id="property_id"
                            name="property_id"
                            class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        >
                            <option value="">Semua Properti</option>
                            @foreach($properties as $prop)
                                <option value="{{ $prop->id }}" @selected($selectedPropertyId === $prop->id)>
                                    {{ $prop->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status filter -->
                    <div class="md:col-span-2">
                        <label for="status" class="block text-xs font-semibold text-[#121417] mb-1">Status</label>
                        <select
                            id="status"
                            name="status"
                            class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        >
                            <option value="">Semua Status</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st->value }}" @selected($selectedStatus === $st->value)>
                                    {{ $st->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category filter -->
                    <div class="md:col-span-2">
                        <label for="category" class="block text-xs font-semibold text-[#121417] mb-1">Kategori</label>
                        <select
                            id="category"
                            name="category"
                            class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        >
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->value }}" @selected($selectedCategory === $cat->value)>
                                    {{ $cat->value }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Priority filter -->
                    <div class="md:col-span-2">
                        <label for="priority" class="block text-xs font-semibold text-[#121417] mb-1">Prioritas</label>
                        <select
                            id="priority"
                            name="priority"
                            class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        >
                            <option value="">Semua Prioritas</option>
                            @foreach($priorities as $prio)
                                <option value="{{ $prio->value }}" @selected($selectedPriority === $prio->value)>
                                    {{ $prio->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Submit & Reset Actions -->
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#DEDAD1]">
                    @if($searchQuery !== '' || $selectedPropertyId || $selectedStatus || $selectedCategory || $selectedPriority)
                        <a
                            href="{{ route('owner.complaints.index') }}"
                            class="px-4 py-2 rounded-xl bg-[#F4F2EC] border border-[#DEDAD1] font-semibold text-xs text-[#6B6B6B] hover:text-[#121417] hover:bg-[#EAE6DF] transition-colors cursor-pointer"
                        >
                            Reset Filter
                        </a>
                    @endif

                    <button
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-[#121417] text-white font-semibold text-xs hover:bg-[#2A2B2D] transition-colors cursor-pointer flex items-center gap-1.5"
                    >
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Complaints Table Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl shadow-[0_1px_3px_rgba(18,20,23,0.04)] overflow-hidden">
            @if($complaints->isEmpty())
                <div class="p-12 text-center">
                    <x-ui.empty-state
                        title="Tidak Ada Pengaduan"
                        description="{{ ($searchQuery !== '' || $selectedPropertyId || $selectedStatus || $selectedCategory || $selectedPriority) ? 'Tidak ada tiket pengaduan yang cocok dengan filter pencarian Anda.' : 'Belum ada keluhan atau tiket pengaduan fasilitas dari penyewa untuk properti Anda.' }}"
                        :actionUrl="($searchQuery !== '' || $selectedPropertyId || $selectedStatus || $selectedCategory || $selectedPriority) ? route('owner.complaints.index') : null"
                        actionText="Reset Filter"
                    />
                </div>
            @else
                <!-- Responsive Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-[#DEDAD1] bg-[#F9F8F5] text-[11px] font-bold text-[#6B6B6B] uppercase tracking-wider">
                                <th class="py-3 px-4">Tiket</th>
                                <th class="py-3 px-4">Penyewa & Lokasi</th>
                                <th class="py-3 px-4">Judul & Kategori</th>
                                <th class="py-3 px-4">Prioritas</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Penanganan</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DEDAD1]/70 text-xs text-[#121417]">
                            @foreach($complaints as $complaint)
                                @php
                                    $statusBadgeClass = match($complaint->status) {
                                        \App\Enums\ComplaintStatus::Baru->value => 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
                                        \App\Enums\ComplaintStatus::Diproses->value => 'bg-[#EFF6FF] text-[#1D4ED8] border-[#BFDBFE]',
                                        \App\Enums\ComplaintStatus::MenungguTanggapan->value => 'bg-[#FFEDD5] text-[#C2410C] border-[#FED7AA]',
                                        \App\Enums\ComplaintStatus::Selesai->value => 'bg-[#E8F8D0] text-[#15803D] border-[#C7F36B]/60',
                                        \App\Enums\ComplaintStatus::Ditutup->value => 'bg-[#F4F2EC] text-[#6B6B6B] border-[#DEDAD1]',
                                        default => 'bg-white text-[#121417] border-[#DEDAD1]',
                                    };

                                    $priorityBadgeClass = match($complaint->priority) {
                                        \App\Enums\ComplaintPriority::Darurat->value => 'bg-[#FEE2E2] text-[#B91C1C] border-[#FECACA]',
                                        \App\Enums\ComplaintPriority::Tinggi->value => 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
                                        \App\Enums\ComplaintPriority::Sedang->value => 'bg-[#EFF6FF] text-[#1D4ED8] border-[#BFDBFE]',
                                        \App\Enums\ComplaintPriority::Rendah->value => 'bg-[#F4F2EC] text-[#6B6B6B] border-[#DEDAD1]',
                                        default => 'bg-white text-[#121417] border-[#DEDAD1]',
                                    };
                                @endphp
                                <tr class="hover:bg-[#F9F8F5]/60 transition-colors">
                                    <!-- Ticket & Date -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-mono font-bold text-xs text-[#121417]">
                                            {{ $complaint->ticket_number }}
                                        </div>
                                        <div class="text-[11px] text-[#6B6B6B] font-normal mt-0.5">
                                            {{ $complaint->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </td>

                                    <!-- Tenant & Location -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold text-[#121417]">
                                            {{ $complaint->user?->name ?? 'Penyewa' }}
                                        </div>
                                        <div class="text-[11px] text-[#6B6B6B]">
                                            {{ $complaint->property?->name ?? '-' }} (Kamar {{ $complaint->room?->room_number ?? '-' }})
                                        </div>
                                    </td>

                                    <!-- Title & Category -->
                                    <td class="py-3.5 px-4 max-w-xs">
                                        <div class="font-semibold text-[#121417] truncate" title="{{ $complaint->title }}">
                                            {{ $complaint->title }}
                                        </div>
                                        <div class="text-[11px] text-[#6B6B6B] mt-0.5 flex items-center gap-1.5">
                                            <span>{{ $complaint->categoryLabel() }}</span>
                                            @if($complaint->photo_url)
                                                <span class="text-[#1D4ED8] font-medium" title="Memiliki lampiran foto">• Foto</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Priority -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $priorityBadgeClass }}">
                                            {{ $complaint->priorityLabel() }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $statusBadgeClass }}">
                                            ● {{ $complaint->statusLabel() }}
                                        </span>
                                    </td>

                                    <!-- Handling / Technician -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @if($complaint->handled_by)
                                            <div class="font-semibold text-[#121417] text-xs">
                                                {{ $complaint->handled_by }}
                                            </div>
                                            @if($complaint->estimated_completion)
                                                <div class="text-[11px] text-[#6B6B6B]">
                                                    Est: {{ $complaint->estimated_completion->format('d/m/Y') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-[11px] text-[#6B6B6B] italic">Belum ditugaskan</span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <x-ui.button
                                            href="{{ route('owner.complaints.show', $complaint) }}"
                                            variant="outline"
                                            size="sm"
                                            class="text-xs"
                                        >
                                            <span>Kelola</span>
                                            <span class="text-xs">›</span>
                                        </x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination footer -->
                @if($complaints->hasPages())
                    <div class="p-4 border-t border-[#DEDAD1] bg-[#F9F8F5]/50">
                        {{ $complaints->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-layouts.owner>
