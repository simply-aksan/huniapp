<x-layouts.owner title="Detail Pembayaran {{ $payment->payment_number }} — HUNI" activeNav="payments">
    <div x-data="{
        showApproveModal: false,
        showRejectModal: false,
    }" class="space-y-6">
        <!-- Breadcrumb & Header Banner Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-2">
                        <a href="{{ route('owner.dashboard') }}" class="hover:text-[#121417] transition-colors">Dashboard</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <a href="{{ route('owner.payments.index') }}" class="hover:text-[#121417] transition-colors">Verifikasi Pembayaran</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <span class="text-[#121417] font-semibold">{{ $payment->payment_number }}</span>
                    </nav>

                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] font-mono tracking-tight leading-none">
                            {{ $payment->payment_number }}
                        </h1>

                        <!-- Payment Status Badge -->
                        @if($payment->status === 'berhasil')
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-[#E8F8D0] text-[#15803D] border-[#C7F36B]/60">
                                ● Disetujui
                            </span>
                        @elseif($payment->status === 'ditolak')
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-[#FEE2E2] text-[#B91C1C] border-[#FECACA]">
                                ● Ditolak
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]">
                                ⏳ Menunggu Verifikasi
                            </span>
                        @endif

                        @if($bill)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-[#F4F2EC] text-[#121417] border-[#DEDAD1]">
                                {{ $bill->typeEnum()?->label() ?? ucfirst($bill->type) }}
                            </span>
                        @endif
                    </div>

                    <p class="text-xs sm:text-sm text-[#6B6B6B] mt-2">
                        Pembayaran dari <span class="text-[#121417] font-semibold">{{ $payment->user?->name ?? 'Penyewa' }}</span> untuk
                        <span class="text-[#121417] font-semibold">{{ $payment->property()?->name ?? 'Kos' }}</span>,
                        Kamar <span class="text-[#121417] font-semibold">{{ $bill?->room?->room_number ?? $bill?->contract?->room?->room_number ?? '-' }}</span>
                    </p>
                </div>

                <!-- Action Button Group -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <x-ui.button href="{{ route('owner.payments.index') }}" variant="outline" size="sm" class="gap-1.5">
                        <x-ui.icon name="arrow-left" class="w-4 h-4" />
                        <span>Daftar Pembayaran</span>
                    </x-ui.button>

                    @if($canVerify)
                        <x-ui.button
                            type="button"
                            x-on:click="showApproveModal = true"
                            variant="primary"
                            size="sm"
                            class="gap-1.5"
                        >
                            <x-ui.icon name="check-circle" class="w-4 h-4" />
                            <span>Setujui Pembayaran</span>
                        </x-ui.button>

                        <button
                            type="button"
                            x-on:click="showRejectModal = true"
                            class="px-3.5 py-2 rounded-xl bg-[#FEE2E2] text-[#B91C1C] border border-[#FECACA] font-semibold text-xs hover:bg-[#FECACA] transition-colors cursor-pointer flex items-center gap-1.5"
                        >
                            <x-ui.icon name="x-circle" class="w-4 h-4" />
                            <span>Tolak</span>
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Nominal Pembayaran Ini -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Nominal Ditransfer</span>
                    <div class="w-8 h-8 rounded-lg bg-[#E8F8D0] border border-[#C7F36B]/60 flex items-center justify-center text-[#15803D]">
                        <x-ui.icon name="wallet" class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl font-extrabold text-[#15803D] font-mono tracking-tight">
                    Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-[#6B6B6B] mt-1">
                    {{ $payment->payment_method ?? 'Transfer Bank' }}
                </p>
            </div>

            <!-- Sisa Tagihan Sebelum Verifikasi -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Sisa Tagihan Saat Ini</span>
                    <div class="w-8 h-8 rounded-lg bg-[#FEF3C7] border border-[#FDE68A] flex items-center justify-center text-[#B45309]">
                        <x-ui.icon name="clock" class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl font-extrabold font-mono tracking-tight {{ ($bill?->outstandingAmount() ?? 0) > 0 ? 'text-[#B91C1C]' : 'text-[#121417]' }}">
                    Rp {{ number_format($bill?->outstandingAmount() ?? 0, 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-[#6B6B6B] mt-1">
                    Sebelum pembayaran ini diverifikasi
                </p>
            </div>

            <!-- Total Nilai Tagihan -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Total Invoice</span>
                    <div class="w-8 h-8 rounded-lg bg-[#F4F2EC] border border-[#DEDAD1] flex items-center justify-center text-[#121417]">
                        <x-ui.icon name="receipt" class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl font-extrabold text-[#121417] font-mono tracking-tight">
                    Rp {{ number_format((float) ($bill?->total_amount ?? 0), 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-[#6B6B6B] mt-1">
                    {{ $bill?->items->count() ?? 0 }} rincian tagihan
                </p>
            </div>

            <!-- Sudah Terbayar Sebelumnya -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-[#6B6B6B]">Total Terbayar</span>
                    <div class="w-8 h-8 rounded-lg bg-[#F4F2EC] border border-[#DEDAD1] flex items-center justify-center text-[#121417]">
                        <x-ui.icon name="credit-card" class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl font-extrabold text-[#121417] font-mono tracking-tight">
                    Rp {{ number_format((float) ($bill?->paid_amount ?? 0), 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-[#6B6B6B] mt-1">
                    @if($bill?->isPaid())
                        <span class="text-[#15803D] font-semibold">Lunas sepenuhnya</span>
                    @else
                        Status: {{ $bill?->statusEnum()?->label() ?? '-' }}
                    @endif
                </p>
            </div>
        </div>

        <!-- 2 Column Layout: Details & History -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Tenant & Bill Summary (1/3) -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Data Penyewa Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                    <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                        <x-ui.icon name="user" class="w-4 h-4 text-[#6B6B6B]" />
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#121417]">Identitas Penyewa</h2>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="font-medium text-[#6B6B6B] block">Nama Lengkap</span>
                            <span class="font-bold text-[#121417] text-sm">{{ $payment->user?->name ?? 'Penyewa' }}</span>
                        </div>

                        <div>
                            <span class="font-medium text-[#6B6B6B] block">Email</span>
                            <span class="font-semibold text-[#121417]">{{ $payment->user?->email ?? '-' }}</span>
                        </div>

                        <div>
                            <span class="font-medium text-[#6B6B6B] block">Nomor Telepon</span>
                            <span class="font-semibold text-[#121417]">{{ $payment->user?->phone ?? '-' }}</span>
                        </div>

                        @if($payment->user?->occupation)
                            <div>
                                <span class="font-medium text-[#6B6B6B] block">Pekerjaan</span>
                                <span class="font-semibold text-[#121417]">{{ $payment->user->occupation }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Properti & Kamar Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                    <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                        <x-ui.icon name="building" class="w-4 h-4 text-[#6B6B6B]" />
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#121417]">Properti & Kamar</h2>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="font-medium text-[#6B6B6B] block">Properti</span>
                            <span class="font-bold text-[#121417] text-sm">{{ $payment->property()?->name ?? '-' }}</span>
                            <span class="text-[11px] text-[#6B6B6B] block">{{ $payment->property()?->address ?? '' }}</span>
                        </div>

                        <div>
                            <span class="font-medium text-[#6B6B6B] block">Nomor Kamar</span>
                            <span class="font-semibold text-[#121417]">
                                Kamar {{ $bill?->room?->room_number ?? $bill?->contract?->room?->room_number ?? '-' }}
                                @if($bill?->room?->floor)
                                    (Lantai {{ $bill->room->floor }})
                                @endif
                            </span>
                        </div>

                        @if($bill?->contract)
                            <div class="pt-2 border-t border-[#DEDAD1]">
                                <span class="font-medium text-[#6B6B6B] block">Kontrak Terkait</span>
                                <a
                                    href="{{ route('owner.contracts.show', $bill->contract) }}"
                                    class="font-semibold text-[#15803D] hover:underline flex items-center gap-1 mt-0.5"
                                >
                                    <span>{{ $bill->contract->contract_number }}</span>
                                    <span class="text-[10px]">↗</span>
                                </a>
                                @if((float) $bill->contract->deposit_amount > 0)
                                    <span class="text-[11px] text-[#6B6B6B] block mt-1">
                                        Deposit: <strong class="text-[#121417] font-mono">Rp {{ number_format((float) $bill->contract->deposit_amount, 0, ',', '.') }}</strong>
                                        (Status: <span class="font-semibold {{ $bill->contract->deposit_status === 'lunas' ? 'text-[#15803D]' : 'text-[#B45309]' }}">{{ $bill->contract->deposit_status }}</span>)
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Ringkasan Invoice Card -->
                @if($bill)
                    <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                        <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                            <div class="flex items-center gap-2">
                                <x-ui.icon name="receipt" class="w-4 h-4 text-[#6B6B6B]" />
                                <h2 class="text-xs font-bold uppercase tracking-wider text-[#121417]">Informasi Invoice</h2>
                            </div>
                            <a href="{{ route('owner.bills.show', $bill) }}" class="text-xs font-semibold text-[#15803D] hover:underline flex items-center gap-0.5">
                                <span>Lihat</span>
                                <span>↗</span>
                            </a>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div>
                                <span class="font-medium text-[#6B6B6B] block">Nomor Invoice</span>
                                <span class="font-bold font-mono text-[#121417]">{{ $bill->bill_number }}</span>
                            </div>

                            <div>
                                <span class="font-medium text-[#6B6B6B] block">Periode Tagihan</span>
                                <span class="font-bold text-[#121417]">{{ $bill->formattedPeriod() }}</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-[#DEDAD1]">
                                <div>
                                    <span class="font-medium text-[#6B6B6B] block">Tgl Terbit</span>
                                    <span class="font-semibold text-[#121417]">{{ $bill->issue_date?->format('d/m/Y') ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-[#6B6B6B] block">Jatuh Tempo</span>
                                    <span class="font-semibold text-[#121417]">{{ $bill->due_date?->format('d/m/Y') ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Catatan Penyewa Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-3">
                    <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                        <x-ui.icon name="message-square" class="w-4 h-4 text-[#6B6B6B]" />
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#121417]">Catatan Penyewa</h2>
                    </div>

                    @if(!empty($payment->notes))
                        <div class="p-3 bg-[#F9F8F5] rounded-xl border border-[#DEDAD1] text-xs font-medium text-[#121417] whitespace-pre-line leading-relaxed">
                            {{ $payment->notes }}
                        </div>
                    @else
                        <p class="text-xs text-[#6B6B6B] italic">Penyewa tidak menyertakan catatan tambahan.</p>
                    @endif
                </div>
            </div>

            <!-- Right Column: Verification, Proofs & Other Payments (2/3) -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Detail Transaksi & Status Verifikasi Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-5">
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-4">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="credit-card" class="w-5 h-5 text-[#121417]" />
                            <h2 class="text-base font-bold text-[#121417]">Rincian Transaksi Pembayaran</h2>
                        </div>
                        <span class="text-xs font-mono font-bold text-[#6B6B6B]">
                            {{ $payment->payment_number }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="p-3.5 bg-[#F9F8F5] rounded-xl border border-[#DEDAD1] space-y-1">
                            <span class="text-[11px] font-semibold text-[#6B6B6B] block">Metode Pembayaran</span>
                            <span class="text-sm font-bold text-[#121417] block">{{ $payment->payment_method ?? 'Transfer Bank' }}</span>
                        </div>

                        <div class="p-3.5 bg-[#F9F8F5] rounded-xl border border-[#DEDAD1] space-y-1">
                            <span class="text-[11px] font-semibold text-[#6B6B6B] block">Nomor Referensi Transfer</span>
                            <span class="text-sm font-bold font-mono text-[#121417] block">{{ $payment->reference_number ?? '-' }}</span>
                        </div>

                        <div class="p-3.5 bg-[#F9F8F5] rounded-xl border border-[#DEDAD1] space-y-1">
                            <span class="text-[11px] font-semibold text-[#6B6B6B] block">Tanggal & Waktu Transfer</span>
                            <span class="text-sm font-semibold text-[#121417] block">{{ $payment->payment_date?->format('d F Y, H:i') ?? '-' }}</span>
                        </div>

                        <div class="p-3.5 bg-[#F9F8F5] rounded-xl border border-[#DEDAD1] space-y-1">
                            <span class="text-[11px] font-semibold text-[#6B6B6B] block">Status Verifikasi</span>
                            <div class="flex items-center gap-2 mt-0.5">
                                @if($payment->status === 'berhasil')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-[#E8F8D0] border border-[#C7F36B]/60 text-[#15803D]">Disetujui</span>
                                @elseif($payment->status === 'ditolak')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-[#FEE2E2] border border-[#FECACA] text-[#B91C1C]">Ditolak</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-[#FEF3C7] border border-[#FDE68A] text-[#B45309]">Menunggu Verifikasi</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Audit Trail / Verifier Record -->
                    @if($payment->verified_by || $payment->verified_at)
                        <div class="p-4 rounded-xl border {{ $payment->status === 'berhasil' ? 'bg-[#E8F8D0]/30 border-[#C7F36B]/60' : 'bg-[#FEE2E2]/30 border-[#FECACA]' }} space-y-1 text-xs">
                            <p class="font-bold text-[#121417]">
                                {{ $payment->status === 'berhasil' ? 'Telah Disetujui' : 'Telah Ditolak' }}
                                oleh: <span class="underline">{{ $payment->verifier?->name ?? 'Pemilik Kos' }}</span>
                            </p>
                            <p class="text-[#6B6B6B] text-[11px]">
                                Waktu: {{ $payment->verified_at?->format('d/m/Y H:i:s') ?? '-' }}
                            </p>
                            @if($payment->rejection_reason)
                                <div class="mt-2 p-3 bg-white border border-[#DEDAD1] rounded-lg">
                                    <span class="text-[11px] font-semibold text-[#B91C1C] block">Alasan Penolakan:</span>
                                    <p class="text-xs font-medium text-[#121417] mt-0.5">{{ $payment->rejection_reason }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Bukti Pembayaran Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-5">
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-4">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="file-text" class="w-5 h-5 text-[#121417]" />
                            <h2 class="text-base font-bold text-[#121417]">Berkas Bukti Pembayaran</h2>
                        </div>
                        <span class="text-xs font-medium text-[#6B6B6B]">
                            {{ $payment->proofs->count() }} Berkas Terlampir
                        </span>
                    </div>

                    @if($payment->proofs->isEmpty())
                        <div class="p-6 text-center bg-[#F9F8F5] rounded-xl border border-dashed border-[#DEDAD1]">
                            <p class="text-xs font-medium text-[#6B6B6B]">Tidak ada bukti transfer yang dilampirkan pada transaksi ini.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            @foreach($payment->proofs as $proof)
                                @php
                                    $isImage = str_starts_with((string)$proof->mime_type, 'image/');
                                    $sizeInKb = round($proof->file_size / 1024, 1);
                                @endphp
                                <div class="bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl p-4 flex flex-col justify-between gap-3 shadow-[0_1px_2px_rgba(18,20,23,0.02)]">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-white border border-[#DEDAD1] flex items-center justify-center shrink-0 text-[#121417]">
                                            <x-ui.icon name="{{ $isImage ? 'image' : 'file-text' }}" class="w-5 h-5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-[#121417] truncate leading-tight" title="{{ $proof->file_name }}">
                                                {{ $proof->file_name }}
                                            </p>
                                            <p class="text-[11px] text-[#6B6B6B] mt-0.5">
                                                {{ $sizeInKb }} KB • {{ $proof->mime_type }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="pt-2 border-t border-[#DEDAD1] flex items-center justify-between gap-2">
                                        <a
                                            href="{{ route('owner.payments.proofs.download', [$payment, $proof]) }}"
                                            class="w-full py-2 px-3 rounded-lg bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] hover:bg-[#EAE6DF] transition-colors text-center flex items-center justify-center gap-1.5 shadow-[0_1px_2px_rgba(18,20,23,0.02)]"
                                        >
                                            <x-ui.icon name="download" class="w-3.5 h-3.5" />
                                            <span>Unduh Bukti</span>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Riwayat Pembayaran Lain pada Tagihan yang Sama -->
                @if($bill && $bill->payments->where('id', '!=', $payment->id)->isNotEmpty())
                    <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                        <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                            <div class="flex items-center gap-2">
                                <x-ui.icon name="refresh" class="w-4 h-4 text-[#6B6B6B]" />
                                <h2 class="text-xs font-bold uppercase tracking-wider text-[#121417]">Pembayaran Lain pada Invoice Ini</h2>
                            </div>
                            <span class="text-xs font-medium text-[#6B6B6B]">
                                {{ $bill->payments->where('id', '!=', $payment->id)->count() }} Transaksi
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-[#DEDAD1] bg-[#F9F8F5] text-[#6B6B6B] font-bold uppercase tracking-wider">
                                        <th class="py-2 px-3">No. Transaksi</th>
                                        <th class="py-2 px-3">Tanggal</th>
                                        <th class="py-2 px-3 text-right">Nominal</th>
                                        <th class="py-2 px-3 text-center">Status</th>
                                        <th class="py-2 px-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#DEDAD1]/70 text-[#121417]">
                                    @foreach($bill->payments->where('id', '!=', $payment->id) as $otherPay)
                                        <tr class="hover:bg-[#F9F8F5]/60">
                                            <td class="py-2.5 px-3 font-mono font-semibold">{{ $otherPay->payment_number }}</td>
                                            <td class="py-2.5 px-3 text-[#6B6B6B]">{{ $otherPay->payment_date?->format('d/m/Y') ?? $otherPay->created_at->format('d/m/Y') }}</td>
                                            <td class="py-2.5 px-3 text-right font-bold font-mono">Rp {{ number_format((float) $otherPay->amount, 0, ',', '.') }}</td>
                                            <td class="py-2.5 px-3 text-center">
                                                @if($otherPay->status === 'berhasil')
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#E8F8D0] text-[#15803D]">Disetujui</span>
                                                @elseif($otherPay->status === 'ditolak')
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#FEE2E2] text-[#B91C1C]">Ditolak</span>
                                                @else
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#FEF3C7] text-[#B45309]">Pending</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3 text-right">
                                                <a href="{{ route('owner.payments.show', $otherPay) }}" class="font-semibold text-[#15803D] hover:underline">
                                                    Lihat ›
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Action Card: Approve or Reject (When Pending) -->
                @if($canVerify)
                    <div class="bg-[#F9F8F5] border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#C7F36B]/20 border border-[#C7F36B]/60 flex items-center justify-center shrink-0">
                                <x-ui.icon name="check-circle" class="w-5 h-5 text-[#15803D]" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-[#121417]">Keputusan Verifikasi Pembayaran</h3>
                                <p class="text-xs text-[#6B6B6B]">
                                    Pastikan mutasi dana telah masuk ke rekening Anda sebelum menyetujui pembayaran ini.
                                </p>
                            </div>
                        </div>

                        <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                            <x-ui.button
                                type="button"
                                x-on:click="showApproveModal = true"
                                variant="primary"
                                class="w-full sm:w-auto gap-2"
                            >
                                <x-ui.icon name="check-circle" class="w-4 h-4" />
                                <span>Setujui Pembayaran (Rp {{ number_format((float) $payment->amount, 0, ',', '.') }})</span>
                            </x-ui.button>

                            <button
                                type="button"
                                x-on:click="showRejectModal = true"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-[#FEE2E2] border border-[#FECACA] text-xs font-semibold text-[#B91C1C] hover:bg-[#FECACA] transition-colors cursor-pointer flex items-center justify-center gap-2"
                            >
                                <x-ui.icon name="x-circle" class="w-4 h-4" />
                                <span>Tolak Pembayaran</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- MODAL: Approve Confirmation Modal -->
        @if($canVerify)
            <div
                x-show="showApproveModal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
            >
                <div
                    x-on:click.away="showApproveModal = false"
                    class="bg-white border border-[#DEDAD1] rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4"
                >
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="check-circle" class="w-5 h-5 text-[#15803D]" />
                            <h3 class="text-base font-bold text-[#121417]">Konfirmasi Persetujuan</h3>
                        </div>
                        <button
                            type="button"
                            x-on:click="showApproveModal = false"
                            class="w-7 h-7 rounded-lg border border-[#DEDAD1] bg-white flex items-center justify-center text-xs font-semibold text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F4F2EC] cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="space-y-3 text-xs text-[#121417] leading-relaxed">
                        <p>
                            Anda akan menyetujui pembayaran <strong class="font-mono">{{ $payment->payment_number }}</strong> sejumlah:
                        </p>
                        <div class="p-4 bg-[#E8F8D0]/40 border border-[#C7F36B]/60 rounded-xl text-center">
                            <span class="text-2xl font-extrabold font-mono text-[#15803D]">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</span>
                        </div>
                        <p class="text-[#6B6B6B]">
                            Saldo tagihan akan diperbarui secara otomatis. Jika pembayaran ini melunasi seluruh tagihan, status invoice akan berubah menjadi <strong>Lunas</strong>.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('owner.payments.approve', $payment) }}" class="pt-3 border-t border-[#DEDAD1] flex items-center justify-end gap-2.5">
                        @csrf
                        <x-ui.button
                            type="button"
                            x-on:click="showApproveModal = false"
                            variant="outline"
                            size="sm"
                        >
                            Batal
                        </x-ui.button>
                        <x-ui.button
                            type="submit"
                            variant="primary"
                            size="sm"
                        >
                            Ya, Setujui Sekarang
                        </x-ui.button>
                    </form>
                </div>
            </div>

            <!-- MODAL: Reject Modal with Reason Input -->
            <div
                x-show="showRejectModal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
            >
                <div
                    x-on:click.away="showRejectModal = false"
                    class="bg-white border border-[#DEDAD1] rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4"
                >
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="x-circle" class="w-5 h-5 text-[#B91C1C]" />
                            <h3 class="text-base font-bold text-[#B91C1C]">Tolak Pembayaran</h3>
                        </div>
                        <button
                            type="button"
                            x-on:click="showRejectModal = false"
                            class="w-7 h-7 rounded-lg border border-[#DEDAD1] bg-white flex items-center justify-center text-xs font-semibold text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F4F2EC] cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <p class="text-xs text-[#6B6B6B]">
                        Berikan alasan penolakan yang jelas agar penyewa memahami dan dapat mengunggah bukti pembayaran baru yang valid.
                    </p>

                    <form method="POST" action="{{ route('owner.payments.reject', $payment) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="rejection_reason" class="block text-xs font-semibold text-[#121417] mb-1.5">
                                Alasan Penolakan <span class="text-[#B91C1C]">*</span>
                            </label>
                            <textarea
                                name="rejection_reason"
                                id="rejection_reason"
                                rows="3"
                                required
                                minlength="5"
                                maxlength="1000"
                                placeholder="Contoh: Bukti mutasi tidak terbaca atau nominal tidak sesuai dengan rekening koran."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-white text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] resize-none"
                            >{{ old('rejection_reason') }}</textarea>
                            @error('rejection_reason')
                                <p class="text-[11px] font-semibold text-[#B91C1C] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#DEDAD1]">
                            <x-ui.button
                                type="button"
                                x-on:click="showRejectModal = false"
                                variant="outline"
                                size="sm"
                            >
                                Batal
                            </x-ui.button>
                            <button
                                type="submit"
                                class="px-4 py-2 rounded-xl bg-[#B91C1C] text-white font-semibold text-xs hover:bg-[#991B1B] transition-colors cursor-pointer"
                            >
                                Konfirmasi Penolakan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-layouts.owner>
