<x-layouts.owner title="Detail Tagihan {{ $bill->bill_number }} — HUNI" activeNav="bills">
    <div x-data="{
        showCancelModal: false,
    }" class="space-y-6">
        <!-- Breadcrumb & Header Banner Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-2">
                        <a href="{{ route('owner.dashboard') }}" class="hover:text-[#121417] transition-colors">Dashboard</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <a href="{{ route('owner.bills.index') }}" class="hover:text-[#121417] transition-colors">Invoice & Tagihan</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <span class="text-[#121417] font-semibold">{{ $bill->bill_number }}</span>
                    </nav>

                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] font-mono tracking-tight leading-none">
                            {{ $bill->bill_number }}
                        </h1>

                        <!-- Type Badge -->
                        @php
                            $typeBadgeClass = match($bill->type) {
                                \App\Enums\BillType::Sewa->value => 'bg-[#EFF6FF] text-[#1D4ED8] border-[#BFDBFE]',
                                \App\Enums\BillType::Deposit->value => 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
                                \App\Enums\BillType::Tambahan->value => 'bg-[#F3E8FF] text-[#7E22CE] border-[#E9D5FF]',
                                default => 'bg-[#F4F2EC] text-[#121417] border-[#DEDAD1]',
                            };

                            $statusBadgeClass = match($bill->status) {
                                \App\Enums\BillStatus::Lunas->value => 'bg-[#E8F8D0] text-[#15803D] border-[#C7F36B]/60',
                                \App\Enums\BillStatus::MenungguPembayaran->value => 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
                                \App\Enums\BillStatus::MenungguKonfirmasi->value => 'bg-[#EFF6FF] text-[#1D4ED8] border-[#BFDBFE]',
                                \App\Enums\BillStatus::Sebagian->value => 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
                                \App\Enums\BillStatus::LewatJatuhTempo->value => 'bg-[#FEE2E2] text-[#B91C1C] border-[#FECACA]',
                                \App\Enums\BillStatus::Dibatalkan->value => 'bg-[#F4F2EC] text-[#6B6B6B] border-[#DEDAD1]',
                                default => 'bg-white text-[#121417] border-[#DEDAD1]',
                            };
                        @endphp

                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $typeBadgeClass }}">
                            {{ $bill->typeEnum()?->label() ?? ucfirst($bill->type) }}
                        </span>

                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusBadgeClass }}">
                            ● {{ $bill->statusEnum()?->label() ?? ucfirst($bill->status) }}
                        </span>
                    </div>

                    <p class="text-xs sm:text-sm text-[#6B6B6B] mt-2">
                        Tagihan untuk <span class="text-[#121417] font-semibold">{{ $bill->room?->property?->name ?? $bill->contract?->property?->name ?? 'Kos' }}</span>,
                        Kamar <span class="text-[#121417] font-semibold">{{ $bill->room?->room_number ?? '-' }}</span> — Penyewa:
                        <span class="text-[#121417] font-semibold">{{ $bill->user?->name ?? $bill->contract?->tenant?->name ?? '-' }}</span>
                    </p>
                </div>

                <!-- Action Button Group -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <x-ui.button href="{{ route('owner.bills.index') }}" variant="outline" size="sm" class="gap-1.5">
                        <x-ui.icon name="arrow-left" class="w-4 h-4" />
                        <span>Daftar Tagihan</span>
                    </x-ui.button>

                    @if($canCancel)
                        <button
                            type="button"
                            x-on:click="showCancelModal = true"
                            class="px-3.5 py-2 rounded-xl bg-[#FEE2E2] text-[#B91C1C] border border-[#FECACA] font-semibold text-xs hover:bg-[#FECACA] transition-colors cursor-pointer flex items-center gap-1.5"
                        >
                            <x-ui.icon name="x-circle" class="w-4 h-4" />
                            <span>Batalkan Tagihan</span>
                        </button>
                    @endif
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

        @if($errors->any())
            <div class="p-4 rounded-xl bg-[#FEE2E2] border border-[#FECACA] space-y-1">
                @foreach($errors->all() as $error)
                    <div class="flex items-center gap-2 text-xs font-semibold text-[#B91C1C]">
                        <x-ui.icon name="alert-triangle" class="w-4 h-4 shrink-0" />
                        <p>{{ $error }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Total Tagihan -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Total Tagihan</span>
                    <div class="w-8 h-8 rounded-lg bg-[#F4F2EC] border border-[#DEDAD1] flex items-center justify-center text-[#121417]">
                        <x-ui.icon name="receipt" class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl font-extrabold text-[#121417] font-mono tracking-tight">
                    Rp {{ number_format((float) $bill->total_amount, 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-[#6B6B6B] mt-1">
                    {{ $bill->items->count() }} rincian item
                </p>
            </div>

            <!-- Total Terbayar -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Total Terbayar</span>
                    <div class="w-8 h-8 rounded-lg bg-[#E8F8D0] border border-[#C7F36B]/60 flex items-center justify-center text-[#15803D]">
                        <x-ui.icon name="check-circle" class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl font-extrabold text-[#15803D] font-mono tracking-tight">
                    Rp {{ number_format((float) $bill->paid_amount, 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-[#6B6B6B] mt-1">
                    @if($bill->isPaid())
                        <span class="text-[#15803D] font-semibold">Lunas sepenuhnya</span>
                    @elseif($bill->paid_amount > 0)
                        Terbayar sebagian
                    @else
                        Belum ada pembayaran
                    @endif
                </p>
            </div>

            <!-- Sisa Tagihan (Outstanding) -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Sisa Tagihan</span>
                    <div class="w-8 h-8 rounded-lg bg-[#FEF3C7] border border-[#FDE68A] flex items-center justify-center text-[#B45309]">
                        <x-ui.icon name="clock" class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl font-extrabold font-mono tracking-tight {{ $bill->outstandingAmount() > 0 ? 'text-[#B91C1C]' : 'text-[#121417]' }}">
                    Rp {{ number_format($bill->outstandingAmount(), 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-[#6B6B6B] mt-1">
                    @if($bill->isOverdue())
                        <span class="text-[#B91C1C] font-semibold">Lewat jatuh tempo!</span>
                    @elseif($bill->isPaid())
                        Tidak ada tanggungan
                    @else
                        Jatuh tempo: {{ $bill->due_date?->format('d/m/Y') ?? '-' }}
                    @endif
                </p>
            </div>
        </div>

        <!-- 2 Column Layout: Metadata & Line Items -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Details & Contract Info (1/3) -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Info Tagihan Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                    <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                        <x-ui.icon name="receipt" class="w-4 h-4 text-[#6B6B6B]" />
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#121417]">Informasi Tagihan</h2>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="font-medium text-[#6B6B6B] block">Nomor Tagihan</span>
                            <span class="font-bold font-mono text-[#121417] text-sm">{{ $bill->bill_number }}</span>
                        </div>

                        <div>
                            <span class="font-medium text-[#6B6B6B] block">Tipe Tagihan</span>
                            <span class="font-semibold text-[#121417]">{{ $bill->typeEnum()?->label() ?? ucfirst($bill->type) }}</span>
                        </div>

                        @if($bill->billing_period)
                            <div>
                                <span class="font-medium text-[#6B6B6B] block">Periode Penagihan</span>
                                <span class="font-bold text-[#121417]">{{ $bill->formattedPeriod() }}</span>
                                <span class="text-[10px] text-[#6B6B6B] block">({{ $bill->billing_period }})</span>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-[#DEDAD1]">
                            <div>
                                <span class="font-medium text-[#6B6B6B] block">Tanggal Terbit</span>
                                <span class="font-semibold text-[#121417]">{{ $bill->issue_date?->format('d M Y') ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-[#6B6B6B] block">Jatuh Tempo</span>
                                <span class="font-semibold {{ $bill->isOverdue() ? 'text-[#B91C1C]' : 'text-[#121417]' }}">
                                    {{ $bill->due_date?->format('d M Y') ?? '-' }}
                                </span>
                            </div>
                        </div>

                        @if($bill->deduplication_key)
                            <div class="pt-2 border-t border-[#DEDAD1]">
                                <span class="font-medium text-[#6B6B6B] block">Dedup Key (Idempotency)</span>
                                <code class="text-[10px] font-mono bg-[#F4F2EC] px-2 py-0.5 rounded border border-[#DEDAD1] block break-all text-[#121417] mt-1">
                                    {{ $bill->deduplication_key }}
                                </code>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Info Properti, Kamar & Penyewa Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                    <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                        <x-ui.icon name="building" class="w-4 h-4 text-[#6B6B6B]" />
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#121417]">Properti & Penyewa</h2>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="font-medium text-[#6B6B6B] block">Properti</span>
                            <span class="font-bold text-[#121417] text-sm">{{ $bill->room?->property?->name ?? $bill->contract?->property?->name ?? '-' }}</span>
                        </div>

                        <div>
                            <span class="font-medium text-[#6B6B6B] block">Nomor Kamar</span>
                            <span class="font-semibold text-[#121417]">Kamar {{ $bill->room?->room_number ?? '-' }}</span>
                        </div>

                        <div>
                            <span class="font-medium text-[#6B6B6B] block">Penyewa</span>
                            <span class="font-bold text-[#121417]">{{ $bill->user?->name ?? $bill->contract?->tenant?->name ?? '-' }}</span>
                            <span class="text-[#6B6B6B] block">{{ $bill->user?->email ?? $bill->contract?->tenant?->email ?? '-' }}</span>
                            @if($bill->user?->phone_number || $bill->contract?->tenant?->phone_number)
                                <span class="text-[#6B6B6B] block">{{ $bill->user?->phone_number ?? $bill->contract?->tenant?->phone_number }}</span>
                            @endif
                        </div>

                        @if($bill->contract)
                            <div class="pt-2 border-t border-[#DEDAD1]">
                                <span class="font-medium text-[#6B6B6B] block">Kontrak Terkait</span>
                                <a
                                    href="{{ route('owner.contracts.show', $bill->contract) }}"
                                    class="font-semibold text-[#15803D] hover:underline flex items-center gap-1 mt-0.5"
                                >
                                    <span>{{ $bill->contract->contract_number }}</span>
                                    <span class="text-[10px]">↗</span>
                                </a>
                                <span class="text-[11px] text-[#6B6B6B] block mt-0.5">
                                    Status: <span class="font-semibold text-[#121417]">{{ ucfirst($bill->contract->status) }}</span>
                                    ({{ $bill->contract->start_date?->format('d/m/Y') }} - {{ $bill->contract->end_date?->format('d/m/Y') }})
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Catatan Tagihan Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-3">
                    <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                        <x-ui.icon name="file-text" class="w-4 h-4 text-[#6B6B6B]" />
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#121417]">Catatan Tagihan</h2>
                    </div>

                    @if(!empty($bill->notes))
                        <div class="p-3 bg-[#F9F8F5] rounded-xl border border-[#DEDAD1] text-xs font-medium text-[#121417] whitespace-pre-line leading-relaxed">
                            {{ $bill->notes }}
                        </div>
                    @else
                        <p class="text-xs text-[#6B6B6B] italic">Tidak ada catatan untuk tagihan ini.</p>
                    @endif
                </div>
            </div>

            <!-- Right Column: Line Items & Payment History (2/3) -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Rincian Item Tagihan Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-5">
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-4">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="receipt" class="w-5 h-5 text-[#121417]" />
                            <h2 class="text-base font-bold text-[#121417]">Rincian Item Tagihan</h2>
                        </div>
                        <span class="text-xs font-medium text-[#6B6B6B]">
                            {{ $bill->items->count() }} Item
                        </span>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-[#DEDAD1] bg-[#F9F8F5] text-[#6B6B6B] font-bold uppercase tracking-wider">
                                    <th class="py-2.5 px-3">Deskripsi</th>
                                    <th class="py-2.5 px-3 text-right">Harga Satuan</th>
                                    <th class="py-2.5 px-3 text-center">Qty</th>
                                    <th class="py-2.5 px-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#DEDAD1]/70 text-[#121417]">
                                @forelse($bill->items as $item)
                                    <tr class="hover:bg-[#F9F8F5]/60 transition-colors">
                                        <td class="py-3 px-3 font-semibold">
                                            {{ $item->description }}
                                        </td>
                                        <td class="py-3 px-3 text-right font-mono text-[#6B6B6B]">
                                            Rp {{ number_format((float) $item->amount, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <span class="px-2 py-0.5 rounded-md bg-[#F4F2EC] border border-[#DEDAD1] font-mono text-[11px] font-semibold text-[#121417]">
                                                {{ $item->quantity }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-right font-bold font-mono text-[#121417]">
                                            Rp {{ number_format($item->total(), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-[#6B6B6B] italic">
                                            Tidak ada item pada tagihan ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-[#DEDAD1]">
                                    <td colspan="3" class="pt-3 pb-1.5 px-3 text-right font-semibold uppercase tracking-wider text-xs text-[#6B6B6B]">
                                        Total Tagihan
                                    </td>
                                    <td class="pt-3 pb-1.5 px-3 text-right font-bold font-mono text-base text-[#121417]">
                                        Rp {{ number_format((float) $bill->total_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="py-1 px-3 text-right font-semibold uppercase tracking-wider text-xs text-[#6B6B6B]">
                                        Sudah Dibayar
                                    </td>
                                    <td class="py-1 px-3 text-right font-bold font-mono text-xs text-[#15803D]">
                                        Rp {{ number_format((float) $bill->paid_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr class="border-t border-[#DEDAD1]">
                                    <td colspan="3" class="pt-2 pb-2 px-3 text-right font-bold uppercase tracking-wider text-xs text-[#121417]">
                                        Sisa Tagihan
                                    </td>
                                    <td class="pt-2 pb-2 px-3 text-right font-extrabold font-mono text-sm {{ $bill->outstandingAmount() > 0 ? 'text-[#B91C1C]' : 'text-[#121417]' }}">
                                        Rp {{ number_format($bill->outstandingAmount(), 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Riwayat Pembayaran Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-5">
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-4">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="credit-card" class="w-5 h-5 text-[#121417]" />
                            <h2 class="text-base font-bold text-[#121417]">Riwayat Pembayaran</h2>
                        </div>
                        <span class="text-xs font-medium text-[#6B6B6B]">
                            {{ $bill->payments->count() }} Transaksi
                        </span>
                    </div>

                    @if($bill->payments->isEmpty())
                        <div class="p-8 text-center bg-[#F9F8F5] rounded-xl border border-dashed border-[#DEDAD1] space-y-2">
                            <x-ui.icon name="credit-card" class="w-8 h-8 text-[#A5A096] mx-auto" />
                            <p class="text-xs font-semibold text-[#6B6B6B]">Belum ada riwayat pembayaran yang tercatat untuk tagihan ini.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-[#DEDAD1] bg-[#F9F8F5] text-[#6B6B6B] font-bold uppercase tracking-wider">
                                        <th class="py-2.5 px-3">No. Pembayaran</th>
                                        <th class="py-2.5 px-3">Metode & Ref</th>
                                        <th class="py-2.5 px-3 text-right">Nominal</th>
                                        <th class="py-2.5 px-3 text-center">Status</th>
                                        <th class="py-2.5 px-3">Waktu & Verifikasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#DEDAD1]/70 text-[#121417]">
                                    @foreach($bill->payments as $payment)
                                        @php
                                            $payBadgeClass = match($payment->status) {
                                                \App\Enums\PaymentStatus::Berhasil->value => 'bg-[#E8F8D0] text-[#15803D] border-[#C7F36B]/60',
                                                \App\Enums\PaymentStatus::MenungguVerifikasi->value => 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
                                                \App\Enums\PaymentStatus::Ditolak->value => 'bg-[#FEE2E2] text-[#B91C1C] border-[#FECACA]',
                                                \App\Enums\PaymentStatus::Dibatalkan->value => 'bg-[#F4F2EC] text-[#6B6B6B] border-[#DEDAD1]',
                                                default => 'bg-white text-[#121417] border-[#DEDAD1]',
                                            };
                                        @endphp
                                        <tr class="hover:bg-[#F9F8F5]/60 transition-colors">
                                            <td class="py-3 px-3 font-bold font-mono text-[#121417]">
                                                {{ $payment->payment_number }}
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="font-semibold block uppercase">{{ $payment->payment_method ?? 'Manual' }}</span>
                                                @if($payment->reference_number)
                                                    <span class="text-[11px] text-[#6B6B6B] font-mono block">Ref: {{ $payment->reference_number }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-3 text-right font-bold font-mono text-[#121417]">
                                                Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $payBadgeClass }}">
                                                    {{ $payment->statusEnum()?->label() ?? ucfirst($payment->status) }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-[11px]">
                                                <div class="text-[#121417]">{{ $payment->payment_date?->format('d/m/Y H:i') ?? '-' }}</div>
                                                @if($payment->verifier)
                                                    <div class="text-[10px] text-[#6B6B6B]">Diverifikasi: {{ $payment->verifier->name }}</div>
                                                @endif
                                                @if($payment->rejection_reason)
                                                    <div class="text-[10px] text-[#B91C1C] font-semibold mt-0.5">Alasan: {{ $payment->rejection_reason }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- MODAL: Pembatalan Tagihan (Cancel Modal) -->
        @if($canCancel)
            <div
                x-show="showCancelModal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
            >
                <div
                    x-on:click.away="showCancelModal = false"
                    class="bg-white border border-[#DEDAD1] rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4"
                >
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="x-circle" class="w-5 h-5 text-[#B91C1C]" />
                            <h3 class="text-base font-bold text-[#B91C1C]">Batalkan Tagihan</h3>
                        </div>
                        <button
                            type="button"
                            x-on:click="showCancelModal = false"
                            class="w-7 h-7 rounded-lg border border-[#DEDAD1] bg-white flex items-center justify-center text-xs font-semibold text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F4F2EC] cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="space-y-2 text-xs text-[#6B6B6B] leading-relaxed">
                        <p>
                            Apakah Anda yakin ingin membatalkan tagihan <strong class="text-[#121417] font-mono">{{ $bill->bill_number }}</strong>?
                        </p>
                        <p class="text-[#B91C1C] font-semibold bg-[#FEE2E2]/60 p-2.5 rounded-xl border border-[#FECACA]">
                            Perhatian: Tagihan yang dibatalkan tidak dapat dikembalikan ke status aktif. Seluruh data historis dan rincian item tetap tersimpan untuk audit finansial.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('owner.bills.cancel', $bill) }}" class="space-y-4 pt-1">
                        @csrf
                        <div>
                            <label for="cancellation_reason" class="block text-xs font-semibold text-[#121417] mb-1.5">
                                Alasan Pembatalan <span class="text-[#B91C1C]">*</span>
                            </label>
                            <textarea
                                name="cancellation_reason"
                                id="cancellation_reason"
                                rows="3"
                                required
                                minlength="5"
                                maxlength="1000"
                                placeholder="Contoh: Kesalahan penagihan item tambahan atau terjadi perubahan kesepakatan sewa."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-white text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] resize-none"
                            >{{ old('cancellation_reason') }}</textarea>
                            @error('cancellation_reason')
                                <p class="text-[11px] font-semibold text-[#B91C1C] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#DEDAD1]">
                            <x-ui.button
                                type="button"
                                x-on:click="showCancelModal = false"
                                variant="outline"
                                size="sm"
                            >
                                Tutup
                            </x-ui.button>
                            <button
                                type="submit"
                                class="px-4 py-2 rounded-xl bg-[#B91C1C] text-white font-semibold text-xs hover:bg-[#991B1B] transition-colors cursor-pointer"
                            >
                                Konfirmasi Pembatalan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-layouts.owner>
