<x-layouts.owner title="Laporan & Analitik — HUNI" activeNav="analytics">
    <!-- Page Header & Welcome Banner -->
    <div class="space-y-4">
        <!-- Welcome Banner / Hero Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-3" aria-label="Breadcrumb">
                <a href="{{ route('owner.dashboard') }}" class="hover:text-[#121417] transition-colors">Dashboard</a>
                <span class="text-[#DEDAD1]">/</span>
                <span class="text-[#121417] font-semibold">Laporan & Analitik</span>
                <span class="text-[#DEDAD1]">/</span>
                <span class="text-[#6B6B6B]">{{ $periodLabel }}</span>
            </nav>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-[#E8F8D0] border border-[#C7F36B]/60 text-xs font-semibold text-[#15803D] mb-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                        <span>Analytics Engine • {{ $periodLabel }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#C7F36B]/20 border border-[#C7F36B]/60 flex items-center justify-center shrink-0">
                            <x-ui.icon name="bar-chart" class="w-5 h-5 text-[#15803D]" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">
                                Laporan & Analitik
                            </h1>
                            <p class="text-xs sm:text-sm text-[#6B6B6B] mt-0.5">
                                Pantau performa finansial, okupansi hunian, dan statistik keluhan kos Anda secara transparan dengan data kas terverifikasi.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <!-- Export & Print Dropdown Menu -->
                    <div x-data="{ open: false }" class="relative">
                        <button
                            type="button"
                            @click="open = !open"
                            class="px-4 py-2 rounded-xl bg-[#121417] text-white font-semibold text-xs hover:bg-[#2A2B2D] transition-colors flex items-center gap-2 cursor-pointer shadow-[0_1px_2px_rgba(18,20,23,0.08)]"
                        >
                            <x-ui.icon name="download" class="w-4 h-4 text-[#C7F36B]" />
                            <span>Export & Cetak</span>
                            <span class="text-[10px] bg-[#C7F36B] text-[#121417] px-1.5 py-0.2 rounded font-bold">CSV & PDF</span>
                        </button>

                        <!-- Dropdown Menu -->
                        <div
                            x-show="open"
                            @click.outside="open = false"
                            x-cloak
                            x-transition
                            class="absolute right-0 mt-2 w-64 bg-white border border-[#DEDAD1] rounded-2xl p-2.5 shadow-xl z-30 text-xs space-y-1.5"
                        >
                            <div class="px-2 py-1.5 border-b border-[#DEDAD1]">
                                <p class="font-bold text-[#121417]">Ekspor Laporan Aktif</p>
                                <p class="text-[11px] text-[#6B6B6B]">{{ $periodLabel }}</p>
                            </div>

                            <div class="space-y-1">
                                <a
                                    href="{{ route('owner.reports.print', ['report_type' => 'revenue', 'property_id' => $selectedPropertyId, 'period_type' => $periodType, 'period' => $periodKey]) }}"
                                    class="w-full px-3 py-2 rounded-xl bg-[#F9F8F5] hover:bg-[#EAE6DF] font-medium text-[#121417] flex items-center gap-2 border border-[#DEDAD1] transition-colors"
                                >
                                    <x-ui.icon name="printer" class="w-4 h-4 text-[#6B6B6B]" />
                                    <span>Halaman Cetak / PDF</span>
                                </a>

                                <a
                                    href="{{ route('owner.reports.export', ['report_type' => 'revenue', 'property_id' => $selectedPropertyId, 'period_type' => $periodType, 'period' => $periodKey]) }}"
                                    class="w-full px-3 py-2 rounded-xl bg-[#F9F8F5] hover:bg-[#EAE6DF] font-medium text-[#121417] flex items-center gap-2 border border-[#DEDAD1] transition-colors"
                                >
                                    <x-ui.icon name="download" class="w-4 h-4 text-[#6B6B6B]" />
                                    <span>Unduh File CSV</span>
                                </a>
                            </div>

                            <div class="pt-1.5 border-t border-[#DEDAD1] px-2 text-[10px] text-[#6B6B6B]">
                                Format CSV aman formula injection & kompatibel dengan Excel/Sheets.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Top KPI Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: Total Pendapatan Kas -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-[#6B6B6B]">Total Pendapatan</p>
                    <p class="text-2xl font-extrabold text-[#121417] mt-1 font-mono tracking-tight">
                        Rp {{ number_format($kpis['totalRevenue'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="w-8 h-8 rounded-lg bg-[#E8F8D0] border border-[#C7F36B]/60 flex items-center justify-center text-[#15803D]">
                    <x-ui.icon name="wallet" class="w-4 h-4" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#DEDAD1] flex items-center justify-between text-xs">
                @if($kpis['revenueGrowth'] !== null)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $kpis['revenueGrowth'] >= 0 ? 'bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60' : 'bg-[#FEE2E2] text-[#B91C1C] border border-[#FECACA]' }}">
                        {{ $kpis['revenueGrowth'] >= 0 ? '↑ +'.$kpis['revenueGrowth'].'%' : '↓ '.$kpis['revenueGrowth'].'%' }}
                    </span>
                    <span class="text-[11px] text-[#6B6B6B]">dari periode lalu</span>
                @else
                    <span class="text-[11px] text-[#6B6B6B]">Kas terverifikasi</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#F4F2EC] text-[#6B6B6B] border border-[#DEDAD1]">
                        {{ $periodLabel }}
                    </span>
                @endif
            </div>
        </div>

        <!-- KPI 2: Invoice Dibayar -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-[#6B6B6B]">Invoice Dibayar</p>
                    <p class="text-2xl font-extrabold text-[#121417] mt-1 font-mono tracking-tight">
                        {{ $kpis['paidInvoicesCount'] }}
                        <span class="text-sm font-normal text-[#6B6B6B]">/ {{ $kpis['totalInvoicesCount'] }}</span>
                    </p>
                </div>
                <div class="w-8 h-8 rounded-lg bg-[#F4F2EC] border border-[#DEDAD1] flex items-center justify-center text-[#121417]">
                    <x-ui.icon name="receipt" class="w-4 h-4" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#DEDAD1] flex items-center justify-between text-xs">
                <span class="text-xs text-[#6B6B6B]">
                    dari {{ $kpis['totalInvoicesCount'] }} tagihan
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8D0] border border-[#C7F36B]/60 text-[#15803D]">
                    {{ $kpis['paidInvoicesRate'] }}% Lunas
                </span>
            </div>
        </div>

        <!-- KPI 3: Tingkat Hunian -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-[#6B6B6B]">Tingkat Hunian</p>
                    <p class="text-2xl font-extrabold text-[#121417] mt-1 font-mono tracking-tight">
                        {{ $kpis['occupiedRooms'] }} <span class="text-sm font-normal text-[#6B6B6B]">/ {{ $kpis['totalRooms'] }} Kamar</span>
                    </p>
                </div>
                <div class="w-8 h-8 rounded-lg bg-[#EFF6FF] border border-[#BFDBFE] flex items-center justify-center text-[#1D4ED8]">
                    <x-ui.icon name="building" class="w-4 h-4" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#DEDAD1] space-y-1.5">
                <div class="flex items-center justify-between text-xs font-medium">
                    <span class="text-[#15803D] font-semibold">{{ $kpis['occupancyRate'] }}% Terisi</span>
                    <span class="text-[#6B6B6B]">{{ $kpis['vacantRooms'] }} Kosong</span>
                </div>
                <div class="w-full bg-[#F4F2EC] h-1.5 rounded-full overflow-hidden">
                    <div class="bg-[#15803D] h-full rounded-full" style="width: {{ $kpis['occupancyRate'] }}%;"></div>
                </div>
            </div>
        </div>

        <!-- KPI 4: Tunggakan Jatuh Tempo -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-[#6B6B6B]">Tunggakan Terlambat</p>
                    <p class="text-2xl font-extrabold text-[#B91C1C] mt-1 font-mono tracking-tight">
                        {{ $kpis['overdueTenantsCount'] }} <span class="text-sm font-normal text-[#6B6B6B]">Penghuni</span>
                    </p>
                </div>
                <div class="w-8 h-8 rounded-lg bg-[#FEE2E2] border border-[#FECACA] flex items-center justify-center text-[#B91C1C]">
                    <x-ui.icon name="clock" class="w-4 h-4" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#DEDAD1] flex items-center justify-between text-xs">
                <span class="text-xs text-[#6B6B6B]">Total nilai:</span>
                <span class="text-xs font-bold font-mono text-[#B91C1C]">
                    Rp {{ number_format($kpis['totalOverdueAmount'], 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Main Content Layout (Left 8 Cols: Charts & Widgets, Right 4 Cols: Filter & Downloads) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left Section (8 Cols on LG) -->
        <div class="lg:col-span-8 space-y-6">
            <!-- Row 1 Charts: Tren Pendapatan & Status Pembayaran -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                <!-- Chart 1: Tren Pendapatan 12 Bulan (7 Cols) -->
                <div class="md:col-span-7 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#DEDAD1]">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-[#E8F8D0] border border-[#C7F36B]/60 flex items-center justify-center text-[#15803D]">
                                    <x-ui.icon name="bar-chart" class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-[#121417]">Tren Pendapatan</h2>
                                    <p class="text-[11px] text-[#6B6B6B]">Penerimaan 12 bulan terakhir</p>
                                </div>
                            </div>
                            <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-[#F4F2EC] border border-[#DEDAD1] text-[#6B6B6B]">
                                12 Bulan
                            </span>
                        </div>

                        <!-- ApexCharts Container -->
                        <div
                            x-data="chartComponent(@js($charts['revenueTrend']), 'bar')"
                            class="w-full"
                        >
                            <div x-ref="chartContainer" class="w-full min-h-[260px]"></div>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-[#DEDAD1] flex flex-wrap items-center justify-between gap-2 text-[11px] font-medium text-[#6B6B6B]">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#15803D]"></span>Tepat Waktu</span>
                            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#F59E0B]"></span>Terlambat</span>
                            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#EF4444]"></span>Tunggakan</span>
                        </div>
                    </div>
                </div>

                <!-- Chart 3: Status Pembayaran (5 Cols) -->
                <div class="md:col-span-5 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#DEDAD1]">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-[#FEF3C7] border border-[#FDE68A] flex items-center justify-center text-[#B45309]">
                                    <x-ui.icon name="receipt" class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-[#121417]">Status Tagihan</h2>
                                    <p class="text-[11px] text-[#6B6B6B]">Periode {{ $periodLabel }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Donut Container -->
                        <div
                            x-data="donutChartComponent(@js($charts['paymentStatus']), 'Invoice')"
                            class="w-full flex items-center justify-center"
                        >
                            <div x-ref="chartContainer" class="w-full min-h-[240px]"></div>
                        </div>
                    </div>

                    <!-- Legend List -->
                    <div class="mt-3 pt-3 border-t border-[#DEDAD1] space-y-1.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#15803D]"></span>Dibayar</span>
                            <span class="font-bold font-mono text-[#121417]">{{ $charts['paymentStatus']['counts']['paid'] }} ({{ $charts['paymentStatus']['percentages']['paid'] }}%)</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#F59E0B]"></span>Terlambat</span>
                            <span class="font-bold font-mono text-[#B45309]">{{ $charts['paymentStatus']['counts']['late'] }} ({{ $charts['paymentStatus']['percentages']['late'] }}%)</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#EF4444]"></span>Belum Dibayar</span>
                            <span class="font-bold font-mono text-[#B91C1C]">{{ $charts['paymentStatus']['counts']['unpaid'] }} ({{ $charts['paymentStatus']['percentages']['unpaid'] }}%)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2 Charts: Tingkat Hunian & Komposisi Pendapatan -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                <!-- Chart 2: Tingkat Hunian 12 Bulan (7 Cols) -->
                <div class="md:col-span-7 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#DEDAD1]">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-[#EFF6FF] border border-[#BFDBFE] flex items-center justify-center text-[#1D4ED8]">
                                    <x-ui.icon name="building" class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-[#121417]">Tingkat Hunian</h2>
                                    <p class="text-[11px] text-[#6B6B6B]">Okupansi kamar 12 bulan terakhir</p>
                                </div>
                            </div>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#E8F8D0] border border-[#C7F36B]/60 text-[#15803D]">
                                {{ $kpis['occupancyRate'] }}%
                            </span>
                        </div>

                        <!-- Area Chart Container -->
                        <div
                            x-data="occupancyChartComponent(@js($charts['occupancyTrend']))"
                            class="w-full"
                        >
                            <div x-ref="chartContainer" class="w-full min-h-[250px]"></div>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-[#DEDAD1] flex items-center justify-between text-xs text-[#6B6B6B]">
                        <span>Kapasitas total kamar:</span>
                        <span class="text-[#121417] font-bold font-mono">{{ $kpis['totalRooms'] }} Kamar</span>
                    </div>
                </div>

                <!-- Chart 4: Komposisi Pendapatan (5 Cols) -->
                <div class="md:col-span-5 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#DEDAD1]">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-[#F3E8FF] border border-[#E9D5FF] flex items-center justify-center text-[#7E22CE]">
                                    <x-ui.icon name="credit-card" class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-[#121417]">Komposisi Pendapatan</h2>
                                    <p class="text-[11px] text-[#6B6B6B]">Berdasarkan jenis tagihan</p>
                                </div>
                            </div>
                        </div>

                        <!-- Revenue Donut Container -->
                        <div
                            x-data="revenueDonutChartComponent(@js($charts['revenueComposition']))"
                            class="w-full flex items-center justify-center"
                        >
                            <div x-ref="chartContainer" class="w-full min-h-[240px]"></div>
                        </div>
                    </div>

                    <!-- Composition Legend -->
                    <div class="mt-3 pt-3 border-t border-[#DEDAD1] space-y-1.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#15803D]"></span>Sewa Kamar</span>
                            <span class="font-bold font-mono text-[#121417]">Rp {{ number_format($charts['revenueComposition']['amounts']['rent'], 0, ',', '.') }} ({{ $charts['revenueComposition']['percentages']['rent'] }}%)</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#F59E0B]"></span>Deposit</span>
                            <span class="font-bold font-mono text-[#121417]">Rp {{ number_format($charts['revenueComposition']['amounts']['deposit'], 0, ',', '.') }} ({{ $charts['revenueComposition']['percentages']['deposit'] }}%)</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#3B82F6]"></span>Tambahan</span>
                            <span class="font-bold font-mono text-[#121417]">Rp {{ number_format($charts['revenueComposition']['amounts']['additional'], 0, ',', '.') }} ({{ $charts['revenueComposition']['percentages']['additional'] }}%)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3 Bottom Widgets: 5 Tunggakan Terbesar & Kategori Komplain -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                <!-- Widget 1: 5 Penghuni dengan Tunggakan Terbesar (7 Cols) -->
                <div class="md:col-span-7 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#DEDAD1]">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-[#FEE2E2] border border-[#FECACA] flex items-center justify-center text-[#B91C1C]">
                                <x-ui.icon name="clock" class="w-4 h-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-[#121417]">5 Tunggakan Terbesar</h2>
                                <p class="text-[11px] text-[#6B6B6B]">Penghuni dengan tagihan jatuh tempo</p>
                            </div>
                        </div>
                        <a href="{{ route('owner.bills.index') }}" class="px-2.5 py-1 rounded-lg bg-[#F9F8F5] border border-[#DEDAD1] text-[11px] font-semibold text-[#121417] hover:bg-[#EAE6DF] transition-colors">
                            Lihat Semua
                        </a>
                    </div>

                    @if($widgets['topOverdueTenants']->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-[#DEDAD1] text-[#6B6B6B] uppercase font-bold text-[10px]">
                                        <th class="pb-2 w-8 text-center">No</th>
                                        <th class="pb-2">Nama Penghuni</th>
                                        <th class="pb-2">Kamar</th>
                                        <th class="pb-2">Total Tunggakan</th>
                                        <th class="pb-2 text-right">Hari Terlambat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#DEDAD1]/70">
                                    @foreach($widgets['topOverdueTenants'] as $index => $tenant)
                                        <tr class="hover:bg-[#F9F8F5]/60 transition-colors">
                                            <td class="py-2.5 text-center font-bold text-[#6B6B6B]">{{ $index + 1 }}</td>
                                            <td class="py-2.5">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-5 h-5 rounded-full bg-[#FEF3C7] border border-[#FDE68A] flex items-center justify-center text-[10px] font-bold text-[#B45309]">
                                                        {{ strtoupper(substr($tenant['name'], 0, 1)) }}
                                                    </div>
                                                    <span class="font-semibold text-[#121417]">{{ $tenant['name'] }}</span>
                                                </div>
                                            </td>
                                            <td class="py-2.5 text-[#6B6B6B]">{{ $tenant['room_number'] }}</td>
                                            <td class="py-2.5 font-bold font-mono text-[#B91C1C]">
                                                Rp {{ number_format($tenant['total_overdue'], 0, ',', '.') }}
                                            </td>
                                            <td class="py-2.5 text-right">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#FEE2E2] border border-[#FECACA] text-[#B91C1C]">
                                                    {{ $tenant['days_late'] }} hari
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 px-4 rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1] my-2">
                            <x-ui.icon name="check-circle" class="w-8 h-8 text-[#15803D] mx-auto mb-2" />
                            <p class="text-xs font-bold text-[#121417]">Tidak Ada Tunggakan</p>
                            <p class="text-[11px] text-[#6B6B6B] mt-0.5">Semua tagihan sewa terpantau lunas atau belum melewati jatuh tempo.</p>
                        </div>
                    @endif
                </div>

                <!-- Widget 2: Kategori Komplain Terbanyak (5 Cols) -->
                <div class="md:col-span-5 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#DEDAD1]">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-[#E8F8D0] border border-[#C7F36B]/60 flex items-center justify-center text-[#15803D]">
                                    <x-ui.icon name="message-square" class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-[#121417]">Kategori Komplain</h2>
                                    <p class="text-[11px] text-[#6B6B6B]">Statistik keluhan fasilitas</p>
                                </div>
                            </div>
                            <a href="{{ route('owner.complaints.index') }}" class="px-2.5 py-1 rounded-lg bg-[#F9F8F5] border border-[#DEDAD1] text-[11px] font-semibold text-[#121417] hover:bg-[#EAE6DF] transition-colors">
                                Lihat Semua
                            </a>
                        </div>

                        @if($widgets['topComplaintCategories']->isNotEmpty())
                            <div class="space-y-3 my-2">
                                @foreach($widgets['topComplaintCategories'] as $cat)
                                    <div>
                                        <div class="flex items-center justify-between text-xs font-medium mb-1">
                                            <span class="text-[#121417]">{{ $cat['category'] }}</span>
                                            <span class="font-bold text-[#121417]">{{ $cat['count'] }} ({{ $cat['percentage'] }}%)</span>
                                        </div>
                                        <div class="w-full bg-[#F4F2EC] h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-[#121417] h-full rounded-full" style="width: {{ $cat['percentage'] }}%;"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 px-4 rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1] my-2">
                                <x-ui.icon name="sparkles" class="w-8 h-8 text-[#A5A096] mx-auto mb-2" />
                                <p class="text-xs font-bold text-[#121417]">Belum Ada Komplain</p>
                                <p class="text-[11px] text-[#6B6B6B] mt-0.5">Semua fasilitas kos beroperasi normal tanpa kendala.</p>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#DEDAD1] text-center">
                        <span class="text-[11px] text-[#6B6B6B]">Dikelompokkan sesuai kategori tiket</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Section: Filter Laporan & Quick Ringkasan Links (4 Cols on LG) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Filter Laporan Card -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center gap-2.5 mb-5 pb-3 border-b border-[#DEDAD1]">
                    <div class="w-7 h-7 rounded-lg bg-[#E8F8D0] border border-[#C7F36B]/60 flex items-center justify-center text-[#15803D]">
                        <x-ui.icon name="filter" class="w-4 h-4" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-[#121417]">Filter Laporan</h2>
                        <p class="text-xs text-[#6B6B6B]">Sesuaikan rentang analisis</p>
                    </div>
                </div>

                <form
                    method="GET"
                    action="{{ route('owner.analytics.index') }}"
                    x-data="{
                        periodType: '{{ $periodType }}',
                        period: '{{ $periodKey }}',
                        propertyId: '{{ $selectedPropertyId ?? '' }}',
                        monthlyOptions: @js($filterOptions['monthly']),
                        quarterlyOptions: @js($filterOptions['quarterly']),
                        yearlyOptions: @js($filterOptions['yearly']),
                        switchType(type) {
                            this.periodType = type;
                            if (type === 'month') {
                                this.period = this.monthlyOptions[0]?.value || '';
                            } else if (type === 'quarter') {
                                this.period = this.quarterlyOptions[0]?.value || '';
                            } else if (type === 'year') {
                                this.period = this.yearlyOptions[0]?.value || '';
                            }
                        }
                    }"
                    class="space-y-4"
                >
                    <!-- Period Type Tabs -->
                    <div>
                        <label class="block text-xs font-semibold text-[#121417] mb-2">Tipe Periode</label>
                        <input type="hidden" name="period_type" :value="periodType">
                        <div class="grid grid-cols-3 gap-1.5 p-1 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1]">
                            <button
                                type="button"
                                @click="switchType('month')"
                                :class="periodType === 'month' ? 'bg-[#121417] text-white' : 'text-[#6B6B6B] hover:text-[#121417]'"
                                class="py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer text-center"
                            >
                                Bulanan
                            </button>
                            <button
                                type="button"
                                @click="switchType('quarter')"
                                :class="periodType === 'quarter' ? 'bg-[#121417] text-white' : 'text-[#6B6B6B] hover:text-[#121417]'"
                                class="py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer text-center"
                            >
                                Triwulanan
                            </button>
                            <button
                                type="button"
                                @click="switchType('year')"
                                :class="periodType === 'year' ? 'bg-[#121417] text-white' : 'text-[#6B6B6B] hover:text-[#121417]'"
                                class="py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer text-center"
                            >
                                Tahunan
                            </button>
                        </div>
                    </div>

                    <!-- Dynamic Period Selector -->
                    <div>
                        <label class="block text-xs font-semibold text-[#121417] mb-1.5">Periode</label>
                        
                        <!-- Monthly Dropdown -->
                        <div x-show="periodType === 'month'">
                            <select
                                name="period"
                                x-model="period"
                                :disabled="periodType !== 'month'"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] font-medium text-xs text-[#121417] focus:outline-hidden focus:border-[#121417] cursor-pointer"
                            >
                                @foreach($filterOptions['monthly'] as $opt)
                                    <option value="{{ $opt['value'] }}" {{ $periodKey === $opt['value'] ? 'selected' : '' }}>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Quarterly Dropdown -->
                        <div x-show="periodType === 'quarter'" x-cloak>
                            <select
                                name="period"
                                x-model="period"
                                :disabled="periodType !== 'quarter'"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] font-medium text-xs text-[#121417] focus:outline-hidden focus:border-[#121417] cursor-pointer"
                            >
                                @foreach($filterOptions['quarterly'] as $opt)
                                    <option value="{{ $opt['value'] }}" {{ $periodKey === $opt['value'] ? 'selected' : '' }}>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Yearly Dropdown -->
                        <div x-show="periodType === 'year'" x-cloak>
                            <select
                                name="period"
                                x-model="period"
                                :disabled="periodType !== 'year'"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] font-medium text-xs text-[#121417] focus:outline-hidden focus:border-[#121417] cursor-pointer"
                            >
                                @foreach($filterOptions['yearly'] as $opt)
                                    <option value="{{ $opt['value'] }}" {{ $periodKey === $opt['value'] ? 'selected' : '' }}>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Property Selector -->
                    <div>
                        <label class="block text-xs font-semibold text-[#121417] mb-1.5">Properti</label>
                        <select
                            name="property_id"
                            x-model="propertyId"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] font-medium text-xs text-[#121417] focus:outline-hidden focus:border-[#121417] cursor-pointer"
                        >
                            <option value="">Semua Properti ({{ $filterOptions['properties']->count() }})</option>
                            @foreach($filterOptions['properties'] as $prop)
                                <option value="{{ $prop->id }}" {{ (string) $selectedPropertyId === (string) $prop->id ? 'selected' : '' }}>
                                    {{ $prop->name }} ({{ $prop->city }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Submit Filter Button -->
                    <x-ui.button
                        type="submit"
                        variant="primary"
                        class="w-full justify-center"
                    >
                        Terapkan Filter
                    </x-ui.button>

                    @if($selectedPropertyId || $periodType !== 'month' || $periodKey !== now()->format('Y-m'))
                        <a
                            href="{{ route('owner.analytics.index') }}"
                            class="block text-center text-xs text-[#6B6B6B] hover:text-[#121417] hover:underline"
                        >
                            Reset ke Default (Bulan Ini)
                        </a>
                    @endif
                </form>
            </div>

            <!-- Quick Download Ringkasan Laporan Card -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#DEDAD1]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#FEF3C7] border border-[#FDE68A] flex items-center justify-center text-[#B45309]">
                            <x-ui.icon name="file-text" class="w-4 h-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-[#121417]">Ringkasan Laporan</h2>
                            <p class="text-[11px] text-[#6B6B6B]">Cetak PDF & download data CSV</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    @php
                        $reportLinks = [
                            ['type' => 'revenue', 'label' => 'Laporan Pendapatan', 'icon' => 'wallet'],
                            ['type' => 'payments', 'label' => 'Laporan Pembayaran', 'icon' => 'credit-card'],
                            ['type' => 'occupancy', 'label' => 'Laporan Hunian', 'icon' => 'building'],
                            ['type' => 'bills', 'label' => 'Laporan Tagihan & Tunggakan', 'icon' => 'clock'],
                            ['type' => 'complaints', 'label' => 'Laporan Komplain', 'icon' => 'message-square'],
                            ['type' => 'inventory', 'label' => 'Laporan Inventaris', 'icon' => 'box'],
                        ];
                    @endphp

                    @foreach($reportLinks as $link)
                        <div class="p-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <x-ui.icon :name="$link['icon']" class="w-3.5 h-3.5 text-[#6B6B6B] shrink-0" />
                                <span class="text-xs font-medium text-[#121417] truncate">{{ $link['label'] }}</span>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <!-- Print View Link -->
                                <a
                                    href="{{ route('owner.reports.print', ['report_type' => $link['type'], 'property_id' => $selectedPropertyId, 'period_type' => $periodType, 'period' => $periodKey]) }}"
                                    title="Buka layout siap cetak / simpan sebagai PDF"
                                    class="p-1 rounded-lg border border-[#DEDAD1] bg-white text-[#121417] hover:bg-[#EAE6DF] transition-colors"
                                >
                                    <x-ui.icon name="printer" class="w-3.5 h-3.5" />
                                </a>

                                <!-- CSV Export Link -->
                                <a
                                    href="{{ route('owner.reports.export', ['report_type' => $link['type'], 'property_id' => $selectedPropertyId, 'period_type' => $periodType, 'period' => $periodKey]) }}"
                                    title="Unduh dataset mentah format CSV (Excel/Sheets)"
                                    class="p-1 rounded-lg border border-[#DEDAD1] bg-white text-[#121417] hover:bg-[#EAE6DF] transition-colors"
                                >
                                    <x-ui.icon name="download" class="w-3.5 h-3.5" />
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-3 border-t border-[#DEDAD1] text-center">
                    <p class="text-[11px] text-[#6B6B6B]">
                        Format CSV kompatibel dengan Excel & Google Sheets. Layout cetak siap simpan PDF.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Component Initializers for ApexCharts -->
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                // 1. Bar Chart Component (Revenue Trend)
                Alpine.data('chartComponent', (chartData, type) => ({
                    chart: null,
                    init() {
                        this.$nextTick(() => {
                            if (typeof window.ApexCharts === 'undefined') return;
                            if (this.chart) this.chart.destroy();

                            const options = {
                                chart: {
                                    type: 'bar',
                                    height: 260,
                                    stacked: true,
                                    toolbar: { show: false },
                                    fontFamily: 'Inter, sans-serif'
                                },
                                plotOptions: {
                                    bar: {
                                        horizontal: false,
                                        borderRadius: 4,
                                        columnWidth: '45%'
                                    }
                                },
                                dataLabels: { enabled: false },
                                stroke: {
                                    width: 1,
                                    colors: ['#ffffff']
                                },
                                colors: ['#15803D', '#F59E0B', '#EF4444'],
                                series: chartData.series,
                                xaxis: {
                                    categories: chartData.categories,
                                    labels: {
                                        style: { colors: '#6B6B6B', fontSize: '11px', fontWeight: 500 }
                                    },
                                    axisBorder: { color: '#DEDAD1', width: 1 },
                                    axisTicks: { color: '#DEDAD1' }
                                },
                                yaxis: {
                                    labels: {
                                        style: { colors: '#6B6B6B', fontSize: '10px', fontWeight: 500 },
                                        formatter: (val) => val >= 1000000 ? (val / 1000000).toFixed(1) + ' jt' : (val > 0 ? (val / 1000).toFixed(0) + ' rb' : '0')
                                    }
                                },
                                tooltip: {
                                    theme: 'light',
                                    y: {
                                        formatter: (val) => 'Rp ' + Number(val).toLocaleString('id-ID')
                                    }
                                },
                                legend: { show: false },
                                grid: {
                                    borderColor: '#DEDAD1',
                                    strokeDashArray: 3
                                }
                            };

                            this.chart = new window.ApexCharts(this.$refs.chartContainer, options);
                            this.chart.render();
                        });
                    },
                    destroy() {
                        if (this.chart) this.chart.destroy();
                    }
                }));

                // 2. Donut Chart Component (Payment Status)
                Alpine.data('donutChartComponent', (chartData, unit) => ({
                    chart: null,
                    init() {
                        this.$nextTick(() => {
                            if (typeof window.ApexCharts === 'undefined') return;
                            if (this.chart) this.chart.destroy();

                            const total = chartData.total || 0;
                            const options = {
                                chart: {
                                    type: 'donut',
                                    height: 240,
                                    fontFamily: 'Inter, sans-serif'
                                },
                                labels: chartData.labels,
                                series: total > 0 ? chartData.series : [1],
                                colors: total > 0 ? ['#15803D', '#F59E0B', '#EF4444'] : ['#EAE6DF'],
                                stroke: {
                                    width: 2,
                                    colors: ['#ffffff']
                                },
                                dataLabels: { enabled: false },
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            size: '72%',
                                            labels: {
                                                show: true,
                                                name: { show: true, fontSize: '11px', fontWeight: 500, color: '#6B6B6B' },
                                                value: {
                                                    show: true,
                                                    fontSize: '18px',
                                                    fontWeight: 800,
                                                    color: '#121417',
                                                    formatter: (val) => total > 0 ? val + ' ' + unit : '0'
                                                },
                                                total: {
                                                    show: true,
                                                    label: 'Total',
                                                    fontSize: '11px',
                                                    fontWeight: 500,
                                                    color: '#6B6B6B',
                                                    formatter: () => total + ' ' + unit
                                                }
                                            }
                                        }
                                    }
                                },
                                legend: { show: false },
                                tooltip: {
                                    theme: 'light',
                                    y: {
                                        formatter: (val) => total > 0 ? val + ' ' + unit : 'Kosong'
                                    }
                                }
                            };

                            this.chart = new window.ApexCharts(this.$refs.chartContainer, options);
                            this.chart.render();
                        });
                    },
                    destroy() {
                        if (this.chart) this.chart.destroy();
                    }
                }));

                // 3. Occupancy Area Chart Component
                Alpine.data('occupancyChartComponent', (chartData) => ({
                    chart: null,
                    init() {
                        this.$nextTick(() => {
                            if (typeof window.ApexCharts === 'undefined') return;
                            if (this.chart) this.chart.destroy();

                            const options = {
                                chart: {
                                    type: 'area',
                                    height: 250,
                                    toolbar: { show: false },
                                    fontFamily: 'Inter, sans-serif'
                                },
                                dataLabels: { enabled: false },
                                stroke: {
                                    curve: 'smooth',
                                    width: 2.5,
                                    colors: ['#15803D']
                                },
                                fill: {
                                    type: 'gradient',
                                    gradient: {
                                        shadeIntensity: 1,
                                        opacityFrom: 0.4,
                                        opacityTo: 0.02,
                                        colorStops: [
                                            { offset: 0, color: '#C7F36B', opacity: 0.5 },
                                            { offset: 100, color: '#C7F36B', opacity: 0.02 }
                                        ]
                                    }
                                },
                                markers: {
                                    size: 4,
                                    colors: ['#15803D'],
                                    strokeColors: '#ffffff',
                                    strokeWidth: 2,
                                    hover: { size: 6 }
                                },
                                series: chartData.series,
                                xaxis: {
                                    categories: chartData.categories,
                                    labels: {
                                        style: { colors: '#6B6B6B', fontSize: '11px', fontWeight: 500 }
                                    },
                                    axisBorder: { color: '#DEDAD1', width: 1 },
                                    axisTicks: { color: '#DEDAD1' }
                                },
                                yaxis: {
                                    min: 0,
                                    max: 100,
                                    labels: {
                                        style: { colors: '#6B6B6B', fontSize: '10px', fontWeight: 500 },
                                        formatter: (val) => val + '%'
                                    }
                                },
                                tooltip: {
                                    theme: 'light',
                                    y: {
                                        formatter: (val) => val + '% terisi'
                                    }
                                },
                                grid: {
                                    borderColor: '#DEDAD1',
                                    strokeDashArray: 3
                                }
                            };

                            this.chart = new window.ApexCharts(this.$refs.chartContainer, options);
                            this.chart.render();
                        });
                    },
                    destroy() {
                        if (this.chart) this.chart.destroy();
                    }
                }));

                // 4. Revenue Donut Chart Component
                Alpine.data('revenueDonutChartComponent', (chartData) => ({
                    chart: null,
                    init() {
                        this.$nextTick(() => {
                            if (typeof window.ApexCharts === 'undefined') return;
                            if (this.chart) this.chart.destroy();

                            const total = chartData.total || 0;
                            const options = {
                                chart: {
                                    type: 'donut',
                                    height: 240,
                                    fontFamily: 'Inter, sans-serif'
                                },
                                labels: chartData.labels,
                                series: total > 0 ? chartData.series : [1],
                                colors: total > 0 ? ['#15803D', '#F59E0B', '#3B82F6'] : ['#EAE6DF'],
                                stroke: {
                                    width: 2,
                                    colors: ['#ffffff']
                                },
                                dataLabels: { enabled: false },
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            size: '72%',
                                            labels: {
                                                show: true,
                                                name: { show: true, fontSize: '11px', fontWeight: 500, color: '#6B6B6B' },
                                                value: {
                                                    show: true,
                                                    fontSize: '15px',
                                                    fontWeight: 800,
                                                    color: '#121417',
                                                    formatter: (val) => total > 0 ? (val >= 1000000 ? (val / 1000000).toFixed(1) + ' jt' : 'Rp ' + Number(val).toLocaleString('id-ID')) : '0'
                                                },
                                                total: {
                                                    show: true,
                                                    label: 'Kas Masuk',
                                                    fontSize: '11px',
                                                    fontWeight: 500,
                                                    color: '#6B6B6B',
                                                    formatter: () => total >= 1000000 ? (total / 1000000).toFixed(1) + ' jt' : 'Rp ' + Number(total).toLocaleString('id-ID')
                                                }
                                            }
                                        }
                                    }
                                },
                                legend: { show: false },
                                tooltip: {
                                    theme: 'light',
                                    y: {
                                        formatter: (val) => total > 0 ? 'Rp ' + Number(val).toLocaleString('id-ID') : 'Belum ada penerimaan kas'
                                    }
                                }
                            };

                            this.chart = new window.ApexCharts(this.$refs.chartContainer, options);
                            this.chart.render();
                        });
                    },
                    destroy() {
                        if (this.chart) this.chart.destroy();
                    }
                }));
            });
        </script>
    @endpush
</x-layouts.owner>
