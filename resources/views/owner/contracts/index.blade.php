<x-layouts.owner title="Manajemen Kontrak — HUNI" activeNav="contracts">
    <div class="space-y-6 max-w-7xl mx-auto">
        <!-- Header Banner Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1.5">
                    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#6B6B6B]">
                        <a href="{{ route('owner.dashboard') }}" class="hover:text-[#121417] transition-colors">Dashboard</a>
                        <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="font-medium text-[#121417]">Kontrak Sewa</span>
                    </nav>
                    <h1 class="text-2xl font-bold tracking-tight text-[#121417]">
                        Manajemen Kontrak Sewa
                    </h1>
                    <p class="text-xs text-[#6B6B6B]">
                        Kelola perjanjian sewa, periode aktif, berkas dokumen, dan status okupansi kamar secara terpusat.
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a
                        href="{{ route('owner.contracts.create') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-[0_1px_2px_rgba(18,20,23,0.08)] active:scale-[0.98] transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Buat Kontrak Baru</span>
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <x-ui.alert type="success" :dismissible="true">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if(session('error'))
            <x-ui.alert type="danger" :dismissible="true">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <x-ui.stat-card
                label="Total Kontrak"
                :value="$stats['total']"
                icon="file-text"
            />
            <x-ui.stat-card
                label="Kontrak Aktif"
                :value="$stats['aktif']"
                icon="check-circle"
            />
            <x-ui.stat-card
                label="Draft Disiapkan"
                :value="$stats['draft']"
                icon="edit"
            />
            <x-ui.stat-card
                label="Selesai / Batal"
                :value="$stats['selesai'] + $stats['dibatalkan']"
                icon="clock"
            />
        </div>

        <!-- Filter & Search Section -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <form method="GET" action="{{ route('owner.contracts.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                <!-- Search Input -->
                <div class="lg:col-span-5">
                    <label for="q" class="block text-xs font-semibold text-[#121417] mb-1.5">
                        Pencarian Kontrak
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            name="q"
                            id="q"
                            value="{{ $searchQuery }}"
                            placeholder="Cari no. kontrak, penyewa, kos, kamar..."
                            class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all"
                        />
                        <svg class="w-4 h-4 text-[#A5A096] absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="lg:col-span-3">
                    <label for="status" class="block text-xs font-semibold text-[#121417] mb-1.5">
                        Status Kontrak
                    </label>
                    <select
                        name="status"
                        id="status"
                        class="w-full px-3 py-2 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all cursor-pointer"
                    >
                        <option value="">Semua Status</option>
                        <option value="aktif" @selected($selectedStatus === 'aktif')>Aktif</option>
                        <option value="draft" @selected($selectedStatus === 'draft')>Draft</option>
                        <option value="selesai" @selected($selectedStatus === 'selesai')>Selesai</option>
                        <option value="dibatalkan" @selected($selectedStatus === 'dibatalkan')>Dibatalkan</option>
                    </select>
                </div>

                <!-- Property Filter -->
                <div class="lg:col-span-3">
                    <label for="property_id" class="block text-xs font-semibold text-[#121417] mb-1.5">
                        Properti
                    </label>
                    <select
                        name="property_id"
                        id="property_id"
                        class="w-full px-3 py-2 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all cursor-pointer"
                    >
                        <option value="">Semua Properti</option>
                        @foreach($properties as $prop)
                            <option value="{{ $prop->id }}" @selected((string)$selectedProperty === (string)$prop->id)>
                                {{ $prop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Button -->
                <div class="lg:col-span-1 flex gap-2">
                    <button
                        type="submit"
                        class="w-full py-2 px-3 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-xs active:scale-[0.98] transition-all cursor-pointer text-center"
                        title="Terapkan Filter"
                    >
                        Filter
                    </button>
                    @if($searchQuery || $selectedStatus || $selectedProperty)
                        <a
                            href="{{ route('owner.contracts.index') }}"
                            class="py-2 px-3 rounded-xl bg-white border border-[#DEDAD1] text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] text-xs font-semibold transition-colors cursor-pointer text-center"
                            title="Reset Filter"
                        >
                            ✕
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Contracts Table / Cards -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl shadow-[0_1px_3px_rgba(18,20,23,0.04)] overflow-hidden">
            @if($contracts->isEmpty())
                <div class="p-12 text-center space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-center justify-center text-[#6B6B6B] mx-auto">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="max-w-md mx-auto">
                        <h3 class="text-sm font-semibold text-[#121417]">Belum Ada Kontrak Sewa</h3>
                        <p class="text-xs text-[#6B6B6B] mt-1 leading-relaxed">
                            @if($searchQuery || $selectedStatus || $selectedProperty)
                                Tidak ada kontrak yang sesuai dengan kriteria filter Anda. Coba ubah kata kunci atau reset filter.
                            @else
                                Buat kontrak sewa pertama Anda untuk calon penghuni yang telah menerima undangan sewa kamar.
                            @endif
                        </p>
                    </div>
                    <div class="pt-2">
                        @if($searchQuery || $selectedStatus || $selectedProperty)
                            <a href="{{ route('owner.contracts.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#6B6B6B] hover:text-[#121417] cursor-pointer">
                                <span>Reset Filter</span>
                            </a>
                        @else
                            <a href="{{ route('owner.contracts.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs transition-colors cursor-pointer">
                                <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Buat Kontrak Sekarang</span>
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#F9F8F5] border-b border-[#DEDAD1] text-[10px] font-semibold uppercase tracking-wider text-[#6B6B6B]">
                                <th class="py-3 px-5">Nomor Kontrak</th>
                                <th class="py-3 px-5">Penyewa</th>
                                <th class="py-3 px-5">Kamar &amp; Kos</th>
                                <th class="py-3 px-5">Periode Sewa</th>
                                <th class="py-3 px-5">Harga Sewa</th>
                                <th class="py-3 px-5">Status</th>
                                <th class="py-3 px-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DEDAD1]/60 font-medium text-[#121417]">
                            @foreach($contracts as $contract)
                                <tr class="hover:bg-[#F9F8F5]/80 transition-colors">
                                    <!-- Contract Number & Date created -->
                                    <td class="py-3.5 px-5">
                                        <div class="font-mono font-bold text-[#121417]">
                                            {{ $contract->contract_number }}
                                        </div>
                                        <div class="text-[11px] text-[#A5A096] mt-0.5">
                                            Dibuat {{ $contract->created_at?->format('d/m/Y') }}
                                        </div>
                                        @if($contract->documents->isNotEmpty())
                                            <div class="inline-flex items-center gap-1 text-[10px] font-medium text-[#0369A1] bg-[#E0F2FE] px-1.5 py-0.5 rounded-md mt-1 border border-[#BAE6FD]">
                                                <span>📎 {{ $contract->documents->count() }} Dokumen</span>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Tenant Profile -->
                                    <td class="py-3.5 px-5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-[#121417] text-white font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($contract->tenant?->name ?? 'P', 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-[#121417] truncate">
                                                    {{ $contract->tenant?->name ?? 'Penyewa #' . $contract->user_id }}
                                                </div>
                                                <div class="text-[11px] text-[#6B6B6B] truncate">
                                                    {{ $contract->tenant?->phone ?? $contract->tenant?->email ?? '-' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Property & Room -->
                                    <td class="py-3.5 px-5">
                                        <div class="font-semibold text-[#121417]">
                                            Kamar {{ $contract->room?->room_number ?? '-' }}
                                        </div>
                                        <div class="text-[11px] text-[#6B6B6B]">
                                            {{ $contract->property?->name ?? '-' }}
                                        </div>
                                    </td>

                                    <!-- Period & Countdown -->
                                    <td class="py-3.5 px-5">
                                        <div class="text-xs font-semibold text-[#121417]">
                                            {{ $contract->start_date?->format('d M Y') }} – {{ $contract->end_date?->format('d M Y') }}
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-1">
                                            <span class="text-[11px] text-[#6B6B6B]">
                                                {{ $contract->duration_months }} Bulan
                                            </span>
                                            @if($contract->isActive())
                                                @php $daysLeft = $contract->daysRemaining(); @endphp
                                                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-md {{ $daysLeft <= 7 ? 'bg-[#FEE2E2] text-[#B91C1C] border border-[#FECACA]' : 'bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60' }}">
                                                    {{ $daysLeft > 0 ? "{$daysLeft} hari lagi" : ($daysLeft === 0 ? 'Hari ini' : 'Lewat tempo') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Price & Deposit -->
                                    <td class="py-3.5 px-5">
                                        <div class="font-mono font-bold tabular-nums text-[#121417]">
                                            Rp {{ number_format((float) $contract->monthly_rent, 0, ',', '.') }}
                                            <span class="text-[10px] font-sans font-normal text-[#6B6B6B]">/bln</span>
                                        </div>
                                        @if($contract->deposit_amount > 0)
                                            <div class="text-[11px] text-[#6B6B6B]">
                                                Deposit: Rp {{ number_format((float) $contract->deposit_amount, 0, ',', '.') }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-3.5 px-5">
                                        @if($contract->status === 'aktif')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                                                Aktif
                                            </span>
                                        @elseif($contract->status === 'draft')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#D97706]"></span>
                                                Draft
                                            </span>
                                        @elseif($contract->status === 'dibatalkan')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEE2E2] text-[#B91C1C] border border-[#FECACA]">
                                                Dibatalkan
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]">
                                                Selesai
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-3.5 px-5 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a
                                                href="{{ route('owner.contracts.show', $contract) }}"
                                                class="px-2.5 py-1.5 rounded-lg bg-white border border-[#DEDAD1] text-xs font-semibold text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                                                title="Lihat Detail Kontrak"
                                            >
                                                Detail
                                            </a>

                                            @if(!$contract->isCompleted() && !$contract->isCancelled())
                                                <a
                                                    href="{{ route('owner.contracts.edit', $contract) }}"
                                                    class="px-2.5 py-1.5 rounded-lg bg-[#F1EFEA] hover:bg-[#E5E2DA] border border-[#DEDAD1] text-xs font-semibold text-[#121417] transition-colors cursor-pointer"
                                                    title="Edit Kontrak"
                                                >
                                                    Edit
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination footer -->
                @if($contracts->hasPages())
                    <div class="p-4 border-t border-[#DEDAD1] bg-[#F9F8F5]">
                        {{ $contracts->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-layouts.owner>
