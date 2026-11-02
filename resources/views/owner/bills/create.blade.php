<x-layouts.owner title="Buat Tagihan Baru — HUNI" activeNav="invoices">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Breadcrumb & Header -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-2">
                <a href="{{ route('owner.dashboard') }}" class="hover:text-[#121417] transition-colors">Dashboard</a>
                <span class="text-[#DEDAD1]">/</span>
                <a href="{{ route('owner.bills.index') }}" class="hover:text-[#121417] transition-colors">Invoice & Tagihan</a>
                <span class="text-[#DEDAD1]">/</span>
                <span class="text-[#121417] font-semibold">Buat Tagihan Baru</span>
            </nav>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#C7F36B]/20 border border-[#C7F36B]/60 flex items-center justify-center shrink-0">
                    <x-ui.icon name="receipt" class="w-5 h-5 text-[#15803D]" />
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">Buat Tagihan Manual</h1>
                    <p class="text-xs sm:text-sm text-[#6B6B6B] mt-0.5">
                        Terbitkan tagihan sewa bulanan, uang deposit, atau biaya tambahan untuk penghuni kos Anda.
                    </p>
                </div>
            </div>
        </div>

        @if(session('error'))
            <x-ui.alert type="danger" :message="session('error')" dismissible="true" />
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-[#FEE2E2] border border-[#FECACA] space-y-1">
                <div class="flex items-center gap-2 font-bold text-xs text-[#B91C1C]">
                    <x-ui.icon name="alert-triangle" class="w-4 h-4 shrink-0" />
                    <span>Terdapat beberapa kesalahan pengisian formulir:</span>
                </div>
                <ul class="list-disc list-inside text-xs font-medium text-[#B91C1C] pl-2 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $contractsData = $contracts->map(fn($c) => [
                'id' => $c->id,
                'number' => $c->contract_number,
                'property' => $c->property?->name,
                'room' => $c->room?->room_number,
                'tenant' => $c->tenant?->name,
                'rent' => (float)$c->monthly_rent,
                'deposit' => (float)$c->deposit_amount,
                'status' => $c->status,
            ]);
            $initialItems = old('items', [
                ['description' => '', 'amount' => '', 'quantity' => 1]
            ]);
        @endphp

        <!-- Form Card with Alpine.js Reactive State -->
        <div
            x-data="{
                contractId: '{{ old('contract_id', $selectedContractId ?: '') }}',
                type: '{{ old('type', 'tambahan') }}',
                billingPeriod: '{{ old('billing_period', $currentPeriod) }}',
                issueDate: '{{ old('issue_date', $defaultIssueDate) }}',
                dueDate: '{{ old('due_date', $defaultDueDate) }}',
                items: {{ Js::from($initialItems) }},
                contracts: {{ Js::from($contractsData) }},

                addItem() {
                    this.items.push({ description: '', amount: '', quantity: 1 });
                },
                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },
                calculateTotal() {
                    return this.items.reduce((sum, item) => {
                        const amount = parseFloat(item.amount) || 0;
                        const qty = parseInt(item.quantity) || 1;
                        return sum + (amount * qty);
                    }, 0);
                },
                formatCurrency(val) {
                    return 'Rp ' + Math.round(val).toLocaleString('id-ID');
                },
                onContractOrTypeChange() {
                    const selected = this.contracts.find(c => String(c.id) === String(this.contractId));
                    if (!selected) return;

                    if (this.type === 'deposit') {
                        if (this.items.length === 1 && (!this.items[0].description || this.items[0].description.startsWith('Deposit'))) {
                            this.items[0].description = 'Deposit Jaminan Sewa - Kamar ' + (selected.room || '');
                            this.items[0].amount = selected.deposit;
                            this.items[0].quantity = 1;
                        }
                    } else if (this.type === 'sewa') {
                        if (this.items.length === 1 && (!this.items[0].description || this.items[0].description.startsWith('Sewa'))) {
                            this.items[0].description = 'Sewa Kamar ' + (selected.room || '');
                            this.items[0].amount = selected.rent;
                            this.items[0].quantity = 1;
                        }
                    }
                },
                updateDueDate() {
                    if (!this.issueDate) return;
                    const d = new Date(this.issueDate);
                    d.setDate(d.getDate() + 7);
                    this.dueDate = d.toISOString().split('T')[0];
                }
            }"
            class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-6"
        >
            <form method="POST" action="{{ route('owner.bills.store') }}" class="space-y-6">
                @csrf

                <!-- Section 1: Contract & Type Selection -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                        <span class="w-6 h-6 rounded-lg bg-[#F4F2EC] text-[#121417] text-xs font-bold flex items-center justify-center">1</span>
                        <h3 class="text-sm font-bold text-[#121417]">Sasaran Kontrak & Jenis Tagihan</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Contract Selector -->
                        <div class="space-y-1.5 sm:col-span-2">
                            <label for="contract_id" class="text-xs font-semibold text-[#121417] block">
                                Kontrak Sewa <span class="text-[#B91C1C]">*</span>
                            </label>
                            <select
                                name="contract_id"
                                id="contract_id"
                                x-model="contractId"
                                x-on:change="onContractOrTypeChange()"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                            >
                                <option value="">-- Pilih Kontrak Sewa Penghuni --</option>
                                @foreach($contracts as $c)
                                    <option value="{{ $c->id }}">
                                        {{ $c->contract_number }} — {{ $c->tenant?->name ?? 'Penghuni' }} ({{ $c->property?->name }}, Kamar {{ $c->room?->room_number }}) [Status: {{ ucfirst($c->status) }}]
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-[#6B6B6B]">
                                Pilih kontrak aktif untuk sewa/deposit, atau kontrak historis untuk biaya pelunasan/tambahan.
                            </p>
                        </div>

                        <!-- Invoice Type -->
                        <div class="space-y-1.5">
                            <label for="type" class="text-xs font-semibold text-[#121417] block">
                                Jenis Tagihan <span class="text-[#B91C1C]">*</span>
                            </label>
                            <select
                                name="type"
                                id="type"
                                x-model="type"
                                x-on:change="onContractOrTypeChange()"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                            >
                                <option value="tambahan">Biaya Tambahan / Ad-Hoc (Denda, Listrik, Kerusakan, dll)</option>
                                <option value="sewa">Sewa Kamar Bulanan</option>
                                <option value="deposit">Deposit Jaminan Sewa</option>
                            </select>
                        </div>

                        <!-- Billing Period (Conditionally required for sewa) -->
                        <div class="space-y-1.5">
                            <label for="billing_period" class="text-xs font-semibold text-[#121417] block">
                                Periode Penagihan (YYYY-MM)
                                <span x-show="type === 'sewa'" class="text-[#B91C1C]">*</span>
                            </label>
                            <input
                                type="text"
                                name="billing_period"
                                id="billing_period"
                                x-model="billingPeriod"
                                placeholder="contoh: 2026-10"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                            />
                            <p class="text-[11px] text-[#6B6B6B]">Format: YYYY-MM (misal: 2026-10 untuk Oktober 2026).</p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Dates -->
                <div class="space-y-4 pt-2">
                    <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                        <span class="w-6 h-6 rounded-lg bg-[#F4F2EC] text-[#121417] text-xs font-bold flex items-center justify-center">2</span>
                        <h3 class="text-sm font-bold text-[#121417]">Jadwal Terbit & Jatuh Tempo</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="issue_date" class="text-xs font-semibold text-[#121417] block">
                                Tanggal Terbit <span class="text-[#B91C1C]">*</span>
                            </label>
                            <input
                                type="date"
                                name="issue_date"
                                id="issue_date"
                                x-model="issueDate"
                                x-on:change="updateDueDate()"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label for="due_date" class="text-xs font-semibold text-[#121417] block">
                                Tanggal Jatuh Tempo (Otomatis 7 Hari)
                            </label>
                            <input
                                type="date"
                                name="due_date"
                                id="due_date"
                                x-model="dueDate"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                            />
                            <p class="text-[11px] text-[#6B6B6B]">Standar default jatuh tempo adalah 7 hari kalender pasca terbit.</p>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Line Items (Dynamic with Alpine.js) -->
                <div class="space-y-4 pt-2">
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-[#F4F2EC] text-[#121417] text-xs font-bold flex items-center justify-center">3</span>
                            <h3 class="text-sm font-bold text-[#121417]">Rincian Item Tagihan</h3>
                        </div>
                        <button
                            type="button"
                            x-on:click="addItem()"
                            class="px-3 py-1.5 rounded-xl bg-[#F4F2EC] border border-[#DEDAD1] text-xs font-semibold text-[#121417] hover:bg-[#EAE6DF] transition-colors cursor-pointer flex items-center gap-1.5"
                        >
                            <x-ui.icon name="plus" class="w-3.5 h-3.5" />
                            <span>Tambah Baris</span>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="p-3.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                <!-- Item Description -->
                                <div class="sm:col-span-6 space-y-1">
                                    <label class="text-[11px] font-semibold text-[#6B6B6B] block">
                                        Deskripsi <span class="text-[#B91C1C]">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        :name="'items[' + index + '][description]'"
                                        x-model="item.description"
                                        placeholder="Contoh: Sewa Kamar, Denda Kunci, Biaya Listrik"
                                        required
                                        class="w-full px-3 py-2 rounded-lg bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417]"
                                    />
                                </div>

                                <!-- Item Amount -->
                                <div class="sm:col-span-3 space-y-1">
                                    <label class="text-[11px] font-semibold text-[#6B6B6B] block">
                                        Nominal (Rp) <span class="text-[#B91C1C]">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        :name="'items[' + index + '][amount]'"
                                        x-model="item.amount"
                                        min="1"
                                        step="1"
                                        placeholder="Contoh: 1500000"
                                        required
                                        class="w-full px-3 py-2 rounded-lg bg-white border border-[#DEDAD1] text-xs font-mono font-medium text-[#121417] focus:outline-hidden focus:border-[#121417]"
                                    />
                                </div>

                                <!-- Quantity -->
                                <div class="sm:col-span-2 space-y-1">
                                    <label class="text-[11px] font-semibold text-[#6B6B6B] block">Jumlah</label>
                                    <input
                                        type="number"
                                        :name="'items[' + index + '][quantity]'"
                                        x-model="item.quantity"
                                        min="1"
                                        max="999"
                                        required
                                        class="w-full px-3 py-2 rounded-lg bg-white border border-[#DEDAD1] text-xs font-mono font-medium text-[#121417] focus:outline-hidden focus:border-[#121417]"
                                    />
                                </div>

                                <!-- Remove Button -->
                                <div class="sm:col-span-1 flex justify-end">
                                    <button
                                        type="button"
                                        x-on:click="removeItem(index)"
                                        :disabled="items.length <= 1"
                                        class="w-8 h-8 rounded-lg bg-white border border-[#DEDAD1] text-xs font-semibold text-[#B91C1C] hover:bg-[#FEE2E2] hover:border-[#FECACA] transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center cursor-pointer"
                                        title="Hapus baris ini"
                                    >
                                        <x-ui.icon name="trash" class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Calculated Total Preview Box -->
                    <div class="p-4 rounded-xl bg-[#121417] text-white flex items-center justify-between shadow-[0_1px_3px_rgba(18,20,23,0.08)]">
                        <div>
                            <span class="text-xs font-semibold text-[#A5A096] uppercase tracking-wider block">Total Tagihan Dihitung:</span>
                            <span class="text-[11px] text-[#A5A096]">Dihitung otomatis oleh server dari rincian item</span>
                        </div>
                        <div class="text-2xl font-extrabold text-[#C7F36B] font-mono tracking-tight" x-text="formatCurrency(calculateTotal())">
                            Rp 0
                        </div>
                    </div>
                </div>

                <!-- Section 4: Notes -->
                <div class="space-y-2 pt-2 border-t border-[#DEDAD1]">
                    <label for="notes" class="text-xs font-semibold text-[#121417] block">Catatan / Instruksi Pembayaran (Opsional)</label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="3"
                        placeholder="Contoh: Pembayaran dapat ditransfer ke BCA 1234567890 a.n. Pemilik Kos. Harap cantumkan nomor invoice pada berita transfer."
                        class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] resize-none"
                    >{{ old('notes') }}</textarea>
                </div>

                <!-- Submit / Cancel Actions -->
                <div class="pt-4 border-t border-[#DEDAD1] flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('owner.bills.index') }}" variant="outline">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Terbitkan Tagihan Sekarang
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.owner>
