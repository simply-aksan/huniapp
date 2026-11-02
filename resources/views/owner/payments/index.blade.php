<x-layouts.owner title="Verifikasi Pembayaran — HUNI" activeNav="payments">
    <div class="space-y-6">
        <!-- Header Banner Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-2">
                        <a href="{{ route('owner.dashboard') }}" class="hover:text-[#121417] transition-colors">Dashboard</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <span class="text-[#121417] font-semibold">Verifikasi Pembayaran</span>
                    </nav>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#C7F36B]/20 border border-[#C7F36B]/60 flex items-center justify-center shrink-0">
                            <x-ui.icon name="check-circle" class="w-5 h-5 text-[#15803D]" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">
                                Verifikasi Pembayaran
                            </h1>
                            <p class="text-xs sm:text-sm text-[#6B6B6B] mt-0.5">
                                Verifikasi setoran penyewa, periksa bukti transfer, dan rekonsiliasi saldo tagihan secara akurat.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <x-ui.button href="{{ route('owner.bills.index') }}" variant="outline" class="gap-2">
                        <x-ui.icon name="receipt" class="w-4 h-4 text-[#6B6B6B]" />
                        <span>Lihat Semua Tagihan</span>
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
            <!-- Menunggu Verifikasi -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Menunggu Verifikasi</span>
                    <div class="w-8 h-8 rounded-lg bg-[#FEF3C7] border border-[#FDE68A] flex items-center justify-center text-[#B45309]">
                        <x-ui.icon name="clock" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-extrabold text-[#B45309] tracking-tight font-mono">{{ number_format($stats['pending_count']) }}</span>
                        <span class="text-xs text-[#6B6B6B]">transaksi</span>
                    </div>
                    <p class="text-xs font-mono font-medium text-[#6B6B6B] mt-1 truncate">Rp {{ number_format($stats['pending_amount'], 0, ',', '.') }}</p>
                </div>
            </div>

            <!-- Terverifikasi Bulan Ini -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Disetujui Bulan Ini</span>
                    <div class="w-8 h-8 rounded-lg bg-[#E8F8D0] border border-[#C7F36B]/60 flex items-center justify-center text-[#15803D]">
                        <x-ui.icon name="check-circle" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-extrabold text-[#15803D] tracking-tight font-mono">{{ number_format($stats['verified_this_month_count']) }}</span>
                        <span class="text-xs text-[#6B6B6B]">transaksi</span>
                    </div>
                    <p class="text-xs font-mono font-medium text-[#6B6B6B] mt-1 truncate">Rp {{ number_format($stats['verified_this_month_amount'], 0, ',', '.') }}</p>
                </div>
            </div>

            <!-- Pembayaran Ditolak -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Ditolak</span>
                    <div class="w-8 h-8 rounded-lg bg-[#FEE2E2] border border-[#FECACA] flex items-center justify-center text-[#B91C1C]">
                        <x-ui.icon name="x-circle" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-extrabold text-[#B91C1C] tracking-tight font-mono">{{ number_format($stats['rejected_count']) }}</span>
                        <span class="text-xs text-[#6B6B6B]">transaksi</span>
                    </div>
                    <p class="text-[11px] text-[#6B6B6B] mt-1">Perlu revisi penyewa</p>
                </div>
            </div>

            <!-- Total Transaksi -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Total Transaksi</span>
                    <div class="w-8 h-8 rounded-lg bg-[#F4F2EC] border border-[#DEDAD1] flex items-center justify-center text-[#121417]">
                        <x-ui.icon name="credit-card" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-extrabold text-[#121417] tracking-tight font-mono">{{ number_format($stats['total']) }}</span>
                        <span class="text-xs text-[#6B6B6B]">semua</span>
                    </div>
                    <p class="text-[11px] text-[#6B6B6B] mt-1">Seluruh riwayat pembayaran</p>
                </div>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <form method="GET" action="{{ route('owner.payments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5 items-end">
                <!-- Search Input -->
                <div class="sm:col-span-2 lg:col-span-4 space-y-1.5">
                    <label for="q" class="text-xs font-semibold text-[#121417] block">Cari Pembayaran</label>
                    <input
                        type="text"
                        name="q"
                        id="q"
                        value="{{ $searchQuery }}"
                        placeholder="No pembayaran, no invoice, nama penyewa..."
                        class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                    />
                </div>

                <!-- Property Filter -->
                <div class="lg:col-span-3 space-y-1.5">
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

                <!-- Status Filter -->
                <div class="lg:col-span-2 space-y-1.5">
                    <label for="status" class="text-xs font-semibold text-[#121417] block">Status Verifikasi</label>
                    <select
                        name="status"
                        id="status"
                        class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                    >
                        <option value="">Semua Status</option>
                        <option value="menunggu_verifikasi" {{ $selectedStatus === 'menunggu_verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                        <option value="berhasil" {{ $selectedStatus === 'berhasil' ? 'selected' : '' }}>Berhasil (Disetujui)</option>
                        <option value="ditolak" {{ $selectedStatus === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>

                <!-- Payment Date Filter -->
                <div class="lg:col-span-2 space-y-1.5">
                    <label for="payment_date" class="text-xs font-semibold text-[#121417] block">Tanggal Bayar</label>
                    <input
                        type="date"
                        name="payment_date"
                        id="payment_date"
                        value="{{ $selectedDate }}"
                        class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
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
                    @if($searchQuery || $selectedProperty || $selectedStatus || $selectedDate)
                        <a
                            href="{{ route('owner.payments.index') }}"
                            class="py-2.5 px-3 rounded-xl bg-[#F4F2EC] border border-[#DEDAD1] font-semibold text-xs text-[#6B6B6B] hover:text-[#121417] hover:bg-[#EAE6DF] transition-colors text-center"
                            title="Reset Filter"
                        >
                            ✕
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Payments Table Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl shadow-[0_1px_3px_rgba(18,20,23,0.04)] overflow-hidden">
            <div class="px-6 py-4.5 border-b border-[#DEDAD1] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-[#121417]">Daftar Pembayaran Masuk</h2>
                    <p class="text-xs text-[#6B6B6B] mt-0.5">Prioritas verifikasi pembayaran yang sedang menunggu konfirmasi Anda.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#DEDAD1] bg-[#F9F8F5] text-[11px] font-bold text-[#6B6B6B] uppercase tracking-wider">
                            <th class="py-3 px-5">No. Pembayaran</th>
                            <th class="py-3 px-5">Penghuni & Kamar</th>
                            <th class="py-3 px-5">Tagihan Terkait</th>
                            <th class="py-3 px-5">Metode & Ref</th>
                            <th class="py-3 px-5 text-right">Nominal</th>
                            <th class="py-3 px-5 text-center">Bukti</th>
                            <th class="py-3 px-5 text-center">Status</th>
                            <th class="py-3 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DEDAD1]/70 text-xs text-[#121417]">
                        @forelse($payments as $payment)
                            @php
                                $property = $payment->property();
                                $room = $payment->bill?->room ?? $payment->bill?->contract?->room;
                                $tenant = $payment->user;
                                $isPending = $payment->isPending();
                            @endphp
                            <tr class="transition-colors {{ $isPending ? 'bg-[#FFFBEB]/50 hover:bg-[#FFFBEB]/80' : 'hover:bg-[#F9F8F5]/60' }}">
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold font-mono text-[#121417]">{{ $payment->payment_number }}</span>
                                        @if($isPending)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">BARU</span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-[#6B6B6B] font-normal mt-0.5">
                                        {{ $payment->payment_date?->format('d/m/Y H:i') ?? $payment->created_at->format('d/m/Y H:i') }}
                                    </p>
                                </td>

                                <td class="py-3.5 px-5">
                                    <div class="font-semibold text-[#121417]">{{ $tenant?->name ?? 'Penyewa' }}</div>
                                    <p class="text-[11px] text-[#6B6B6B] mt-0.5">
                                        {{ $property?->name ?? '-' }} • Kamar {{ $room?->room_number ?? '-' }}
                                    </p>
                                </td>

                                <td class="py-3.5 px-5">
                                    @if($payment->bill)
                                        <a href="{{ route('owner.bills.show', $payment->bill) }}" class="font-semibold text-[#15803D] hover:underline flex items-center gap-1">
                                            <span>{{ $payment->bill->bill_number }}</span>
                                            <span class="text-[10px]">↗</span>
                                        </a>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            @if($payment->bill->type === 'sewa')
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-semibold bg-[#EFF6FF] border border-[#BFDBFE] text-[#1D4ED8]">Sewa</span>
                                            @elseif($payment->bill->type === 'deposit')
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-semibold bg-[#FEF3C7] border border-[#FDE68A] text-[#B45309]">Deposit</span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-semibold bg-[#F3E8FF] border border-[#E9D5FF] text-[#7E22CE]">Tambahan</span>
                                            @endif
                                            <span class="text-[10px] text-[#6B6B6B]">{{ $payment->bill->formattedPeriod() }}</span>
                                        </div>
                                    @else
                                        <span class="text-[#6B6B6B]">-</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-5">
                                    <span class="font-semibold text-[#121417] block">{{ $payment->payment_method ?? 'Transfer Bank' }}</span>
                                    @if($payment->reference_number)
                                        <span class="text-[11px] text-[#6B6B6B] font-mono block">Ref: {{ $payment->reference_number }}</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-5 text-right font-bold font-mono text-[#121417]">
                                    Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                </td>

                                <td class="py-3.5 px-5 text-center">
                                    @if($payment->proofs->isNotEmpty())
                                        @php $firstProof = $payment->proofs->first(); @endphp
                                        <a
                                            href="{{ route('owner.payments.proofs.download', [$payment, $firstProof]) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#F4F2EC] border border-[#DEDAD1] text-[11px] font-semibold text-[#121417] hover:bg-[#EAE6DF] transition-colors"
                                            title="Unduh / Lihat Bukti Pembayaran"
                                        >
                                            <x-ui.icon name="file-text" class="w-3.5 h-3.5 text-[#6B6B6B]" />
                                            <span>{{ $payment->proofs->count() }} Bukti</span>
                                        </a>
                                    @else
                                        <span class="text-[11px] text-[#6B6B6B] italic">Tidak ada</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-5 text-center">
                                    @if($payment->status === 'berhasil')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8D0] border border-[#C7F36B]/60 text-[#15803D]">
                                            Disetujui
                                        </span>
                                    @elseif($payment->status === 'ditolak')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEE2E2] border border-[#FECACA] text-[#B91C1C]">
                                            Ditolak
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] border border-[#FDE68A] text-[#B45309]">
                                            Menunggu Verifikasi
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-5 text-right">
                                    <x-ui.button
                                        href="{{ route('owner.payments.show', $payment) }}"
                                        variant="{{ $isPending ? 'primary' : 'outline' }}"
                                        size="sm"
                                        class="text-xs"
                                    >
                                        <span>{{ $isPending ? 'Verifikasi' : 'Rincian' }}</span>
                                        <span class="text-xs">›</span>
                                    </x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 px-6 text-center">
                                    <x-ui.empty-state
                                        title="Tidak Ada Pembayaran Ditemukan"
                                        description="Belum ada transaksi pembayaran yang sesuai dengan kriteria filter atau pencarian Anda."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if($payments->hasPages())
                <div class="p-4 border-t border-[#DEDAD1] bg-[#F9F8F5]/50">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.owner>
