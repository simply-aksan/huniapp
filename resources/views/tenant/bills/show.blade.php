<x-layouts.tenant title="Detail Tagihan {{ $bill->bill_number }} — HUNI" activeNav="bills">
    <div class="space-y-6">
        <!-- Header Banner Card -->
        <div class="relative bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 text-xs font-medium text-[#5E6470] mb-2">
                        <a href="{{ route('tenant.dashboard') }}" class="hover:text-[#121417] transition-colors">Beranda</a>
                        <span>›</span>
                        <a href="{{ route('tenant.bills.index') }}" class="hover:text-[#121417] transition-colors">Tagihan Saya</a>
                        <span>›</span>
                        <span class="text-[#121417] font-semibold">{{ $bill->bill_number }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl sm:text-3xl font-bold font-mono text-[#121417] tracking-tight">
                            {{ $bill->bill_number }}
                        </h1>

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
                        @endphp

                        <x-ui.badge :variant="$typeBadgeVariant">
                            {{ $bill->typeEnum()?->label() ?? ucfirst($bill->type) }}
                        </x-ui.badge>

                        <x-ui.badge :variant="$statusBadgeVariant">
                            {{ $bill->statusEnum()?->label() ?? ucfirst($bill->status) }}
                        </x-ui.badge>
                    </div>

                    <p class="text-sm text-[#5E6470] mt-1.5">
                        <span class="text-[#121417] font-semibold">{{ $bill->room?->property?->name ?? $bill->contract?->property?->name ?? 'Kos' }}</span> —
                        Kamar <span class="text-[#121417] font-mono font-semibold">{{ $bill->room?->room_number ?? '-' }}</span>
                        @if($bill->billing_period)
                            • Periode Penagihan: <span class="text-[#121417] font-medium">{{ $bill->formattedPeriod() }}</span>
                        @endif
                    </p>
                </div>

                <!-- Back Action -->
                <div>
                    <a
                        href="{{ route('tenant.bills.index') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-[#DEDAD1] bg-white text-xs font-semibold text-[#121417] hover:bg-[#F4F1EA] transition-all cursor-pointer shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                    >
                        <x-ui.icon name="arrow-left" class="w-3.5 h-3.5 text-[#5E6470]" />
                        <span>Kembali ke Daftar Tagihan</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <x-ui.alert type="success">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if(session('error'))
            <x-ui.alert type="error">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        @if($errors->any())
            <x-ui.alert type="error">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Total Tagihan -->
            <div class="bg-white border border-[#DEDAD1] rounded-xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#5E6470]">Total Tagihan</span>
                    <span class="w-8 h-8 rounded-lg bg-[#F4F1EA] text-[#5E6470] flex items-center justify-center">
                        <x-ui.icon name="document-text" class="w-4 h-4" />
                    </span>
                </div>
                <p class="text-2xl font-bold font-mono text-[#121417]">
                    Rp {{ number_format((float) $bill->total_amount, 0, ',', '.') }}
                </p>
                <p class="text-xs text-[#5E6470] mt-1 font-mono">
                    {{ $bill->items->count() }} rincian item
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
                    Rp {{ number_format((float) $bill->paid_amount, 0, ',', '.') }}
                </p>
                <p class="text-xs text-[#5E6470] mt-1">
                    @if($bill->isPaid())
                        Lunas sepenuhnya
                    @elseif($bill->paid_amount > 0)
                        Terbayar sebagian
                    @else
                        Belum ada pembayaran diverifikasi
                    @endif
                </p>
            </div>

            <!-- Sisa Tagihan (Outstanding) -->
            <div class="bg-white border border-[#DEDAD1] rounded-xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#5E6470]">Sisa Tagihan</span>
                    <span class="w-8 h-8 rounded-lg bg-[#FEF3C7] border border-[#FDE68A] flex items-center justify-center text-amber-700">
                        <x-ui.icon name="clock" class="w-4 h-4" />
                    </span>
                </div>
                <p class="text-2xl font-bold font-mono {{ $outstandingAmount > 0 ? 'text-[#B91C1C]' : 'text-[#121417]' }}">
                    Rp {{ number_format($outstandingAmount, 0, ',', '.') }}
                </p>
                <p class="text-xs text-[#5E6470] mt-1">
                    @if($bill->isOverdue())
                        <span class="text-[#B91C1C] font-semibold">Lewat jatuh tempo!</span>
                    @elseif($bill->isPaid())
                        Tidak ada tunggakan
                    @else
                        Jatuh tempo: <span class="font-mono">{{ $bill->due_date?->format('d/m/Y') ?? '-' }}</span>
                    @endif
                </p>
            </div>
        </div>

        <!-- 2 Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Details & Instructions (1/3) -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Info Tagihan Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                    <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                        <x-ui.icon name="document-text" class="w-4 h-4 text-[#5E6470]" />
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#121417]">Informasi Tagihan</h2>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="font-medium text-[#5E6470] block">Nomor Tagihan</span>
                            <span class="font-bold font-mono text-[#121417] text-sm">{{ $bill->bill_number }}</span>
                        </div>

                        <div>
                            <span class="font-medium text-[#5E6470] block">Tipe Tagihan</span>
                            <span class="font-semibold text-[#121417]">{{ $bill->typeEnum()?->label() ?? ucfirst($bill->type) }}</span>
                        </div>

                        @if($bill->billing_period)
                            <div>
                                <span class="font-medium text-[#5E6470] block">Periode Penagihan</span>
                                <span class="font-semibold text-[#121417]">{{ $bill->formattedPeriod() }}</span>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-[#DEDAD1]">
                            <div>
                                <span class="font-medium text-[#5E6470] block">Tanggal Terbit</span>
                                <span class="font-semibold text-[#121417] font-mono">{{ $bill->issue_date?->format('d M Y') ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-[#5E6470] block">Jatuh Tempo</span>
                                <span class="font-semibold font-mono {{ $bill->isOverdue() ? 'text-[#B91C1C]' : 'text-[#121417]' }}">
                                    {{ $bill->due_date?->format('d M Y') ?? '-' }}
                                </span>
                            </div>
                        </div>

                        @if(!empty($bill->notes))
                            <div class="pt-2 border-t border-[#DEDAD1]">
                                <span class="font-medium text-[#5E6470] block mb-1">Catatan Pemilik:</span>
                                <div class="p-2.5 bg-[#F9F8F5] rounded-lg border border-[#DEDAD1] text-xs text-[#121417] whitespace-pre-line">
                                    {{ $bill->notes }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Info Properti & Kamar Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                    <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                        <x-ui.icon name="home" class="w-4 h-4 text-[#5E6470]" />
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#121417]">Properti Kos</h2>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="font-medium text-[#5E6470] block">Nama Kos</span>
                            <span class="font-semibold text-[#121417] text-sm">{{ $bill->room?->property?->name ?? $bill->contract?->property?->name ?? '-' }}</span>
                        </div>

                        <div>
                            <span class="font-medium text-[#5E6470] block">Nomor Kamar</span>
                            <span class="font-semibold font-mono text-[#121417]">Kamar {{ $bill->room?->room_number ?? '-' }}</span>
                        </div>

                        @php
                            $owner = $bill->room?->property?->owner ?? $bill->contract?->property?->owner;
                        @endphp
                        @if($owner)
                            <div class="pt-2 border-t border-[#DEDAD1]">
                                <span class="font-medium text-[#5E6470] block">Pemilik Kos</span>
                                <span class="font-semibold text-[#121417]">{{ $owner->name }}</span>
                                @if($owner->phone)
                                    <span class="text-[#5E6470] block mt-0.5 font-mono">Telp: {{ $owner->phone }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Petunjuk Pembayaran Card -->
                <div class="bg-[#EFF6FF] border border-[#BFDBFE] rounded-xl p-5 space-y-3">
                    <div class="flex items-center gap-2 border-b border-[#BFDBFE] pb-2">
                        <x-ui.icon name="information-circle" class="w-4 h-4 text-blue-700" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-blue-900">Petunjuk Pembayaran</h3>
                    </div>

                    <ol class="list-decimal list-inside text-xs text-blue-950 space-y-1.5 leading-relaxed font-normal">
                        <li>Pastikan nominal transfer sesuai atau tidak melebihi sisa tagihan.</li>
                        <li>Gunakan rekening atau metode pembayaran resmi kos.</li>
                        <li>Unggah foto/tangkapan layar bukti transfer yang jelas (JPEG, PNG, atau PDF).</li>
                        <li>Tunggu verifikasi dari pemilik kos dalam 1x24 jam.</li>
                    </ol>
                </div>
            </div>

            <!-- Right Column: Line Items, Submission Form, History (2/3) -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Rincian Item Tagihan Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="document-text" class="w-4 h-4 text-[#5E6470]" />
                            <h2 class="text-sm font-bold text-[#121417]">Rincian Item Tagihan</h2>
                        </div>
                        <span class="text-xs font-mono text-[#5E6470]">
                            {{ $bill->items->count() }} Item
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-[#DEDAD1] bg-[#F9F8F5] text-[#5E6470] font-semibold uppercase tracking-wider text-[11px]">
                                    <th class="py-2.5 px-3">Deskripsi</th>
                                    <th class="py-2.5 px-3 text-right">Harga Satuan</th>
                                    <th class="py-2.5 px-3 text-center">Qty</th>
                                    <th class="py-2.5 px-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#DEDAD1] font-normal text-[#121417]">
                                @foreach($bill->items as $item)
                                    <tr class="hover:bg-[#F9F8F5]/60 transition-colors">
                                        <td class="py-3 px-3 font-medium text-[#121417]">
                                            {{ $item->description }}
                                        </td>
                                        <td class="py-3 px-3 text-right font-mono text-[#5E6470]">
                                            Rp {{ number_format((float) $item->amount, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <span class="px-2 py-0.5 rounded-full bg-[#F4F1EA] text-[#121417] font-mono text-xs font-medium">
                                                {{ $item->quantity }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-right font-mono font-semibold text-[#121417]">
                                            Rp {{ number_format($item->total(), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-[#DEDAD1]">
                                <tr>
                                    <td colspan="3" class="pt-3 pb-1.5 px-3 text-right font-semibold uppercase tracking-wider text-[11px] text-[#5E6470]">
                                        Total Tagihan
                                    </td>
                                    <td class="pt-3 pb-1.5 px-3 text-right font-mono font-bold text-sm text-[#121417]">
                                        Rp {{ number_format((float) $bill->total_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="py-1 px-3 text-right font-semibold uppercase tracking-wider text-[11px] text-[#5E6470]">
                                        Sudah Dibayar (Terverifikasi)
                                    </td>
                                    <td class="py-1 px-3 text-right font-mono font-semibold text-xs text-[#15803D]">
                                        Rp {{ number_format((float) $bill->paid_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr class="border-t border-[#DEDAD1]">
                                    <td colspan="3" class="pt-2 pb-2 px-3 text-right font-bold uppercase tracking-wider text-[11px] text-[#121417]">
                                        Sisa Kewajiban
                                    </td>
                                    <td class="pt-2 pb-2 px-3 text-right font-mono font-bold text-sm {{ $outstandingAmount > 0 ? 'text-[#B91C1C]' : 'text-[#121417]' }}">
                                        Rp {{ number_format($outstandingAmount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Form Pembayaran / Status Alert Section -->
                @if($hasPendingPayment)
                    <!-- Pending Verification Notice -->
                    <div class="bg-[#FEF3C7] border border-[#FDE68A] rounded-xl p-5 space-y-2">
                        <div class="flex items-center gap-2.5">
                            <x-ui.icon name="clock" class="w-5 h-5 text-amber-700 shrink-0" />
                            <div>
                                <h3 class="text-sm font-bold text-amber-900">Pembayaran Sedang Diverifikasi</h3>
                                <p class="text-xs text-amber-800 mt-0.5">
                                    Anda telah mengirimkan bukti pembayaran yang saat ini sedang ditinjau oleh pemilik kos.
                                </p>
                            </div>
                        </div>
                        <p class="text-[11px] text-amber-700 pt-1">
                            Pengajuan pembayaran baru dapat dilakukan kembali setelah pembayaran sebelumnya diverifikasi atau diselesaikan oleh pemilik kos.
                        </p>
                    </div>
                @elseif($bill->isPaid())
                    <!-- Paid Notice -->
                    <div class="bg-[#E8F5E9] border border-[#C8E6C9] rounded-xl p-5 flex items-center gap-3">
                        <x-ui.icon name="check-circle" class="w-6 h-6 text-[#15803D] shrink-0" />
                        <div>
                            <h3 class="text-sm font-bold text-green-900">Tagihan Ini Telah Lunas</h3>
                            <p class="text-xs text-green-800 mt-0.5">
                                Seluruh kewajiban pembayaran untuk tagihan ini telah selesai diverifikasi. Terima kasih telah membayar tepat waktu!
                            </p>
                        </div>
                    </div>
                @elseif($bill->isCancelled())
                    <!-- Cancelled Notice -->
                    <div class="bg-[#F4F1EA] border border-[#DEDAD1] rounded-xl p-5 flex items-center gap-3">
                        <x-ui.icon name="x-circle" class="w-6 h-6 text-[#5E6470] shrink-0" />
                        <div>
                            <h3 class="text-sm font-bold text-[#121417]">Tagihan Telah Dibatalkan</h3>
                            <p class="text-xs text-[#5E6470] mt-0.5">
                                Tagihan ini telah dibatalkan oleh pemilik kos dan tidak menerima pembayaran lagi.
                            </p>
                        </div>
                    </div>
                @elseif($canPay)
                    <!-- Active Payment Submission Form -->
                    <div class="bg-white border border-[#DEDAD1] rounded-xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-5">
                        <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                            <div class="flex items-center gap-2">
                                <x-ui.icon name="credit-card" class="w-4 h-4 text-[#15803D]" />
                                <div>
                                    <h2 class="text-sm font-bold text-[#121417]">Kirim Bukti Pembayaran</h2>
                                    <p class="text-xs text-[#5E6470]">
                                        Sisa tagihan: <strong class="text-[#B91C1C] font-mono">Rp {{ number_format($outstandingAmount, 0, ',', '.') }}</strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('tenant.bills.payments.store', $bill) }}"
                            enctype="multipart/form-data"
                            class="space-y-4"
                        >
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Nominal Pembayaran -->
                                <div>
                                    <label for="amount" class="block text-xs font-semibold uppercase tracking-wider text-[#121417] mb-1.5">
                                        Nominal Pembayaran (Rp) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        name="amount"
                                        id="amount"
                                        value="{{ old('amount', $outstandingAmount) }}"
                                        min="1"
                                        max="{{ $outstandingAmount }}"
                                        step="1"
                                        required
                                        class="w-full px-3.5 py-2 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-sm font-bold font-mono text-[#121417] focus:outline-none focus:bg-white focus:border-[#121417] transition-all"
                                    />
                                    <span class="text-[11px] text-[#5E6470] mt-1 block font-mono">
                                        Maksimal sesuai sisa tagihan: Rp {{ number_format($outstandingAmount, 0, ',', '.') }}
                                    </span>
                                    @error('amount')
                                        <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Metode Pembayaran -->
                                <div>
                                    <label for="payment_method" class="block text-xs font-semibold uppercase tracking-wider text-[#121417] mb-1.5">
                                        Metode Pembayaran <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        name="payment_method"
                                        id="payment_method"
                                        required
                                        class="w-full px-3.5 py-2 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-none focus:bg-white focus:border-[#121417] transition-all"
                                    >
                                        <option value="Transfer Bank" @selected(old('payment_method') === 'Transfer Bank')>Transfer Bank</option>
                                        <option value="QRIS" @selected(old('payment_method') === 'QRIS')>QRIS</option>
                                        <option value="E-Wallet" @selected(old('payment_method') === 'E-Wallet')>E-Wallet (GoPay / OVO / Dana)</option>
                                        <option value="Tunai" @selected(old('payment_method') === 'Tunai')>Tunai (Cash)</option>
                                    </select>
                                    @error('payment_method')
                                        <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Tanggal Pembayaran -->
                                <div>
                                    <label for="payment_date" class="block text-xs font-semibold uppercase tracking-wider text-[#121417] mb-1.5">
                                        Tanggal Pembayaran <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        name="payment_date"
                                        id="payment_date"
                                        value="{{ old('payment_date', now()->toDateString()) }}"
                                        required
                                        class="w-full px-3.5 py-2 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-none focus:bg-white focus:border-[#121417] transition-all font-mono"
                                    />
                                    @error('payment_date')
                                        <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Nomor Referensi / Pengirim -->
                                <div>
                                    <label for="reference_number" class="block text-xs font-semibold uppercase tracking-wider text-[#121417] mb-1.5">
                                        No. Referensi / Rekening (Opsional)
                                    </label>
                                    <input
                                        type="text"
                                        name="reference_number"
                                        id="reference_number"
                                        value="{{ old('reference_number') }}"
                                        placeholder="Contoh: BCA 123456 a/n Budi"
                                        class="w-full px-3.5 py-2 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-none focus:bg-white focus:border-[#121417] transition-all font-mono"
                                    />
                                    @error('reference_number')
                                        <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Catatan Pembayaran -->
                            <div>
                                <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-[#121417] mb-1.5">
                                    Catatan Tambahan (Opsional)
                                </label>
                                <textarea
                                    name="notes"
                                    id="notes"
                                    rows="2"
                                    placeholder="Tulis catatan atau pesan singkat untuk pemilik kos..."
                                    class="w-full px-3.5 py-2 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs text-[#121417] focus:outline-none focus:bg-white focus:border-[#121417] transition-all"
                                >{{ old('notes') }}</textarea>
                                @error('notes')
                                    <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Unggah Bukti Pembayaran -->
                            <div>
                                <label for="proofs" class="block text-xs font-semibold uppercase tracking-wider text-[#121417] mb-1.5">
                                    Unggah Bukti Transfer / Pembayaran <span class="text-red-500">*</span>
                                </label>
                                <div class="p-5 border border-dashed border-[#DEDAD1] hover:border-[#121417] rounded-xl bg-[#F9F8F5] text-center space-y-2 transition-all">
                                    <x-ui.icon name="arrow-up-tray" class="w-6 h-6 text-[#5E6470] mx-auto" />
                                    <input
                                        type="file"
                                        name="proofs[]"
                                        id="proofs"
                                        multiple
                                        required
                                        accept="image/jpeg,image/png,application/pdf"
                                        class="block w-full text-xs text-[#5E6470] file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-[#DEDAD1] file:text-xs file:font-semibold file:bg-white file:text-[#121417] file:cursor-pointer hover:file:bg-[#F4F1EA] cursor-pointer"
                                    />
                                    <p class="text-[11px] text-[#5E6470]">
                                        Format diizinkan: <strong>JPEG, PNG, PDF</strong> (Maksimal 5MB per file)
                                    </p>
                                </div>
                                @error('proofs')
                                    <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                @error('proofs.*')
                                    <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-2">
                                <button
                                    type="submit"
                                    class="w-full py-2.5 px-4 rounded-lg bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs transition-all cursor-pointer flex items-center justify-center gap-2 shadow-[0_1px_2px_rgba(18,20,23,0.08)]"
                                >
                                    <x-ui.icon name="paper-airplane" class="w-4 h-4 text-[#C7F36B]" />
                                    <span>Kirim Bukti Pembayaran</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                <!-- Riwayat Pembayaran Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-xl p-5 sm:p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="credit-card" class="w-4 h-4 text-[#5E6470]" />
                            <h2 class="text-sm font-bold text-[#121417]">Riwayat Pembayaran</h2>
                        </div>
                        <span class="text-xs font-mono text-[#5E6470]">
                            {{ $bill->payments->count() }} Pengajuan
                        </span>
                    </div>

                    @if($bill->payments->isEmpty())
                        <div class="p-8 text-center bg-[#F9F8F5] rounded-xl border border-dashed border-[#DEDAD1] space-y-1.5">
                            <x-ui.icon name="credit-card" class="w-6 h-6 text-[#5E6470] mx-auto" />
                            <p class="text-xs text-[#5E6470]">Belum ada riwayat pembayaran yang diajukan untuk tagihan ini.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-[#DEDAD1] bg-[#F9F8F5] text-[#5E6470] font-semibold uppercase tracking-wider text-[11px]">
                                        <th class="py-2.5 px-3">No. Transaksi</th>
                                        <th class="py-2.5 px-3">Metode & Ref</th>
                                        <th class="py-2.5 px-3 text-right">Nominal</th>
                                        <th class="py-2.5 px-3 text-center">Status</th>
                                        <th class="py-2.5 px-3">Waktu & Keterangan</th>
                                        <th class="py-2.5 px-3 text-center">Bukti Transfer</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#DEDAD1] font-normal text-[#121417]">
                                    @foreach($bill->payments as $payment)
                                        @php
                                            $payBadgeVariant = match($payment->status) {
                                                \App\Enums\PaymentStatus::Berhasil->value => 'emerald',
                                                \App\Enums\PaymentStatus::MenungguVerifikasi->value => 'amber',
                                                \App\Enums\PaymentStatus::Ditolak->value => 'rose',
                                                \App\Enums\PaymentStatus::Dibatalkan->value => 'neutral',
                                                default => 'neutral',
                                            };
                                        @endphp
                                        <tr class="hover:bg-[#F9F8F5]/60 transition-colors">
                                            <td class="py-3 px-3 font-mono font-semibold text-[#121417]">
                                                {{ $payment->payment_number }}
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="font-medium block">{{ $payment->payment_method ?? 'Manual' }}</span>
                                                @if($payment->reference_number)
                                                    <span class="text-[11px] text-[#5E6470] font-mono block">Ref: {{ $payment->reference_number }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-3 text-right font-mono font-semibold text-[#121417]">
                                                Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <x-ui.badge :variant="$payBadgeVariant" size="sm">
                                                    {{ $payment->statusEnum()?->label() ?? ucfirst($payment->status) }}
                                                </x-ui.badge>
                                            </td>
                                            <td class="py-3 px-3 text-xs">
                                                <div class="font-mono text-[#5E6470]">{{ $payment->payment_date?->format('d/m/Y H:i') ?? '-' }}</div>
                                                @if($payment->verifier)
                                                    <div class="text-[11px] text-[#5E6470]">Diverifikasi: {{ $payment->verifier->name }}</div>
                                                @endif
                                                @if($payment->isRejected() && $payment->rejection_reason)
                                                    <div class="text-[11px] text-[#B91C1C] font-medium mt-1 p-1.5 bg-[#FEE2E2]/60 rounded border border-[#FECACA]">
                                                        Alasan Penolakan: {{ $payment->rejection_reason }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                @if($payment->proofs->isNotEmpty())
                                                    <div class="flex flex-col gap-1 items-center">
                                                        @foreach($payment->proofs as $proof)
                                                            <a
                                                                href="{{ route('payments.proofs.download', [$payment, $proof]) }}"
                                                                class="px-2 py-1 rounded border border-[#DEDAD1] bg-white text-[11px] font-medium text-blue-600 hover:text-blue-800 hover:border-blue-300 transition-all inline-flex items-center gap-1 shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                                                                title="{{ $proof->file_name }}"
                                                            >
                                                                <x-ui.icon name="document-text" class="w-3 h-3 text-[#5E6470]" />
                                                                <span class="max-w-[100px] truncate">{{ $proof->file_name }}</span>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-[#5E6470] text-xs">-</span>
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
    </div>
</x-layouts.tenant>
