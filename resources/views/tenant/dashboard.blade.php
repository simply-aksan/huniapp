<x-layouts.tenant title="Beranda Penghuni — HUNI" activeNav="dashboard">
    @php
        $firstName = explode(' ', $tenant->name ?? 'Penghuni')[0];
        $activeBill = $recentBills->first(fn($b) => in_array($b->status, ['belum_dibayar', 'menunggu_verifikasi', 'terlambat'], true));
        $latestBill = $recentBills->first();
    @endphp

    <div class="space-y-6">
        <!-- Top Welcome Header -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-[#EFF6FF] text-[#1E40AF] border border-[#BFDBFE]">
                        PORTAL PENGHUNI
                    </span>
                    @if($property)
                        <span class="text-xs text-[#767C85] flex items-center gap-1 font-medium">
                            <x-ui.icon name="building" class="w-3.5 h-3.5" />
                            <span>{{ $property->name }}</span>
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">
                    Hai, {{ $firstName }}!
                </h1>
                <p class="text-xs sm:text-sm text-[#767C85]">
                    Berikut ringkasan kamar, kewajiban pembayaran, dan informasi tempat tinggal Anda.
                </p>
            </div>

            <div class="flex items-center gap-2.5 shrink-0 self-start md:self-auto">
                <a
                    href="{{ route('tenant.chat.index') }}"
                    class="px-4 py-2 rounded-xl bg-[#C7F36B] hover:bg-[#bbf055] text-[#121417] border border-[#121417] font-semibold text-xs shadow-[2px_2px_0_#121417] active:scale-[0.98] transition-all flex items-center gap-2 cursor-pointer"
                >
                    <x-ui.icon name="bot" class="w-4 h-4" />
                    <span>Tanya HUNI AI</span>
                </a>

                <div class="bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl px-3 py-1.5 flex items-center gap-2 text-xs font-semibold text-[#121417] shadow-xs">
                    <x-ui.icon name="calendar" class="w-3.5 h-3.5 text-[#767C85]" />
                    <span>{{ now()->translatedFormat('d M Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Main Status Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Card 1: Kewajiban Tagihan (Billing Status) -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div class="space-y-3.5">
                    <div class="flex items-center justify-between">
                        <div class="w-9 h-9 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                            <x-ui.icon name="receipt" class="w-4 h-4 text-[#474D57]" />
                        </div>
                        @if ($activeBill)
                            <x-ui.badge :variant="$activeBill->status === 'menunggu_verifikasi' ? 'warning' : ($activeBill->status === 'terlambat' ? 'danger' : 'warning')">
                                {{ $activeBill->status === 'menunggu_verifikasi' ? 'Menunggu Verifikasi' : ($activeBill->status === 'terlambat' ? 'Terlambat' : 'Belum Dibayar') }}
                            </x-ui.badge>
                        @else
                            <x-ui.badge variant="success">Lunas</x-ui.badge>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-[#767C85] tracking-wide">Kewajiban Pembayaran</p>
                        @if ($activeBill)
                            <h2 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight mt-1 font-mono tabular-nums">
                                Rp {{ number_format((float) $activeBill->total_amount, 0, ',', '.') }}
                            </h2>
                            <p class="text-xs text-[#767C85] mt-1 flex items-center gap-1.5">
                                <x-ui.icon name="clock" class="w-3.5 h-3.5 text-[#8A919E]" />
                                <span>Jatuh tempo: {{ $activeBill->due_date ? $activeBill->due_date->translatedFormat('d M Y') : '-' }}</span>
                            </p>
                        @else
                            <h2 class="text-2xl font-bold text-[#15803D] tracking-tight mt-1">
                                Semua Tagihan Lunas
                            </h2>
                            <p class="text-xs text-[#767C85] mt-1">
                                Tidak ada tagihan sewa yang tertunda saat ini.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="mt-5 pt-3.5 border-t border-[#EBE8E1]">
                    @if ($activeBill)
                        <x-ui.button :href="route('tenant.bills.show', $activeBill)" variant="primary" class="w-full text-xs py-2 justify-center">
                            <span>Bayar & Unggah Bukti</span>
                            <x-ui.icon name="arrow-right" class="w-4 h-4" />
                        </x-ui.button>
                    @else
                        <x-ui.button :href="route('tenant.bills.index')" variant="secondary" class="w-full text-xs py-2 justify-center">
                            <span>Lihat Riwayat Tagihan</span>
                            <x-ui.icon name="arrow-right" class="w-4 h-4" />
                        </x-ui.button>
                    @endif
                </div>
            </div>

            <!-- Card 2: Kamar & Kontrak Aktif -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div class="space-y-3.5">
                    <div class="flex items-center justify-between">
                        <div class="w-9 h-9 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                            <x-ui.icon name="door" class="w-4 h-4 text-[#474D57]" />
                        </div>
                        @if ($contract)
                            <x-ui.badge :variant="$contract->isActive() ? 'aktif' : 'neutral'">
                                {{ $contract->isActive() ? 'Kontrak Aktif' : ucfirst($contract->status) }}
                            </x-ui.badge>
                        @else
                            <x-ui.badge variant="neutral">Tidak Ada Kontrak</x-ui.badge>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-[#767C85] tracking-wide">Kamar & Kontrak</p>
                        @if ($contract && $room)
                            <h2 class="text-2xl font-extrabold text-[#121417] tracking-tight mt-1">
                                Kamar {{ $room->room_number }}
                            </h2>
                            <p class="text-xs text-[#767C85] mt-0.5">
                                {{ $property?->name ?? 'Properti Kos' }} • Tipe {{ $room->type ?? 'Standar' }}
                            </p>
                            <div class="mt-2.5 bg-[#F9F8F5] border border-[#EBE8E1] rounded-xl p-2.5 text-xs text-[#121417] space-y-1 font-medium">
                                <div class="flex justify-between">
                                    <span class="text-[#767C85]">Mulai:</span>
                                    <span class="font-mono">{{ $contract->start_date ? $contract->start_date->translatedFormat('d M Y') : '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#767C85]">Berakhir:</span>
                                    <span class="font-mono">{{ $contract->end_date ? $contract->end_date->translatedFormat('d M Y') : '-' }}</span>
                                </div>
                            </div>
                        @else
                            <h2 class="text-xl font-bold text-[#121417] mt-1">Belum Terdaftar</h2>
                            <p class="text-xs text-[#767C85] mt-1">
                                Hubungi pemilik kos untuk mendapatkan undangan dan aktivasi kontrak sewa.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="mt-5 pt-3.5 border-t border-[#EBE8E1] flex items-center justify-between text-xs text-[#767C85]">
                    <span>Nomor Kontrak:</span>
                    <span class="text-[#121417] font-mono font-semibold">{{ $contract?->contract_number ?? '-' }}</span>
                </div>
            </div>

            <!-- Card 3: Layanan Cepat & Bantuan -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-3 pb-2 border-b border-[#EBE8E1]">
                        <x-ui.icon name="grid" class="w-4 h-4 text-[#767C85]" />
                        <h3 class="text-xs font-bold text-[#121417] uppercase tracking-wider">Aksi Cepat Penghuni</h3>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <a
                            href="{{ route('tenant.complaints.create') }}"
                            class="p-2.5 bg-[#F9F8F5] hover:bg-white border border-[#DEDAD1] hover:border-[#121417] rounded-xl shadow-xs transition-all flex flex-col justify-between group cursor-pointer"
                        >
                            <x-ui.icon name="message-square" class="w-4 h-4 text-[#474D57] group-hover:text-[#121417]" />
                            <div class="mt-2">
                                <p class="text-xs font-bold text-[#121417]">Lapor Kendala</p>
                                <p class="text-[10px] text-[#767C85]">Buat tiket baru</p>
                            </div>
                        </a>

                        <a
                            href="{{ route('tenant.chat.index') }}"
                            class="p-2.5 bg-[#F9F8F5] hover:bg-white border border-[#DEDAD1] hover:border-[#121417] rounded-xl shadow-xs transition-all flex flex-col justify-between group cursor-pointer"
                        >
                            <x-ui.icon name="bot" class="w-4 h-4 text-[#474D57] group-hover:text-[#121417]" />
                            <div class="mt-2">
                                <p class="text-xs font-bold text-[#121417]">HUNI AI</p>
                                <p class="text-[10px] text-[#767C85]">Tanya aturan kos</p>
                            </div>
                        </a>

                        <a
                            href="{{ route('tenant.bills.index') }}"
                            class="p-2.5 bg-[#F9F8F5] hover:bg-white border border-[#DEDAD1] hover:border-[#121417] rounded-xl shadow-xs transition-all flex flex-col justify-between group cursor-pointer"
                        >
                            <x-ui.icon name="receipt" class="w-4 h-4 text-[#474D57] group-hover:text-[#121417]" />
                            <div class="mt-2">
                                <p class="text-xs font-bold text-[#121417]">Semua Tagihan</p>
                                <p class="text-[10px] text-[#767C85]">Riwayat transfer</p>
                            </div>
                        </a>

                        <a
                            href="{{ route('notifications.index') }}"
                            class="p-2.5 bg-[#F9F8F5] hover:bg-white border border-[#DEDAD1] hover:border-[#121417] rounded-xl shadow-xs transition-all flex flex-col justify-between group cursor-pointer"
                        >
                            <x-ui.icon name="bell" class="w-4 h-4 text-[#474D57] group-hover:text-[#121417]" />
                            <div class="mt-2">
                                <p class="text-xs font-bold text-[#121417]">Notifikasi</p>
                                <p class="text-[10px] text-[#767C85]">Pemberitahuan</p>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-[#EBE8E1] flex items-center justify-between text-[11px] text-[#767C85]">
                    <span>Butuh bantuan pengelola?</span>
                    <span class="text-[#121417] font-semibold">Hubungi pengelola</span>
                </div>
            </div>
        </div>

        <!-- Lower Section: Recent Bills & Recent Complaints -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6">
            <!-- Recent Bills (7 Cols) -->
            <div class="lg:col-span-7 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#EBE8E1]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                            <x-ui.icon name="receipt" class="w-4 h-4 text-[#474D57]" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-[#121417]">Tagihan Terakhir</h2>
                            <p class="text-[11px] text-[#767C85]">Status invoice dan pembayaran terkini</p>
                        </div>
                    </div>
                    <a href="{{ route('tenant.bills.index') }}" class="text-xs font-semibold text-[#121417] hover:underline flex items-center gap-1">
                        <span>Lihat Semua</span>
                        <x-ui.icon name="arrow-right" class="w-3.5 h-3.5" />
                    </a>
                </div>

                @if ($recentBills->isNotEmpty())
                    <div class="divide-y divide-[#EBE8E1]">
                        @foreach ($recentBills as $bill)
                            <div class="py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs font-bold text-[#121417] font-mono">{{ $bill->bill_number }}</p>
                                        <x-ui.badge :variant="$bill->status === 'lunas' ? 'success' : ($bill->status === 'menunggu_verifikasi' ? 'warning' : 'danger')" size="sm">
                                            {{ $bill->status === 'lunas' ? 'Lunas' : ($bill->status === 'menunggu_verifikasi' ? 'Menunggu' : 'Belum Bayar') }}
                                        </x-ui.badge>
                                    </div>
                                    <p class="text-[11px] text-[#767C85] mt-0.5">
                                        Jatuh tempo: {{ $bill->due_date ? $bill->due_date->translatedFormat('d M Y') : '-' }}
                                    </p>
                                </div>

                                <div class="text-right shrink-0">
                                    <p class="text-xs sm:text-sm font-bold text-[#121417] font-mono tabular-nums">
                                        Rp {{ number_format((float) $bill->total_amount, 0, ',', '.') }}
                                    </p>
                                    <a
                                        href="{{ route('tenant.bills.show', $bill) }}"
                                        class="inline-block text-[11px] font-semibold text-[#15803D] hover:underline mt-0.5"
                                    >
                                        Detail →
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center space-y-1.5">
                        <div class="w-9 h-9 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1] mx-auto flex items-center justify-center text-[#767C85]">
                            <x-ui.icon name="receipt" class="w-4 h-4" />
                        </div>
                        <p class="text-xs font-bold text-[#121417]">Belum ada riwayat tagihan</p>
                        <p class="text-[11px] text-[#767C85]">Invoice akan muncul otomatis ketika periode sewa dimulai.</p>
                    </div>
                @endif
            </div>

            <!-- Recent Complaints (5 Cols) -->
            <div class="lg:col-span-5 bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#EBE8E1]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                            <x-ui.icon name="message-square" class="w-4 h-4 text-[#474D57]" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-[#121417]">Tiket Pengaduan</h2>
                            <p class="text-[11px] text-[#767C85]">Status keluhan fasilitas Anda</p>
                        </div>
                    </div>
                    <a href="{{ route('tenant.complaints.index') }}" class="text-xs font-semibold text-[#121417] hover:underline flex items-center gap-1">
                        <span>Lihat Semua</span>
                        <x-ui.icon name="arrow-right" class="w-3.5 h-3.5" />
                    </a>
                </div>

                @if ($recentComplaints->isNotEmpty())
                    <div class="divide-y divide-[#EBE8E1]">
                        @foreach ($recentComplaints as $complaint)
                            <div class="py-2.5 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <x-ui.badge :variant="$complaint->status === 'resolved' || $complaint->status === 'closed' ? 'success' : ($complaint->status === 'in_progress' ? 'info' : 'warning')" size="sm">
                                            {{ ucfirst(str_replace('_', ' ', $complaint->status)) }}
                                        </x-ui.badge>
                                        <span class="text-[10px] font-mono text-[#767C85] font-semibold">{{ $complaint->ticket_number }}</span>
                                    </div>
                                    <p class="text-xs font-semibold text-[#121417] truncate mt-1">{{ $complaint->title }}</p>
                                    <p class="text-[10px] text-[#8A919E] font-mono">{{ $complaint->created_at->diffForHumans() }}</p>
                                </div>
                                <a
                                    href="{{ route('tenant.complaints.show', $complaint) }}"
                                    class="text-xs font-semibold text-[#121417] hover:underline shrink-0"
                                >
                                    Lihat →
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center space-y-2">
                        <div class="w-9 h-9 rounded-xl bg-[#ECFDF5] border border-[#A7F3D0] mx-auto flex items-center justify-center text-[#065F46]">
                            <x-ui.icon name="check-circle" class="w-4 h-4" />
                        </div>
                        <p class="text-xs font-bold text-[#121417]">Tidak ada keluhan aktif</p>
                        <p class="text-[11px] text-[#767C85]">Semua fasilitas tempat tinggal Anda berfungsi normal.</p>
                        <div class="pt-1">
                            <x-ui.button :href="route('tenant.complaints.create')" variant="secondary" size="sm">
                                <span>Buat Laporan Baru</span>
                            </x-ui.button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.tenant>
