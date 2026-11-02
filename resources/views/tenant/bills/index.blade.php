<x-layouts.tenant title="Tagihan Saya — HUNI" activeNav="bills">
    <div class="space-y-6">
        <!-- Header Banner Card -->
        <div class="relative bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)] overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div>
                    <div class="flex items-center gap-2 text-xs font-medium text-[#5E6470] mb-2">
                        <a href="{{ route('tenant.dashboard') }}" class="hover:text-[#121417] transition-colors">Beranda</a>
                        <span>›</span>
                        <span class="text-[#121417] font-semibold">Tagihan Saya</span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-bold text-[#121417] tracking-tight">
                        Tagihan Saya
                    </h1>
                    <p class="text-sm text-[#5E6470] mt-1">
                        Pantau seluruh riwayat tagihan kos, konfirmasi pembayaran, dan sisa kewajibanmu.
                    </p>
                </div>

                <!-- Status Badge Banner -->
                <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] text-xs font-medium text-[#121417]">
                    <x-ui.icon name="credit-card" class="w-4 h-4 text-[#15803D]" />
                    <span>{{ $stats['unpaid_count'] > 0 ? $stats['unpaid_count'] . ' tagihan belum terselesaikan' : 'Semua tagihanmu lunas' }}</span>
                </div>
            </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Sisa Tagihan (Outstanding) -->
            <div class="bg-white border border-[#DEDAD1] rounded-xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#5E6470]">Total Sisa Tagihan</span>
                    <span class="w-8 h-8 rounded-lg bg-[#FEF3C7] border border-[#FDE68A] flex items-center justify-center text-amber-700">
                        <x-ui.icon name="clock" class="w-4 h-4" />
                    </span>
                </div>
                <p class="text-2xl font-bold font-mono {{ $stats['unpaid_amount'] > 0 ? 'text-[#B91C1C]' : 'text-[#121417]' }}">
                    Rp {{ number_format($stats['unpaid_amount'], 0, ',', '.') }}
                </p>
                <p class="text-xs text-[#5E6470] mt-1 font-mono">
                    {{ $stats['unpaid_count'] }} tagihan aktif belum lunas
                </p>
            </div>

            <!-- Total Terbayar -->
            <div class="bg-white border border-[#DEDAD1] rounded-xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#5E6470]">Total Terbayar</span>
                    <span class="w-8 h-8 rounded-lg bg-[#E8F5E9] border border-[#C8E6C9] flex items-center justify-center text-[#15803D]">
                        <x-ui.icon name="check-circle" class="w-4 h-4" />
                    </span>
                </div>
                <p class="text-2xl font-bold font-mono text-[#121417]">
                    Rp {{ number_format($stats['paid_total'], 0, ',', '.') }}
                </p>
                <p class="text-xs text-[#5E6470] mt-1">
                    Telah diverifikasi pemilik kos
                </p>
            </div>

            <!-- Total Invoice -->
            <div class="bg-white border border-[#DEDAD1] rounded-xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#5E6470]">Jumlah Tagihan</span>
                    <span class="w-8 h-8 rounded-lg bg-[#EFF6FF] border border-[#BFDBFE] flex items-center justify-center text-blue-700">
                        <x-ui.icon name="document-text" class="w-4 h-4" />
                    </span>
                </div>
                <p class="text-2xl font-bold font-mono text-[#121417]">
                    {{ $stats['total'] }}
                </p>
                <p class="text-xs text-[#5E6470] mt-1">
                    Seluruh riwayat penagihan
                </p>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="bg-white border border-[#DEDAD1] rounded-xl p-4 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <form method="GET" action="{{ route('tenant.bills.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <!-- Search Box -->
                <div class="sm:col-span-5">
                    <label for="search_q" class="sr-only">Cari No. Tagihan / Nama Kos</label>
                    <div class="relative">
                        <x-ui.icon name="magnifying-glass" class="w-4 h-4 text-[#5E6470] absolute left-3 top-3 pointer-events-none" />
                        <input
                            type="text"
                            name="q"
                            id="search_q"
                            value="{{ $searchQuery }}"
                            placeholder="Cari no. tagihan atau nama kos..."
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
                        class="w-full px-3 py-2 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-none focus:bg-white focus:border-[#121417] transition-all"
                    >
                        <option value="">Semua Status</option>
                        @foreach(\App\Enums\BillStatus::cases() as $st)
                            <option value="{{ $st->value }}" @selected($selectedStatus === $st->value)>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Type Filter -->
                <div class="sm:col-span-2">
                    <label for="filter_type" class="sr-only">Filter Tipe</label>
                    <select
                        name="type"
                        id="filter_type"
                        class="w-full px-3 py-2 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-none focus:bg-white focus:border-[#121417] transition-all"
                    >
                        <option value="">Semua Tipe</option>
                        @foreach(\App\Enums\BillType::cases() as $tp)
                            <option value="{{ $tp->value }}" @selected($selectedType === $tp->value)>
                                {{ $tp->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="sm:col-span-2 flex items-center gap-2">
                    <button
                        type="submit"
                        class="flex-1 py-2 px-3 rounded-lg bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs transition-all cursor-pointer text-center shadow-[0_1px_2px_rgba(18,20,23,0.08)]"
                    >
                        Filter
                    </button>
                    @if($searchQuery || $selectedStatus || $selectedType)
                        <a
                            href="{{ route('tenant.bills.index') }}"
                            class="py-2 px-3 rounded-lg bg-white border border-[#DEDAD1] hover:bg-[#F4F1EA] font-semibold text-xs text-[#5E6470] hover:text-[#121417] transition-all text-center"
                            title="Reset Filter"
                        >
                            <x-ui.icon name="x-mark" class="w-3.5 h-3.5" />
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Invoice Cards Grid / List -->
        @if($bills->isEmpty())
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-12 text-center shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-3">
                <div class="w-12 h-12 rounded-xl bg-[#F4F1EA] text-[#5E6470] flex items-center justify-center mx-auto">
                    <x-ui.icon name="document-text" class="w-6 h-6" />
                </div>
                <h3 class="text-sm font-semibold text-[#121417]">Tidak Ada Tagihan Ditemukan</h3>
                <p class="text-xs text-[#5E6470] max-w-sm mx-auto">
                    @if($searchQuery || $selectedStatus || $selectedType)
                        Tidak ada tagihan yang sesuai dengan filter pencarianmu. Coba ubah kata kunci atau reset filter.
                    @else
                        Saat ini belum ada tagihan yang diterbitkan untuk akunmu.
                    @endif
                </p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($bills as $bill)
                    @php
                        $typeBadgeVariant = match($bill->type) {
                            \App\Enums\BillType::Sewa->value => 'blue',
                            \App\Enums\BillType::Deposit->value => 'amber',
                            \App\Enums\BillType::Tambahan->value => 'purple',
                            default => 'neutral',
                        };

                        $statusBadgeVariant = match($bill->status) {
                            \App\Enums\BillStatus::Lunas->value => 'emerald',
                            \App\Enums\BillStatus::MenungguPembayaran->value => 'amber',
                            \App\Enums\BillStatus::Sebagian->value => 'amber',
                            \App\Enums\BillStatus::JatuhTempo->value => 'rose',
                            \App\Enums\BillStatus::Dibatalkan->value => 'neutral',
                            default => 'neutral',
                        };

                        $hasPending = $bill->hasPendingPayment();
                    @endphp

                    <div class="bg-white border border-[#DEDAD1] rounded-xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] hover:border-[#CDC8BD] transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <!-- Left: Bill & Property Metadata -->
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-bold font-mono text-base text-[#121417]">
                                        {{ $bill->bill_number }}
                                    </span>
                                    <x-ui.badge :variant="$typeBadgeVariant" size="sm">
                                        {{ $bill->typeEnum()?->label() ?? ucfirst($bill->type) }}
                                    </x-ui.badge>
                                    <x-ui.badge :variant="$statusBadgeVariant" size="sm">
                                        {{ $bill->statusEnum()?->label() ?? ucfirst($bill->status) }}
                                    </x-ui.badge>
                                    @if($hasPending)
                                        <x-ui.badge variant="blue" size="sm">
                                            Menunggu Verifikasi
                                        </x-ui.badge>
                                    @endif
                                </div>

                                <p class="text-xs text-[#5E6470]">
                                    <span class="text-[#121417] font-semibold">{{ $bill->room?->property?->name ?? $bill->contract?->property?->name ?? 'Kos' }}</span>
                                    — Kamar <span class="text-[#121417] font-mono font-semibold">{{ $bill->room?->room_number ?? '-' }}</span>
                                    @if($bill->billing_period)
                                        • Periode: <span class="text-[#121417] font-medium">{{ $bill->formattedPeriod() }}</span>
                                    @endif
                                </p>

                                <div class="flex flex-wrap items-center gap-4 text-xs text-[#5E6470] font-mono">
                                    <span>Terbit: <strong class="text-[#121417] font-semibold">{{ $bill->issue_date?->format('d/m/Y') ?? '-' }}</strong></span>
                                    <span>Jatuh Tempo: <strong class="{{ $bill->isOverdue() ? 'text-[#B91C1C] font-semibold' : 'text-[#121417] font-semibold' }}">{{ $bill->due_date?->format('d/m/Y') ?? '-' }}</strong></span>
                                </div>
                            </div>

                            <!-- Right: Amounts & Detail Action -->
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6 pt-3 md:pt-0 border-t md:border-t-0 border-[#DEDAD1]">
                                <div class="text-left sm:text-right">
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-[#5E6470] block">Sisa Kewajiban</span>
                                    <p class="text-xl font-bold font-mono {{ $bill->outstandingAmount() > 0 ? 'text-[#B91C1C]' : 'text-[#121417]' }}">
                                        Rp {{ number_format($bill->outstandingAmount(), 0, ',', '.') }}
                                    </p>
                                    <span class="text-[11px] text-[#5E6470] font-mono block">
                                        Total: Rp {{ number_format((float) $bill->total_amount, 0, ',', '.') }}
                                    </span>
                                </div>

                                <a
                                    href="{{ route('tenant.bills.show', $bill) }}"
                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold transition-all cursor-pointer shrink-0 {{ $bill->outstandingAmount() > 0 && !$hasPending ? 'bg-[#C7F36B] hover:bg-[#bbf055] text-[#121417] shadow-[0_1px_2px_rgba(18,20,23,0.08)]' : 'bg-white hover:bg-[#F4F1EA] text-[#121417] border border-[#DEDAD1]' }}"
                                >
                                    @if($bill->outstandingAmount() > 0 && !$hasPending && !$bill->isCancelled())
                                        <x-ui.icon name="credit-card" class="w-3.5 h-3.5 text-[#121417]" />
                                        <span>Bayar Sekarang</span>
                                    @else
                                        <x-ui.icon name="document-text" class="w-3.5 h-3.5 text-[#5E6470]" />
                                        <span>Lihat Detail</span>
                                    @endif
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Pagination -->
                <div class="pt-4">
                    {{ $bills->links() }}
                </div>
            </div>
        @endif
    </div>
</x-layouts.tenant>
