<x-layouts.owner title="Invoice & Pembayaran — HUNI" activeNav="invoices">
    <div class="space-y-6">
        <!-- Header Banner Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-2">
                        <a href="{{ route('owner.dashboard') }}" class="hover:text-[#121417] transition-colors">Dashboard</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <span class="text-[#121417] font-semibold">Invoice & Pembayaran</span>
                    </nav>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#C7F36B]/20 border border-[#C7F36B]/60 flex items-center justify-center shrink-0">
                            <x-ui.icon name="receipt" class="w-5 h-5 text-[#15803D]" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">
                                Invoice & Tagihan
                            </h1>
                            <p class="text-xs sm:text-sm text-[#6B6B6B] mt-0.5">
                                Pantau seluruh tagihan sewa, deposit jaminan, biaya tambahan, serta status pembayaran penghuni.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <x-ui.button href="{{ route('owner.bills.create') }}" variant="primary" class="gap-2">
                        <x-ui.icon name="plus" class="w-4 h-4" />
                        <span>Buat Tagihan Manual</span>
                    </x-ui.button>
                </div>
            </div>
        </div>

        @if(session('success'))
            <x-ui.alert type="success" :message="session('success')" dismissible="true" />
        @endif

        @if(session('error'))
            <x-ui.alert type="danger" :message="session('error')" dismissible="true" />
        @endif

        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Total Tagihan</span>
                    <div class="w-8 h-8 rounded-lg bg-[#F4F2EC] border border-[#DEDAD1] flex items-center justify-center text-[#121417]">
                        <x-ui.icon name="receipt" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold text-[#121417] tracking-tight font-mono">{{ number_format($stats['total']) }}</span>
                    <span class="text-xs text-[#6B6B6B]">invoice</span>
                </div>
            </div>

            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Belum Lunas</span>
                    <div class="w-8 h-8 rounded-lg bg-[#FEF3C7] border border-[#FDE68A] flex items-center justify-center text-[#B45309]">
                        <x-ui.icon name="clock" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold text-[#B45309] tracking-tight font-mono">{{ number_format($stats['unpaid_count']) }}</span>
                    <span class="text-xs text-[#6B6B6B]">menunggu</span>
                </div>
            </div>

            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Sisa Piutang</span>
                    <div class="w-8 h-8 rounded-lg bg-[#FEE2E2] border border-[#FECACA] flex items-center justify-center text-[#B91C1C]">
                        <x-ui.icon name="wallet" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-xl sm:text-2xl font-extrabold text-[#B91C1C] tracking-tight font-mono truncate block">
                        Rp {{ number_format($stats['unpaid_amount'], 0, ',', '.') }}
                    </span>
                    <span class="text-[11px] text-[#6B6B6B]">akumulasi belum dibayar</span>
                </div>
            </div>

            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Terbayar Bulan Ini</span>
                    <div class="w-8 h-8 rounded-lg bg-[#E8F8D0] border border-[#C7F36B]/60 flex items-center justify-center text-[#15803D]">
                        <x-ui.icon name="credit-card" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-xl sm:text-2xl font-extrabold text-[#15803D] tracking-tight font-mono truncate block">
                        Rp {{ number_format($stats['paid_this_month'], 0, ',', '.') }}
                    </span>
                    <span class="text-[11px] text-[#6B6B6B]">arus kas masuk</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <form method="GET" action="{{ route('owner.bills.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5 items-end">
                <!-- Search Input -->
                <div class="sm:col-span-2 lg:col-span-3 space-y-1.5">
                    <label for="q" class="text-xs font-semibold text-[#121417] block">Cari Tagihan</label>
                    <input
                        type="text"
                        name="q"
                        id="q"
                        value="{{ $searchQuery }}"
                        placeholder="No tagihan, nama, kamar..."
                        class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                    />
                </div>

                <!-- Property Filter -->
                <div class="lg:col-span-2 space-y-1.5">
                    <label for="property_id" class="text-xs font-semibold text-[#121417] block">Properti</label>
                    <select
                        name="property_id"
                        id="property_id"
                        class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                    >
                        <option value="">Semua Properti</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" {{ (string)$selectedProperty === (string)$property->id ? 'selected' : '' }}>
                                {{ $property->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Type Filter -->
                <div class="lg:col-span-2 space-y-1.5">
                    <label for="type" class="text-xs font-semibold text-[#121417] block">Jenis Tagihan</label>
                    <select
                        name="type"
                        id="type"
                        class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                    >
                        <option value="">Semua Jenis</option>
                        <option value="sewa" {{ $selectedType === 'sewa' ? 'selected' : '' }}>Sewa Kamar</option>
                        <option value="deposit" {{ $selectedType === 'deposit' ? 'selected' : '' }}>Deposit Jaminan</option>
                        <option value="tambahan" {{ $selectedType === 'tambahan' ? 'selected' : '' }}>Biaya Tambahan</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="lg:col-span-2 space-y-1.5">
                    <label for="status" class="text-xs font-semibold text-[#121417] block">Status Pembayaran</label>
                    <select
                        name="status"
                        id="status"
                        class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                    >
                        <option value="">Semua Status</option>
                        <option value="menunggu_pembayaran" {{ $selectedStatus === 'menunggu_pembayaran' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                        <option value="sebagian" {{ $selectedStatus === 'sebagian' ? 'selected' : '' }}>Sebagian</option>
                        <option value="lunas" {{ $selectedStatus === 'lunas' ? 'selected' : '' }}>Lunas</option>
                        <option value="jatuh_tempo" {{ $selectedStatus === 'jatuh_tempo' ? 'selected' : '' }}>Jatuh Tempo</option>
                        <option value="dibatalkan" {{ $selectedStatus === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>

                <!-- Billing Period Filter -->
                <div class="lg:col-span-2 space-y-1.5">
                    <label for="billing_period" class="text-xs font-semibold text-[#121417] block">Periode (YYYY-MM)</label>
                    <input
                        type="text"
                        name="billing_period"
                        id="billing_period"
                        value="{{ $selectedPeriod }}"
                        placeholder="contoh: 2026-10"
                        class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                    />
                </div>

                <!-- Action Buttons -->
                <div class="lg:col-span-1 flex items-center gap-1.5">
                    <button
                        type="submit"
                        class="flex-1 py-2.5 px-3 rounded-xl bg-[#121417] text-white font-semibold text-xs hover:bg-[#2A2B2D] transition-colors cursor-pointer text-center"
                        title="Terapkan Filter"
                    >
                        Filter
                    </button>
                    @if($searchQuery || $selectedProperty || $selectedType || $selectedStatus || $selectedPeriod)
                        <a
                            href="{{ route('owner.bills.index') }}"
                            class="py-2.5 px-3 rounded-xl bg-[#F4F2EC] border border-[#DEDAD1] font-semibold text-xs text-[#6B6B6B] hover:text-[#121417] hover:bg-[#EAE6DF] transition-colors text-center"
                            title="Reset Filter"
                        >
                            ✕
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Invoices Table Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl shadow-[0_1px_3px_rgba(18,20,23,0.04)] overflow-hidden">
            <div class="px-6 py-4.5 border-b border-[#DEDAD1] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-[#121417]">Daftar Tagihan & Invoice</h2>
                    <p class="text-xs text-[#6B6B6B] mt-0.5">Menampilkan seluruh tagihan resmi untuk penghuni kos Anda.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#DEDAD1] bg-[#F9F8F5] text-[11px] font-bold text-[#6B6B6B] uppercase tracking-wider">
                            <th class="py-3 px-5">No. Tagihan</th>
                            <th class="py-3 px-5">Penghuni & Kamar</th>
                            <th class="py-3 px-5">Jenis & Periode</th>
                            <th class="py-3 px-5">Jatuh Tempo</th>
                            <th class="py-3 px-5 text-right">Total Tagihan</th>
                            <th class="py-3 px-5 text-right">Terbayar</th>
                            <th class="py-3 px-5 text-center">Status</th>
                            <th class="py-3 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DEDAD1]/70 text-xs text-[#121417]">
                        @forelse($bills as $bill)
                            @php
                                $property = $bill->contract?->property ?? $bill->room?->property;
                                $room = $bill->room ?? $bill->contract?->room;
                                $tenant = $bill->user;
                            @endphp
                            <tr class="hover:bg-[#F9F8F5]/60 transition-colors">
                                <td class="py-3.5 px-5">
                                    <span class="font-bold font-mono text-[#121417]">{{ $bill->bill_number }}</span>
                                    <p class="text-[11px] text-[#6B6B6B] font-normal mt-0.5">Terbit: {{ $bill->issue_date?->format('d/m/Y') }}</p>
                                </td>

                                <td class="py-3.5 px-5">
                                    <div class="font-semibold text-[#121417]">{{ $tenant?->name ?? 'Penghuni' }}</div>
                                    <p class="text-[11px] text-[#6B6B6B] mt-0.5">
                                        {{ $property?->name ?? '-' }} • Kamar {{ $room?->room_number ?? '-' }}
                                    </p>
                                </td>

                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-1.5">
                                        @if($bill->type === 'sewa')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#EFF6FF] border border-[#BFDBFE] text-[#1D4ED8]">Sewa</span>
                                        @elseif($bill->type === 'deposit')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#FEF3C7] border border-[#FDE68A] text-[#B45309]">Deposit</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#F3E8FF] border border-[#E9D5FF] text-[#7E22CE]">Tambahan</span>
                                        @endif
                                        <span class="text-[11px] font-medium text-[#6B6B6B]">{{ $bill->formattedPeriod() }}</span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-5">
                                    <span class="{{ $bill->isOverdue() ? 'text-[#B91C1C] font-semibold' : 'text-[#121417]' }}">
                                        {{ $bill->due_date?->format('d/m/Y') }}
                                    </span>
                                    @if($bill->isOverdue())
                                        <span class="block text-[10px] font-semibold text-[#B91C1C]">Terlambat</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-5 text-right font-bold font-mono text-[#121417]">
                                    Rp {{ number_format((float)$bill->total_amount, 0, ',', '.') }}
                                </td>

                                <td class="py-3.5 px-5 text-right font-medium font-mono text-[#15803D]">
                                    Rp {{ number_format((float)$bill->paid_amount, 0, ',', '.') }}
                                </td>

                                <td class="py-3.5 px-5 text-center">
                                    @if($bill->status === 'lunas')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8D0] border border-[#C7F36B]/60 text-[#15803D]">Lunas</span>
                                    @elseif($bill->status === 'sebagian')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] border border-[#FDE68A] text-[#B45309]">Sebagian</span>
                                    @elseif($bill->status === 'dibatalkan')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#F4F2EC] border border-[#DEDAD1] text-[#6B6B6B]">Dibatalkan</span>
                                    @elseif($bill->isOverdue() || $bill->status === 'jatuh_tempo')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEE2E2] border border-[#FECACA] text-[#B91C1C]">Jatuh Tempo</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] border border-[#FDE68A] text-[#B45309]">Menunggu</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-5 text-right">
                                    <x-ui.button
                                        href="{{ route('owner.bills.show', $bill) }}"
                                        variant="outline"
                                        size="sm"
                                        class="text-xs"
                                    >
                                        <span>Rincian</span>
                                        <span class="text-xs">›</span>
                                    </x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 px-6 text-center">
                                    <x-ui.empty-state
                                        title="Tidak Ada Tagihan Ditemukan"
                                        description="Belum ada data tagihan yang sesuai dengan kriteria filter atau pencarian Anda."
                                        actionText="Buat Tagihan Baru"
                                        :actionUrl="route('owner.bills.create')"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if($bills->hasPages())
                <div class="p-4 border-t border-[#DEDAD1] bg-[#F9F8F5]/50">
                    {{ $bills->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.owner>
