<x-layouts.owner title="Edit Kontrak {{ $contract->contract_number }} — HUNI" activeNav="contracts">
    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Breadcrumb & Header -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-1.5">
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#6B6B6B]">
                <a href="{{ route('owner.dashboard') }}" class="hover:text-[#121417] transition-colors">Dashboard</a>
                <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <a href="{{ route('owner.contracts.index') }}" class="hover:text-[#121417] transition-colors">Kontrak</a>
                <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <a href="{{ route('owner.contracts.show', $contract) }}" class="hover:text-[#121417] transition-colors">{{ $contract->contract_number }}</a>
                <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="font-medium text-[#121417]">Edit</span>
            </nav>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-[#121417]">Edit Kontrak Sewa</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $contract->status === 'aktif' ? 'bg-[#E8F8D0] text-[#15803D] border-[#C7F36B]/60' : 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]' }}">
                    {{ ucfirst($contract->status) }}
                </span>
            </div>
            <p class="text-xs text-[#6B6B6B]">
                Perbarui rincian periode, deposit, metode pembayaran, atau catatan kontrak.
            </p>
        </div>

        @if(session('error'))
            <x-ui.alert type="danger" :dismissible="true">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        @if($errors->any())
            <x-ui.alert type="danger" :dismissible="true">
                <p class="font-semibold mb-1">Terdapat beberapa kesalahan pengisian formulir:</p>
                <ul class="list-disc list-inside text-xs pl-2 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <!-- Card Form Edit -->
        <div
            x-data="{
                isDraft: {{ $contract->isDraft() ? 'true' : 'false' }},
                startDate: '{{ old('start_date', $contract->start_date?->format('Y-m-d')) }}',
                endDate: '{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}',
                monthlyRent: '{{ old('monthly_rent', (int)$contract->monthly_rent) }}',
                durationMonths: {{ $contract->duration_months }},
                totalAmount: {{ (float)$contract->total_amount }},

                recalculate() {
                    if (this.startDate && this.endDate) {
                        const start = new Date(this.startDate);
                        const end = new Date(this.endDate);
                        if (end > start) {
                            const diffMonths = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
                            const dayDiff = end.getDate() - start.getDate();
                            this.durationMonths = Math.max(1, diffMonths + (dayDiff > 0 ? 1 : 0));
                        }
                    }
                    const rent = parseFloat(this.monthlyRent) || 0;
                    this.totalAmount = this.durationMonths * rent;
                }
            }"
            class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)]"
        >
            <!-- Locked Entity Information Banner -->
            <div class="p-4 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-white border border-[#DEDAD1] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-[11px] text-[#6B6B6B]">Properti, Kamar, &amp; Penyewa Terikat</p>
                        <p class="text-xs font-semibold text-[#121417]">
                            {{ $contract->property?->name }} • Kamar {{ $contract->room?->room_number }} • {{ $contract->tenant?->name }}
                        </p>
                    </div>
                </div>
                <div class="text-[11px] text-[#6B6B6B] shrink-0">
                    No: <span class="font-mono font-bold text-[#121417]">{{ $contract->contract_number }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('owner.contracts.update', $contract) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Periode Sewa -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Tanggal Mulai Sewa
                        </label>
                        @if($contract->isDraft())
                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
                                x-model="startDate"
                                x-on:change="recalculate()"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417]"
                            />
                        @else
                            <input
                                type="date"
                                value="{{ $contract->start_date?->format('Y-m-d') }}"
                                disabled
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F1EFEA] text-xs font-medium text-[#6B6B6B] cursor-not-allowed"
                            />
                            <p class="text-[11px] text-[#A5A096] mt-1">Tanggal mulai sewa terkunci setelah kontrak aktif.</p>
                        @endif
                    </div>

                    <div>
                        <label for="end_date" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Tanggal Berakhir Sewa <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            name="end_date"
                            id="end_date"
                            x-model="endDate"
                            x-on:change="recalculate()"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417]"
                        />
                    </div>
                </div>

                <!-- Finansial Sewa -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="monthly_rent" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Harga Sewa per Bulan (Rp)
                        </label>
                        @if($contract->isDraft())
                            <input
                                type="number"
                                name="monthly_rent"
                                id="monthly_rent"
                                x-model="monthlyRent"
                                x-on:input="recalculate()"
                                min="0"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-mono font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417]"
                            />
                        @else
                            <input
                                type="number"
                                value="{{ (int)$contract->monthly_rent }}"
                                disabled
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F1EFEA] text-xs font-mono font-medium text-[#6B6B6B] cursor-not-allowed"
                            />
                            <p class="text-[11px] text-[#A5A096] mt-1">Harga kontrak aktif terkunci demi konsistensi data finansial.</p>
                        @endif
                    </div>

                    <div class="p-3.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex flex-col justify-center">
                        <div class="flex items-center justify-between text-xs text-[#6B6B6B]">
                            <span>Durasi Disesuaikan:</span>
                            <span class="font-semibold text-[#121417]" x-text="durationMonths + ' Bulan'"></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-[#6B6B6B] mt-1.5">
                            <span>Estimasi Total Sewa:</span>
                            <span class="text-sm font-mono font-bold tabular-nums text-[#15803D]" x-text="'Rp ' + Number(totalAmount).toLocaleString('id-ID')"></span>
                        </div>
                    </div>
                </div>

                <!-- Deposit & Metode Pembayaran -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="deposit_amount" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Uang Jaminan / Deposit (Rp)
                        </label>
                        <input
                            type="number"
                            name="deposit_amount"
                            id="deposit_amount"
                            value="{{ old('deposit_amount', (int)$contract->deposit_amount) }}"
                            min="0"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-mono font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417]"
                        />
                    </div>

                    <div>
                        <label for="deposit_status" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Status Deposit
                        </label>
                        <select
                            name="deposit_status"
                            id="deposit_status"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] cursor-pointer"
                        >
                            <option value="belum_dibayar" @selected(old('deposit_status', $contract->deposit_status) === 'belum_dibayar')>Belum Dibayar</option>
                            <option value="sudah_dibayar" @selected(old('deposit_status', $contract->deposit_status) === 'sudah_dibayar')>Sudah Dibayar</option>
                            <option value="lunas" @selected(old('deposit_status', $contract->deposit_status) === 'lunas')>Lunas</option>
                        </select>
                    </div>
                </div>

                <!-- Payment Method -->
                <div>
                    <label for="payment_method" class="block text-xs font-semibold text-[#121417] mb-1.5">
                        Metode Pembayaran
                    </label>
                    <select
                        name="payment_method"
                        id="payment_method"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] cursor-pointer"
                    >
                        <option value="transfer_bank" @selected(old('payment_method', $contract->payment_method) === 'transfer_bank')>Transfer Bank</option>
                        <option value="tunai" @selected(old('payment_method', $contract->payment_method) === 'tunai')>Tunai</option>
                        <option value="qris" @selected(old('payment_method', $contract->payment_method) === 'qris')>QRIS</option>
                        <option value="ewallet" @selected(old('payment_method', $contract->payment_method) === 'ewallet')>e-Wallet (GoPay / OVO / Dana)</option>
                    </select>
                </div>

                <!-- Status Transition (Only if draft) -->
                @if($contract->isDraft())
                    <div>
                        <label for="status" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Ubah Status Menjadi Aktif?
                        </label>
                        <select
                            name="status"
                            id="status"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] cursor-pointer"
                        >
                            <option value="draft" @selected(old('status', $contract->status) === 'draft')>Tetap Draft</option>
                            <option value="aktif" @selected(old('status', $contract->status) === 'aktif')>Aktifkan Sekarang (Kamar menjadi Terisi)</option>
                        </select>
                    </div>
                @endif

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-xs font-semibold text-[#121417] mb-1.5">
                        Catatan Kontrak
                    </label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="3"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] leading-relaxed"
                    >{{ old('notes', $contract->notes) }}</textarea>
                </div>

                <!-- Submit buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#DEDAD1]">
                    <a
                        href="{{ route('owner.contracts.show', $contract) }}"
                        class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-xs active:scale-[0.98] transition-all cursor-pointer"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.owner>
