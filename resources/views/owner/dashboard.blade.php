<x-layouts.owner title="Owner Dashboard — HUNI" activeNav="dashboard">
    <!-- Welcome Header Card -->
    <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-[#ECFDF5] text-[#065F46] border border-[#A7F3D0]">
                        IKHTISAR OPERASIONAL
                    </span>
                    @if($primaryProperty)
                        <span class="text-xs text-[#767C85] flex items-center gap-1 font-medium">
                            <x-ui.icon name="building" class="w-3.5 h-3.5" />
                            <span>{{ $primaryProperty->name }}</span>
                        </span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">
                    Selamat datang, {{ $owner->name }}!
                </h1>

                <p class="text-xs sm:text-sm text-[#767C85]">
                    @if($primaryProperty)
                        Berikut ringkasan kondisi <strong>{{ $primaryProperty->name }}</strong> dan portofolio kos Anda hari ini.
                    @else
                        Mulai kelola kos Anda dengan menambahkan properti pertama di HUNI.
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0 self-start md:self-auto">
                <a
                    href="{{ route('owner.properties.create') }}"
                    class="px-4 py-2 rounded-xl bg-[#C7F36B] hover:bg-[#bbf055] text-[#121417] border border-[#121417] font-semibold text-xs shadow-[2px_2px_0_#121417] active:scale-[0.98] transition-all flex items-center gap-2 cursor-pointer"
                >
                    <x-ui.icon name="plus" class="w-4 h-4" />
                    <span>Tambah Properti</span>
                </a>

                <!-- Date & City Widget -->
                <div class="bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl px-3 py-1.5 flex items-center gap-2.5 shadow-xs">
                    <div class="w-7 h-7 rounded-lg bg-white border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                        <x-ui.icon name="calendar" class="w-3.5 h-3.5 text-[#767C85]" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-[#121417] leading-tight">{{ now()->translatedFormat('l, d M Y') }}</p>
                        <p class="text-[10px] text-[#767C85] leading-none mt-0.5">{{ $primaryProperty?->city ?? 'Indonesia' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- Stat 1: Total Kamar -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-[#767C85] tracking-wide">Total Kamar</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#121417] mt-1 font-mono tabular-nums">{{ $totalRooms }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                    <x-ui.icon name="door" class="w-5 h-5 text-[#474D57]" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#EBE8E1]">
                <div class="flex items-center justify-between text-xs font-medium text-[#121417] mb-1.5">
                    <span class="text-[#15803D] font-semibold">{{ $occupiedRooms }} Terisi ({{ $occupancyRate }}%)</span>
                    <span class="text-[#767C85]">{{ $vacantRooms }} Kosong</span>
                </div>
                <div class="w-full bg-[#F3F1EC] h-2 rounded-full overflow-hidden">
                    <div class="bg-[#15803D] h-full rounded-full transition-all duration-300" style="width: {{ $occupancyRate }}%;"></div>
                </div>
            </div>
        </div>

        <!-- Stat 2: Total Penghuni -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-[#767C85] tracking-wide">Total Penghuni</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#121417] mt-1 font-mono tabular-nums">{{ $totalTenants }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                    <x-ui.icon name="users" class="w-5 h-5 text-[#474D57]" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#EBE8E1] flex items-center justify-between">
                <span class="text-xs text-[#767C85]">Kontrak sewa aktif</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#F3F1EC] text-[#121417] border border-[#DEDAD1]">
                    {{ $totalProperties }} Properti
                </span>
            </div>
        </div>

        <!-- Stat 3: Pendapatan Bulan Ini -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-[#767C85] tracking-wide">Pendapatan Bulan Ini</p>
                    <p class="text-xl sm:text-2xl font-extrabold text-[#121417] mt-1 font-mono tabular-nums">
                        Rp {{ number_format($thisMonthRevenue, 0, ',', '.') }}
                    </p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#ECFDF5] border border-[#A7F3D0] flex items-center justify-center text-[#065F46]">
                    <x-ui.icon name="wallet" class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#EBE8E1] flex items-center justify-between">
                <span class="text-xs text-[#767C85]">{{ now()->translatedFormat('F Y') }}</span>
                @if($revenueGrowth !== null)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $revenueGrowth >= 0 ? 'bg-[#ECFDF5] text-[#065F46] border border-[#A7F3D0]' : 'bg-[#FEF2F2] text-[#991B1B] border border-[#FECACA]' }}">
                        {{ $revenueGrowth >= 0 ? '↑ +'.$revenueGrowth.'%' : '↓ '.$revenueGrowth.'%' }}
                    </span>
                @else
                    <span class="text-[11px] text-[#767C85]">Berdasarkan tagihan lunas</span>
                @endif
            </div>
        </div>

        <!-- Stat 4: Tagihan Pending -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-[#767C85] tracking-wide">Tagihan Pending</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#121417] mt-1 font-mono tabular-nums">
                        {{ $pendingBillsCount }} <span class="text-sm font-semibold text-[#767C85]">Invoice</span>
                    </p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#FFFBEB] border border-[#FDE68A] flex items-center justify-center text-[#92400E]">
                    <x-ui.icon name="credit-card" class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#EBE8E1] flex items-center justify-between">
                <span class="text-xs text-[#767C85]">Total belum lunas:</span>
                <span class="text-xs font-bold text-[#DC2626] font-mono tabular-nums">
                    Rp {{ number_format($pendingBillsAmount, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Middle Section: Chart + Room Status + Activities -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6">
        <!-- Chart: Pendapatan 6 Bulan Terakhir (5 Cols) -->
        <div class="lg:col-span-5 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#EBE8E1]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                            <x-ui.icon name="bar-chart" class="w-4 h-4 text-[#474D57]" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-[#121417]">Tren Pendapatan</h2>
                            <p class="text-[11px] text-[#767C85]">Total penerimaan sewa 6 bulan terakhir</p>
                        </div>
                    </div>
                </div>

                <!-- Bar Chart Representation -->
                <div class="pt-4 pb-2">
                    <div class="flex items-end justify-between gap-3 h-40 px-2 border-b border-[#EBE8E1]">
                        @foreach($monthlyChartData as $bar)
                            @php
                                $heightPct = $maxChartAmount > 0 && $bar['amount'] > 0
                                    ? max(14, min(100, (int) round(($bar['amount'] / $maxChartAmount) * 100)))
                                    : 8;
                            @endphp
                            <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end group relative">
                                <!-- Tooltip -->
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute -top-8 bg-[#121417] text-white text-[10px] font-semibold px-2 py-0.5 rounded-md whitespace-nowrap pointer-events-none z-20 font-mono">
                                    Rp {{ number_format($bar['amount'], 0, ',', '.') }}
                                </div>
                                <div class="w-full max-w-[32px] rounded-t-md transition-all duration-200 {{ $bar['is_current'] ? 'bg-[#15803D]' : ($bar['amount'] > 0 ? 'bg-[#DEDAD1] group-hover:bg-[#121417]' : 'bg-[#F3F1EC]') }}"
                                     style="height: {{ $heightPct }}%;">
                                </div>
                                <span class="text-[11px] font-medium text-[#767C85] mt-1">{{ $bar['month'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-[#EBE8E1] flex items-center justify-between text-xs text-[#767C85]">
                <span>Total akumulasi:</span>
                <span class="text-[#121417] font-bold font-mono tabular-nums">
                    Rp {{ number_format(collect($monthlyChartData)->sum('amount'), 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Breakdown: Status Kamar (3 Cols) -->
        <div class="lg:col-span-3 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#EBE8E1]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                            <x-ui.icon name="door" class="w-4 h-4 text-[#474D57]" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-[#121417]">Status Kamar</h2>
                            <p class="text-[11px] text-[#767C85]">Kondisi keterisian kamar</p>
                        </div>
                    </div>
                </div>

                <!-- Circular visual representation -->
                <div class="my-3 flex flex-col items-center justify-center">
                    <div class="relative w-28 h-28 rounded-full border border-[#DEDAD1] flex items-center justify-center bg-[#F9F8F5] shadow-xs">
                        <div class="text-center">
                            <span class="text-2xl font-extrabold text-[#121417] leading-none font-mono tabular-nums">{{ $totalRooms }}</span>
                            <span class="block text-[10px] font-semibold text-[#767C85] uppercase tracking-wider mt-0.5">Total Kamar</span>
                        </div>
                    </div>
                </div>

                <!-- Legend details -->
                <div class="space-y-1.5 mt-3 text-xs">
                    <div class="flex items-center justify-between p-2 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1]">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#15803D]"></span>
                            <span class="font-medium text-[#121417]">Terisi</span>
                        </div>
                        <span class="font-bold text-[#121417] font-mono tabular-nums">{{ $occupiedRooms }} ({{ $occupancyRate }}%)</span>
                    </div>

                    <div class="flex items-center justify-between p-2 rounded-xl bg-white border border-[#EBE8E1]">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#DEDAD1]"></span>
                            <span class="font-medium text-[#474D57]">Kosong</span>
                        </div>
                        <span class="font-bold text-[#121417] font-mono tabular-nums">{{ $vacantRooms }} ({{ $totalRooms > 0 ? (int) round(($vacantRooms / $totalRooms) * 100) : 0 }}%)</span>
                    </div>

                    <div class="flex items-center justify-between p-2 rounded-xl bg-white border border-[#EBE8E1]">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#D97706]"></span>
                            <span class="font-medium text-[#474D57]">Perbaikan</span>
                        </div>
                        <span class="font-bold text-[#121417] font-mono tabular-nums">{{ $maintenanceRooms }} ({{ $totalRooms > 0 ? (int) round(($maintenanceRooms / $totalRooms) * 100) : 0 }}%)</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-[#EBE8E1]">
                <a href="{{ route('owner.properties.index') }}" class="block text-center text-xs font-semibold text-[#121417] hover:underline cursor-pointer">
                    Kelola Kamar & Properti →
                </a>
            </div>
        </div>

        <!-- Notifikasi & Aktivitas Terbaru (4 Cols) -->
        <div class="lg:col-span-4 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#EBE8E1]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                            <x-ui.icon name="bell" class="w-4 h-4 text-[#474D57]" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-[#121417]">Aktivitas Terkini</h2>
                            <p class="text-[11px] text-[#767C85]">Update operasional kos</p>
                        </div>
                    </div>
                </div>

                @if($recentActivities->isNotEmpty())
                    <div class="space-y-2.5">
                        @foreach($recentActivities as $act)
                            <div class="p-2.5 rounded-xl border border-[#EBE8E1] bg-[#F9F8F5] flex items-start gap-3">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ $act['type'] === 'payment' ? 'bg-[#ECFDF5] text-[#065F46]' : 'bg-[#FEF2F2] text-[#991B1B]' }}">
                                    <x-ui.icon :name="$act['type'] === 'payment' ? 'credit-card' : 'wrench'" class="w-3.5 h-3.5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-[#121417] truncate">{{ $act['title'] }}</p>
                                    <p class="text-[11px] text-[#767C85] truncate">{{ $act['subtitle'] }}</p>
                                    <span class="text-[10px] text-[#8A919E] mt-0.5 block font-mono">{{ $act['time']->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 px-4 rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1] my-3">
                        <div class="w-8 h-8 rounded-lg bg-white border border-[#EBE8E1] mx-auto flex items-center justify-center text-[#767C85] mb-2">
                            <x-ui.icon name="file-text" class="w-4 h-4" />
                        </div>
                        <p class="text-xs font-bold text-[#121417]">Belum Ada Aktivitas</p>
                        <p class="text-[11px] text-[#767C85] mt-0.5">Aktivitas sewa dan pembayaran akan muncul di sini secara otomatis.</p>
                    </div>
                @endif
            </div>

            <div class="mt-4 pt-3 border-t border-[#EBE8E1] text-center">
                <span class="text-[11px] text-[#767C85]">Data tersinkronisasi otomatis</span>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Invoice Terbaru & Keluhan Terbaru -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6">
        <!-- Invoice Terbaru (8 Cols) -->
        <div class="lg:col-span-8 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#EBE8E1]">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                        <x-ui.icon name="receipt" class="w-4 h-4 text-[#474D57]" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[#121417]">Tagihan & Invoice Terbaru</h2>
                        <p class="text-xs text-[#767C85]">Daftar status penagihan sewa</p>
                    </div>
                </div>
                <a href="{{ route('owner.bills.index') }}" class="text-xs font-semibold text-[#121417] hover:underline flex items-center gap-1">
                    <span>Lihat Semua</span>
                    <x-ui.icon name="arrow-right" class="w-3.5 h-3.5" />
                </a>
            </div>

            @if($recentBills->isNotEmpty())
                <div class="overflow-x-auto huni-scrollbar">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#EBE8E1] text-[#767C85] uppercase font-semibold text-[10px] tracking-wider">
                                <th class="pb-2.5 font-semibold"># Invoice</th>
                                <th class="pb-2.5 font-semibold">Penghuni</th>
                                <th class="pb-2.5 font-semibold">Kamar</th>
                                <th class="pb-2.5 font-semibold">Jumlah</th>
                                <th class="pb-2.5 font-semibold">Status</th>
                                <th class="pb-2.5 font-semibold">Jatuh Tempo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EBE8E1]">
                            @foreach($recentBills as $bill)
                                <tr class="hover:bg-[#F9F8F5] transition-colors">
                                    <td class="py-3 font-mono font-bold text-[#121417]">{{ $bill->bill_number }}</td>
                                    <td class="py-3 font-semibold text-[#121417]">{{ $bill->contract?->user?->name ?? ($bill->user?->name ?? '-') }}</td>
                                    <td class="py-3 font-medium text-[#474D57]">{{ $bill->room?->room_number ?? '-' }}</td>
                                    <td class="py-3 font-bold text-[#121417] font-mono tabular-nums">Rp {{ number_format((float) $bill->total_amount, 0, ',', '.') }}</td>
                                    <td class="py-3">
                                        @if($bill->status === 'lunas')
                                            <x-ui.badge variant="aktif" size="sm">Lunas</x-ui.badge>
                                        @elseif($bill->status === 'menunggu_verifikasi')
                                            <x-ui.badge variant="warning" size="sm">Menunggu</x-ui.badge>
                                        @elseif($bill->status === 'terlambat')
                                            <x-ui.badge variant="danger" size="sm">Terlambat</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="neutral" size="sm">{{ ucfirst(str_replace('_', ' ', $bill->status)) }}</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="py-3 font-medium text-[#767C85] font-mono">{{ $bill->due_date?->translatedFormat('d M Y') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-10 px-4 rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1] my-2">
                    <div class="w-10 h-10 rounded-xl bg-white border border-[#EBE8E1] mx-auto flex items-center justify-center text-[#767C85] mb-2">
                        <x-ui.icon name="receipt" class="w-5 h-5" />
                    </div>
                    <p class="text-sm font-bold text-[#121417]">Belum Ada Tagihan Terbit</p>
                    <p class="text-xs text-[#767C85] mt-1 max-w-sm mx-auto">Tagihan sewa akan dibuat secara otomatis saat kontrak sewa penghuni dibuat dan aktif.</p>
                </div>
            @endif
        </div>

        <!-- Keluhan Terbaru (4 Cols) -->
        <div class="lg:col-span-4 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#EBE8E1]">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                        <x-ui.icon name="message-square" class="w-4 h-4 text-[#474D57]" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[#121417]">Keluhan Terbaru</h2>
                        <p class="text-xs text-[#767C85]">Aspirasi dan kendala kamar</p>
                    </div>
                </div>
                <a href="{{ route('owner.complaints.index') }}" class="text-xs font-semibold text-[#121417] hover:underline">
                    {{ $recentComplaints->count() }} Data →
                </a>
            </div>

            @if($recentComplaints->isNotEmpty())
                <div class="space-y-2.5">
                    @foreach($recentComplaints as $complaint)
                        <div class="p-3 rounded-xl border border-[#EBE8E1] bg-[#F9F8F5] flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-[#121417] truncate">{{ $complaint->title }}</p>
                                <p class="text-[11px] text-[#767C85] mt-0.5">
                                    Kamar {{ $complaint->room?->room_number ?? '-' }} • {{ $complaint->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <div>
                                @if($complaint->status === 'resolved' || $complaint->status === 'closed')
                                    <x-ui.badge variant="aktif" size="sm">Selesai</x-ui.badge>
                                @elseif($complaint->status === 'in_progress')
                                    <x-ui.badge variant="warning" size="sm">Diproses</x-ui.badge>
                                @else
                                    <x-ui.badge variant="danger" size="sm">Baru</x-ui.badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 px-4 rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1] my-2">
                    <div class="w-10 h-10 rounded-xl bg-[#ECFDF5] border border-[#A7F3D0] mx-auto flex items-center justify-center text-[#065F46] mb-2">
                        <x-ui.icon name="check-circle" class="w-5 h-5" />
                    </div>
                    <p class="text-sm font-bold text-[#121417]">Tidak Ada Keluhan</p>
                    <p class="text-xs text-[#767C85] mt-1">Seluruh fasilitas kamar terpantau dalam kondisi baik.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.owner>
