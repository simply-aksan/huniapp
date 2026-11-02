<x-layouts.base :title="$reportTitle . ' — HUNI'">
    <!-- Screen-only Action & Navigation Topbar -->
    <div class="no-print sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-[#DEDAD1] px-4 py-3 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- Left: Back link and Title indicator -->
            <div class="flex items-center gap-3">
                <a
                    href="{{ route('owner.analytics.index', request()->only(['period_type', 'period', 'property_id'])) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#DEDAD1] bg-white text-xs font-semibold text-[#121417] hover:bg-[#F4F1EA] hover:border-[#CDC8BD] transition-all cursor-pointer shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                >
                    <x-ui.icon name="arrow-left" class="w-3.5 h-3.5 text-[#5E6470]" />
                    <span>Kembali ke Analitik</span>
                </a>
                <div class="hidden sm:block">
                    <span class="text-xs font-bold text-[#121417]">{{ $reportTitle }}</span>
                    <span class="text-[11px] font-medium text-[#5E6470] ml-1.5">• {{ $periodLabel }}</span>
                </div>
            </div>

            <!-- Center: Report Type Tabs -->
            <div class="flex flex-wrap items-center justify-center gap-1 bg-[#F4F1EA] p-1 rounded-xl border border-[#DEDAD1] text-xs">
                @php
                    $tabs = [
                        'revenue' => ['label' => 'Pendapatan', 'icon' => 'currency-dollar'],
                        'payments' => ['label' => 'Pembayaran', 'icon' => 'credit-card'],
                        'bills' => ['label' => 'Tagihan & Tunggakan', 'icon' => 'document-text'],
                        'occupancy' => ['label' => 'Hunian', 'icon' => 'home'],
                        'complaints' => ['label' => 'Komplain', 'icon' => 'chat-bubble-left-right'],
                        'inventory' => ['label' => 'Inventaris', 'icon' => 'archive-box'],
                    ];
                @endphp
                @foreach($tabs as $key => $tab)
                    <a
                        href="{{ route('owner.reports.print', array_merge(request()->all(), ['report_type' => $key])) }}"
                        class="px-2.5 py-1 rounded-lg transition-all flex items-center gap-1.5 {{ $reportType === $key ? 'bg-white text-[#121417] font-semibold shadow-[0_1px_2px_rgba(18,20,23,0.05)] border border-[#DEDAD1]' : 'text-[#5E6470] hover:text-[#121417] font-medium hover:bg-white/60' }}"
                    >
                        <x-ui.icon :name="$tab['icon']" class="w-3.5 h-3.5 {{ $reportType === $key ? 'text-[#15803D]' : 'text-[#5E6470]' }}" />
                        <span>{{ $tab['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <!-- Right: Action Buttons (Print & Download CSV) -->
            <div class="flex items-center gap-2 shrink-0">
                <!-- CSV Export Download -->
                <a
                    href="{{ route('owner.reports.export', request()->all()) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#DEDAD1] bg-white text-xs font-semibold text-[#121417] hover:bg-[#F4F1EA] hover:border-[#CDC8BD] transition-all cursor-pointer shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                    title="Unduh format CSV yang kompatibel dengan Microsoft Excel dan Google Sheets"
                >
                    <x-ui.icon name="arrow-down-tray" class="w-3.5 h-3.5 text-[#5E6470]" />
                    <span>Unduh CSV</span>
                </a>

                <!-- Print / Save as PDF Button -->
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[#121417] text-white text-xs font-semibold hover:bg-[#20242B] transition-all cursor-pointer shadow-[0_1px_2px_rgba(18,20,23,0.08)]"
                >
                    <x-ui.icon name="printer" class="w-3.5 h-3.5 text-[#C7F36B]" />
                    <span>Cetak / Simpan PDF</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Print-Ready Document Container -->
    <main class="max-w-5xl mx-auto my-4 sm:my-8 p-6 sm:p-10 bg-white border border-[#DEDAD1] rounded-2xl shadow-[0_4px_16px_rgba(18,20,23,0.04)] print:border-none print:shadow-none print:rounded-none print:p-0 print:my-0 print:max-w-none">
        <!-- Document Header / Letterhead -->
        <header class="pb-6 border-b border-[#DEDAD1] mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-[#E8F5E9] border border-[#C8E6C9] text-xs font-medium text-[#15803D] mb-2.5">
                        <x-ui.icon name="home" class="w-3.5 h-3.5 text-[#15803D]" />
                        <span>HUNI</span>
                        <span>•</span>
                        <span>Sistem Manajemen Kos Modern</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#121417] tracking-tight">
                        {{ $reportTitle }}
                    </h1>
                    <p class="text-xs text-[#5E6470] mt-1">
                        Laporan resmi performa properti dan pembukuan finansial
                    </p>
                </div>

                <!-- Document Metadata Card -->
                <div class="bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl p-3.5 text-xs space-y-1 sm:text-right shrink-0 print:border print:bg-white">
                    <p><span class="font-medium text-[#5E6470]">Pemilik:</span> <span class="font-semibold text-[#121417]">{{ $owner->name }}</span></p>
                    <p><span class="font-medium text-[#5E6470]">Properti:</span> <span class="font-semibold text-[#121417]">{{ $propertyName }}</span></p>
                    <p><span class="font-medium text-[#5E6470]">Periode:</span> <span class="font-semibold text-[#121417]">{{ $periodLabel }}</span></p>
                    <p><span class="font-medium text-[#5E6470]">Dicetak:</span> <span class="font-medium text-[#121417] font-mono">{{ $generatedAt->translatedFormat('d M Y H:i:s') }}</span></p>
                </div>
            </div>
        </header>

        <!-- KPI Summary Cards -->
        <section class="mb-8">
            <h2 class="text-xs font-bold text-[#5E6470] uppercase tracking-wider mb-3">Ringkasan Laporan</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                @foreach($summary as $metric)
                    <div class="p-4 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] print:border print:bg-white">
                        <p class="text-[11px] font-semibold text-[#5E6470] uppercase tracking-wider leading-tight">{{ $metric['label'] }}</p>
                        <p class="text-lg sm:text-xl font-bold text-[#121417] mt-1 tracking-tight font-mono">{{ $metric['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Main Data Table -->
        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold text-[#5E6470] uppercase tracking-wider">
                    Rincian Data ({{ count($tableRows) }} Baris)
                </h2>
                <span class="text-[11px] font-medium text-[#5E6470] no-print">
                    Mata uang dalam Rupiah (Rp)
                </span>
            </div>

            <div class="overflow-x-auto rounded-xl border border-[#DEDAD1] print:border print:rounded-none">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#F4F1EA] border-b border-[#DEDAD1] text-[#5E6470] font-semibold uppercase text-[11px] tracking-wider">
                            <th class="py-3 px-3 w-10 text-center border-r border-[#DEDAD1]">No</th>
                            @foreach($tableHeaders as $header)
                                <th class="py-3 px-3 border-r border-[#DEDAD1] last:border-r-0 whitespace-nowrap {{ str_contains(strtolower($header), 'rp') || str_contains(strtolower($header), 'nominal') || str_contains(strtolower($header), 'saldo') || str_contains(strtolower($header), 'terbayar') || str_contains(strtolower($header), 'nilai') || str_contains(strtolower($header), 'harga') ? 'text-right' : '' }}">
                                    {{ $header }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DEDAD1] font-normal">
                        @forelse($tableRows as $row)
                            <tr class="hover:bg-[#F9F8F5] transition-colors {{ $loop->even ? 'bg-[#FAF9F6]/40' : 'bg-white' }} print:bg-white">
                                <td class="py-2.5 px-3 text-center border-r border-[#DEDAD1] font-mono text-[#5E6470]">
                                    {{ $row['index'] }}
                                </td>

                                {{-- A. Revenue Report Columns --}}
                                @if($reportType === 'revenue')
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-semibold text-[#121417] whitespace-nowrap font-mono">
                                        {{ $row['payment_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] whitespace-nowrap font-mono text-[#5E6470]">
                                        {{ $row['bill_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-medium text-[#121417]">
                                        {{ $row['tenant_name'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-[#5E6470]">
                                        {{ $row['property_name'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-mono font-semibold text-center text-[#121417]">
                                        {{ $row['room_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1]">
                                        <span class="inline-block px-2 py-0.5 rounded-full bg-[#F4F1EA] text-[#5E6470] border border-[#DEDAD1] text-[10px] font-medium">
                                            {{ $row['bill_type'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] whitespace-nowrap text-[#5E6470] font-mono">
                                        {{ $row['recognition_date'] }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-[#15803D] whitespace-nowrap font-mono">
                                        Rp {{ number_format($row['amount'], 0, ',', '.') }}
                                    </td>

                                {{-- B. Payments Report Columns --}}
                                @elseif($reportType === 'payments')
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-semibold text-[#121417] whitespace-nowrap font-mono">
                                        {{ $row['payment_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] whitespace-nowrap font-mono text-[#5E6470]">
                                        {{ $row['bill_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-medium text-[#121417]">
                                        {{ $row['tenant_name'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-[#5E6470]">
                                        {{ $row['property_name'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-mono font-semibold text-center text-[#121417]">
                                        {{ $row['room_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] whitespace-nowrap text-[#5E6470] font-mono">
                                        {{ $row['payment_date'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] whitespace-nowrap text-[#5E6470] font-mono">
                                        {{ $row['verified_at'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-[11px] text-[#5E6470]">
                                        {{ $row['method'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-right font-semibold text-[#121417] whitespace-nowrap font-mono">
                                        Rp {{ number_format($row['amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-center">
                                        @php
                                            $badgeClass = match($row['status_raw']) {
                                                'berhasil' => 'bg-[#E8F5E9] text-[#15803D] border-[#C8E6C9]',
                                                'ditolak' => 'bg-[#FEE2E2] text-[#B91C1C] border-[#FECACA]',
                                                default => 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
                                            };
                                        @endphp
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-medium border {{ $badgeClass }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-[#5E6470] text-[11px]">
                                        {{ $row['verifier'] }}
                                    </td>

                                {{-- C. Bills & Arrears Columns --}}
                                @elseif($reportType === 'bills')
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-semibold text-[#121417] whitespace-nowrap font-mono">
                                        {{ $row['bill_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-medium text-[#121417]">
                                        {{ $row['tenant_name'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-[#5E6470]">
                                        {{ $row['property_name'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-mono font-semibold text-center text-[#121417]">
                                        {{ $row['room_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1]">
                                        <span class="inline-block px-2 py-0.5 rounded-full bg-[#F4F1EA] text-[#5E6470] border border-[#DEDAD1] text-[10px] font-medium">
                                            {{ $row['type'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] whitespace-nowrap text-[#5E6470] font-mono">
                                        {{ $row['issue_date'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] whitespace-nowrap text-[#5E6470] font-mono">
                                        {{ $row['due_date'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-right font-medium text-[#121417] whitespace-nowrap font-mono">
                                        Rp {{ number_format($row['total_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-right text-[#15803D] whitespace-nowrap font-mono">
                                        Rp {{ number_format($row['paid_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-right font-semibold {{ $row['outstanding'] > 0 ? 'text-[#B91C1C]' : 'text-[#5E6470]' }} whitespace-nowrap font-mono">
                                        Rp {{ number_format($row['outstanding'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-center">
                                        @php
                                            $billBadgeClass = $row['outstanding'] == 0 ? 'bg-[#E8F5E9] text-[#15803D] border-[#C8E6C9]' : ($row['is_overdue'] ? 'bg-[#FEE2E2] text-[#B91C1C] border-[#FECACA]' : 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]');
                                        @endphp
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-medium border {{ $billBadgeClass }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        @if($row['days_late'] > 0)
                                            <span class="inline-block px-2 py-0.5 rounded-full bg-[#FEE2E2] border border-[#FECACA] text-[10px] font-semibold text-[#B91C1C] font-mono">
                                                {{ $row['days_late'] }} Hari
                                            </span>
                                        @else
                                            <span class="text-[#5E6470]">-</span>
                                        @endif
                                    </td>

                                {{-- D. Occupancy Report Columns --}}
                                @elseif($reportType === 'occupancy')
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-[#5E6470]">
                                        {{ $row['property_name'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-mono font-semibold text-[#121417] text-center">
                                        {{ $row['room_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-[#5E6470]">
                                        {{ $row['floor_type'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-right font-medium text-[#121417] font-mono">
                                        Rp {{ number_format($row['price'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-center">
                                        @php
                                            $occBadgeClass = match($row['status_raw']) {
                                                'terisi' => 'bg-[#E8F5E9] text-[#15803D] border-[#C8E6C9]',
                                                'kosong' => 'bg-[#F4F1EA] text-[#5E6470] border-[#DEDAD1]',
                                                'perbaikan' => 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
                                                default => 'bg-[#F4F1EA] text-[#5E6470] border-[#DEDAD1]',
                                            };
                                        @endphp
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-medium border {{ $occBadgeClass }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-medium text-[#121417]">
                                        {{ $row['tenant_name'] }}
                                    </td>
                                    <td class="py-2.5 px-3 text-[#5E6470] text-[11px] font-mono">
                                        {{ $row['contract_period'] }}
                                    </td>

                                {{-- E. Complaints Report Columns --}}
                                @elseif($reportType === 'complaints')
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-semibold text-[#121417] whitespace-nowrap font-mono">
                                        {{ $row['ticket_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] whitespace-nowrap text-[#5E6470] font-mono">
                                        {{ $row['date'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1]">
                                        <span class="inline-block px-2 py-0.5 rounded-full bg-[#F4F1EA] text-[#5E6470] border border-[#DEDAD1] text-[10px] font-medium">
                                            {{ $row['category'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-medium text-[#121417]">
                                        {{ $row['title'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-center">
                                        @php
                                            $compBadgeClass = match($row['status_raw']) {
                                                'selesai', 'ditutup' => 'bg-[#E8F5E9] text-[#15803D] border-[#C8E6C9]',
                                                'diproses', 'menunggu_tanggapan' => 'bg-[#EFF6FF] text-[#1D4ED8] border-[#BFDBFE]',
                                                default => 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
                                            };
                                        @endphp
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-medium border {{ $compBadgeClass }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-center text-[10px] font-semibold uppercase text-[#5E6470]">
                                        {{ $row['priority'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-[#5E6470]">
                                        {{ $row['property_name'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-center font-mono font-semibold text-[#121417]">
                                        {{ $row['room_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3 text-[#121417] font-medium">
                                        {{ $row['tenant_name'] }}
                                    </td>

                                {{-- F. Inventory Report Columns --}}
                                @elseif($reportType === 'inventory')
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] font-semibold text-[#121417]">
                                        {{ $row['name'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-[#5E6470] text-[11px] font-mono">
                                        {{ $row['serial_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1]">
                                        <span class="inline-block px-2 py-0.5 rounded-full bg-[#F4F1EA] text-[#5E6470] border border-[#DEDAD1] text-[10px] font-medium">
                                            {{ $row['category'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-[#5E6470]">
                                        {{ $row['property_name'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-[#121417]">
                                        {{ $row['location'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-center">
                                        @php
                                            $invBadgeClass = in_array($row['condition_raw'], ['baik', 'baru'], true) ? 'bg-[#E8F5E9] text-[#15803D] border-[#C8E6C9]' : 'bg-[#FEE2E2] text-[#B91C1C] border-[#FECACA]';
                                        @endphp
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-medium border {{ $invBadgeClass }}">
                                            {{ $row['condition'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-center font-mono font-semibold text-[#121417]">
                                        {{ $row['quantity'] }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-right text-[#5E6470] font-mono">
                                        Rp {{ number_format($row['purchase_price'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-[#DEDAD1] text-right font-semibold text-[#121417] font-mono">
                                        Rp {{ number_format($row['subtotal'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-[#5E6470] text-[11px] font-mono">
                                        {{ $row['purchase_date'] }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="py-8 text-center text-xs font-medium text-[#5E6470] bg-[#F9F8F5]">
                                    Tidak ada data untuk filter periode dan properti yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    {{-- Table Total Footer --}}
                    @if(count($tableRows) > 0)
                        <tfoot>
                            <tr class="bg-[#F4F1EA] border-t-2 border-[#121417] font-semibold text-[#121417]">
                                <td class="py-3 px-3 text-center border-r border-[#DEDAD1]">TOTAL</td>
                                @if($reportType === 'revenue')
                                    <td colspan="6" class="py-3 px-3 border-r border-[#DEDAD1] text-right font-medium">
                                        Total Penerimaan ({{ $totalRowDisplay['total_count'] }} Transaksi):
                                    </td>
                                    <td class="py-3 px-3 text-right text-sm text-[#15803D] font-mono font-bold">
                                        Rp {{ number_format($totalRowDisplay['total_amount'], 0, ',', '.') }}
                                    </td>
                                @elseif($reportType === 'payments')
                                    <td colspan="7" class="py-3 px-3 border-r border-[#DEDAD1] text-right font-medium">
                                        Total Nominal Semua Transaksi:
                                    </td>
                                    <td class="py-3 px-3 text-right text-sm border-r border-[#DEDAD1] font-mono font-bold">
                                        Rp {{ number_format($totalRowDisplay['total_successful'] + $totalRowDisplay['total_pending'] + $totalRowDisplay['total_rejected'], 0, ',', '.') }}
                                    </td>
                                    <td colspan="2" class="py-3 px-3 text-xs text-[#15803D] font-mono font-semibold">
                                        Berhasil: Rp {{ number_format($totalRowDisplay['total_successful'], 0, ',', '.') }}
                                    </td>
                                @elseif($reportType === 'bills')
                                    <td colspan="6" class="py-3 px-3 border-r border-[#DEDAD1] text-right font-medium">
                                        Total Tagihan & Piutang:
                                    </td>
                                    <td class="py-3 px-3 border-r border-[#DEDAD1] text-right font-mono">
                                        Rp {{ number_format($totalRowDisplay['total_invoiced'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 border-r border-[#DEDAD1] text-right text-[#15803D] font-mono">
                                        Rp {{ number_format($totalRowDisplay['total_paid'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 border-r border-[#DEDAD1] text-right text-[#B91C1C] font-mono font-bold">
                                        Rp {{ number_format($totalRowDisplay['total_outstanding'], 0, ',', '.') }}
                                    </td>
                                    <td colspan="2" class="py-3 px-3 text-[11px] text-[#B91C1C] font-mono font-semibold">
                                        Tunggakan Overdue: Rp {{ number_format($totalRowDisplay['total_overdue'], 0, ',', '.') }}
                                    </td>
                                @elseif($reportType === 'occupancy')
                                    <td colspan="3" class="py-3 px-3 border-r border-[#DEDAD1] text-right font-medium">
                                        Total Kamar: {{ $totalRowDisplay['total_rooms'] }}
                                    </td>
                                    <td class="py-3 px-3 border-r border-[#DEDAD1] text-center font-bold font-mono">
                                        Okupansi: {{ $totalRowDisplay['occupancy_rate'] }}%
                                    </td>
                                    <td colspan="3" class="py-3 px-3 text-xs text-[#5E6470]">
                                        Terisi: {{ $totalRowDisplay['occupied'] }} | Kosong: {{ $totalRowDisplay['vacant'] }} | Perbaikan: {{ $totalRowDisplay['maintenance'] }}
                                    </td>
                                @elseif($reportType === 'complaints')
                                    <td colspan="3" class="py-3 px-3 border-r border-[#DEDAD1] text-right font-medium">
                                        Total Keluhan: {{ $totalRowDisplay['total'] }}
                                    </td>
                                    <td colspan="5" class="py-3 px-3 text-xs text-[#5E6470]">
                                        Selesai: {{ $totalRowDisplay['resolved'] }} | Dalam Proses: {{ $totalRowDisplay['in_progress'] }} | Baru: {{ $totalRowDisplay['new'] }}
                                    </td>
                                @elseif($reportType === 'inventory')
                                    <td colspan="5" class="py-3 px-3 border-r border-[#DEDAD1] text-right font-medium">
                                        Total {{ count($tableRows) }} Jenis Aset:
                                    </td>
                                    <td class="py-3 px-3 border-r border-[#DEDAD1] text-center font-mono font-semibold">
                                        {{ $totalRowDisplay['total_units'] }} Unit
                                    </td>
                                    <td class="py-3 px-3 border-r border-[#DEDAD1]"></td>
                                    <td class="py-3 px-3 border-r border-[#DEDAD1] text-right text-sm font-mono font-bold">
                                        Rp {{ number_format($totalRowDisplay['total_value'], 0, ',', '.') }}
                                    </td>
                                    <td></td>
                                @endif
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </section>

        <!-- Document Signatures & Verification Footer (Print-Ready) -->
        <footer class="mt-12 pt-6 border-t border-[#DEDAD1] grid grid-cols-2 gap-8 text-xs">
            <div>
                <p class="font-bold text-[#5E6470] uppercase tracking-wider text-[11px]">Catatan Pembukuan:</p>
                <p class="text-[11px] text-[#5E6470] mt-1 leading-relaxed">
                    Dokumen ini digenerate secara otomatis oleh HUNI App berdasarkan catatan pembukuan transaksi kas terverifikasi.
                    Data tersinkronisasi langsung dengan rekening dan riwayat invoice penghuni.
                </p>
                <div class="mt-4 flex items-center gap-2 text-[10px] text-[#5E6470] font-mono">
                    <span>Enkripsi Data Aktif</span>
                    <span>•</span>
                    <span>ID Pemilik: #{{ $owner->id }}</span>
                </div>
            </div>

            <div class="flex flex-col items-end justify-between">
                <div class="text-right">
                    <p class="font-semibold text-[#5E6470]">{{ $propertyName }}</p>
                    <p class="font-bold text-[#121417] mt-0.5">Penanggung Jawab Properti / Owner</p>
                </div>

                <div class="mt-16 text-right">
                    <p class="font-bold text-[#121417] border-b border-[#121417] inline-block pb-0.5 min-w-[180px]">
                        {{ $owner->name }}
                    </p>
                    <p class="text-[10px] text-[#5E6470] mt-0.5">Owner Terverifikasi HUNI</p>
                </div>
            </div>
        </footer>
    </main>

    <!-- Print Stylesheet -->
    @push('styles')
        <style>
            @media print {
                /* Hide screen-only interactive elements */
                .no-print,
                nav,
                aside,
                header.sticky,
                button,
                a[href*="export"],
                a[href*="analytics"] {
                    display: none !important;
                }

                /* Page setup */
                @page {
                    size: auto;
                    margin: 10mm 12mm 10mm 12mm;
                }

                body {
                    background: #ffffff !important;
                    color: #121417 !important;
                    font-size: 10pt !important;
                }

                /* Remove drop shadows and borders styling for printing */
                * {
                    box-shadow: none !important;
                    text-shadow: none !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }

                main {
                    border: none !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    max-width: 100% !important;
                    width: 100% !important;
                }

                /* Table repeating header and page break rules */
                thead {
                    display: table-header-group !important;
                }

                tr {
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                }

                table {
                    border-collapse: collapse !important;
                    width: 100% !important;
                }

                th, td {
                    border-color: #DEDAD1 !important;
                }
            }
        </style>
    @endpush
</x-layouts.base>
