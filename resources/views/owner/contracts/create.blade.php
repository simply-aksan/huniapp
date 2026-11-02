<x-layouts.owner title="Buat Kontrak Baru — HUNI" activeNav="contracts">
    <div class="max-w-4xl mx-auto space-y-6">
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
                <span class="font-medium text-[#121417]">Buat Kontrak Baru</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-[#121417]">Buat Kontrak Sewa Baru</h1>
            <p class="text-xs text-[#6B6B6B]">
                Tautkan kamar dengan penyewa sah yang telah menerima undangan sewa kos Anda.
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

        @php
            $initialProp = old('property_id', $preselectedPropertyId ?: ($properties->first()?->id ?? ''));
            $initialRoom = old('room_id', $preselectedRoomId ?: '');
            $initialTenant = old('user_id', $preselectedUserId ?: '');
            $initialRent = old('monthly_rent', '');
            $initialStart = old('start_date', now()->format('Y-m-d'));
            $initialEnd = old('end_date', now()->addMonths(1)->format('Y-m-d'));
        @endphp

        <!-- Form Card with Alpine.js Reactive Calculations -->
        <div
            x-data="{
                selectedProperty: '{{ $initialProp }}',
                selectedRoom: '{{ $initialRoom }}',
                selectedTenant: '{{ $initialTenant }}',
                monthlyRent: '{{ $initialRent }}',
                startDate: '{{ $initialStart }}',
                endDate: '{{ $initialEnd }}',
                durationMonths: 1,
                totalAmount: 0,
                allRooms: {{ json_encode($roomsData) }},

                get filteredRooms() {
                    if (!this.selectedProperty) return [];
                    return this.allRooms.filter(r => String(r.property_id) === String(this.selectedProperty));
                },

                get currentRoom() {
                    return this.allRooms.find(r => String(r.id) === String(this.selectedRoom)) || null;
                },

                get eligibleTenants() {
                    const room = this.currentRoom;
                    return room ? room.eligible_tenants : [];
                },

                onPropertyChange() {
                    this.selectedRoom = '';
                    this.selectedTenant = '';
                    const rooms = this.filteredRooms;
                    if (rooms.length > 0) {
                        this.selectedRoom = rooms[0].id;
                        this.onRoomChange();
                    }
                },

                onRoomChange() {
                    this.selectedTenant = '';
                    const room = this.currentRoom;
                    if (room) {
                        if (!this.monthlyRent || this.monthlyRent === '0') {
                            this.monthlyRent = room.price;
                        }
                        if (room.eligible_tenants.length === 1) {
                            this.selectedTenant = room.eligible_tenants[0].id;
                        }
                    }
                    this.recalculateTotal();
                },

                recalculateTotal() {
                    if (this.startDate && this.endDate) {
                        const start = new Date(this.startDate);
                        const end = new Date(this.endDate);
                        if (end > start) {
                            const diffMonths = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
                            const dayDiff = end.getDate() - start.getDate();
                            const calculatedMonths = Math.max(1, diffMonths + (dayDiff > 0 ? 1 : 0));
                            this.durationMonths = calculatedMonths;
                        } else {
                            this.durationMonths = 1;
                        }
                    }
                    const rent = parseFloat(this.monthlyRent) || 0;
                    this.totalAmount = this.durationMonths * rent;
                },

                init() {
                    if (this.selectedRoom) {
                        const room = this.currentRoom;
                        if (room && (!this.monthlyRent || this.monthlyRent === '0')) {
                            this.monthlyRent = room.price;
                        }
                    }
                    this.recalculateTotal();
                }
            }"
            class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)]"
        >
            <form method="POST" action="{{ route('owner.contracts.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Section: Pemilihan Properti, Kamar, & Penyewa -->
                <div class="border-b border-[#DEDAD1] pb-6 space-y-4">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-[#121417]">
                        <span class="w-2 h-2 rounded-full bg-[#121417]"></span>
                        <span>1. Informasi Kamar &amp; Penyewa</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Properti Selection -->
                        <div>
                            <label for="property_id" class="block text-xs font-semibold text-[#121417] mb-1.5">
                                Properti Kos <span class="text-red-500">*</span>
                            </label>
                            <select
                                name="property_id"
                                id="property_id"
                                x-model="selectedProperty"
                                x-on:change="onPropertyChange()"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all cursor-pointer @error('property_id') border-red-500 @enderror"
                                required
                            >
                                <option value="">-- Pilih Properti --</option>
                                @foreach($properties as $prop)
                                    <option value="{{ $prop->id }}">{{ $prop->name }}</option>
                                @endforeach
                            </select>
                            @error('property_id')
                                <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Kamar Selection -->
                        <div>
                            <label for="room_id" class="block text-xs font-semibold text-[#121417] mb-1.5">
                                Kamar Kos <span class="text-red-500">*</span>
                            </label>
                            <select
                                name="room_id"
                                id="room_id"
                                x-model="selectedRoom"
                                x-on:change="onRoomChange()"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all cursor-pointer @error('room_id') border-red-500 @enderror"
                                required
                            >
                                <option value="">-- Pilih Kamar --</option>
                                <template x-for="r in filteredRooms" :key="r.id">
                                    <option :value="r.id" x-text="'Kamar ' + r.room_number + ' (Rp ' + Number(r.price).toLocaleString('id-ID') + '/bln)' + (r.status === 'perbaikan' ? ' - [Perbaikan]' : '')"></option>
                                </template>
                            </select>
                            @error('room_id')
                                <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Penyewa Selection -->
                    <div>
                        <label for="user_id" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Penyewa Terhubung <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="user_id"
                            id="user_id"
                            x-model="selectedTenant"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all cursor-pointer @error('user_id') border-red-500 @enderror"
                            required
                        >
                            <option value="">-- Pilih Penyewa Sah --</option>
                            <template x-for="t in eligibleTenants" :key="t.id">
                                <option :value="t.id" x-text="t.name + ' (' + (t.phone || t.email) + ')'"></option>
                            </template>
                        </select>
                        <p class="text-[11px] text-[#6B6B6B] mt-1">
                            Hanya penyewa yang telah menerima undangan sewa untuk kamar ini atau pernah menyewa sebelumnya yang dapat dipilih.
                        </p>
                        @error('user_id')
                            <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                        @enderror

                        <!-- Warning if no eligible tenant for selected room -->
                        <div x-show="selectedRoom && eligibleTenants.length === 0" x-cloak class="mt-3 p-3.5 rounded-xl bg-[#FEF3C7] border border-[#FDE68A] text-xs text-[#92400E] flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#D97706] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Kamar ini belum memiliki calon penyewa yang menerima undangan.</span>
                            </div>
                            <a href="{{ route('owner.invitations.create') }}" class="px-2.5 py-1 rounded-lg bg-white border border-[#DEDAD1] font-semibold text-[11px] text-[#121417] hover:bg-gray-50 shrink-0">
                                Buat Undangan
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Section: Periode & Finansial Sewa -->
                <div class="border-b border-[#DEDAD1] pb-6 space-y-4">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-[#121417]">
                        <span class="w-2 h-2 rounded-full bg-[#121417]"></span>
                        <span>2. Periode Sewa &amp; Finansial</span>
                    </div>

                    <!-- Periode Sewa Tanggal (2 Kolom) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="start_date" class="block text-xs font-semibold text-[#121417] mb-1.5">
                                Tanggal Mulai Sewa <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
                                x-model="startDate"
                                x-on:change="recalculateTotal()"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('start_date') border-red-500 @enderror"
                                required
                            />
                            @error('start_date')
                                <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                            @enderror
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
                                x-on:change="recalculateTotal()"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('end_date') border-red-500 @enderror"
                                required
                            />
                            @error('end_date')
                                <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Harga Sewa per Bulan & Durasi / Total Preview (2 Kolom) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="monthly_rent" class="block text-xs font-semibold text-[#121417] mb-1.5">
                                Harga Sewa per Bulan (Rp) <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                name="monthly_rent"
                                id="monthly_rent"
                                x-model="monthlyRent"
                                x-on:input="recalculateTotal()"
                                min="0"
                                step="1000"
                                placeholder="Contoh: 1500000"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-mono font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('monthly_rent') border-red-500 @enderror"
                                required
                            />
                            @error('monthly_rent')
                                <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Auto Calculation Card -->
                        <div class="p-3.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex flex-col justify-center">
                            <div class="flex items-center justify-between text-xs text-[#6B6B6B]">
                                <span>Durasi Terhitung:</span>
                                <span class="font-semibold text-[#121417]" x-text="durationMonths + ' Bulan'"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-[#6B6B6B] mt-1.5">
                                <span>Estimasi Total Sewa:</span>
                                <span class="text-sm font-mono font-bold tabular-nums text-[#15803D]" x-text="'Rp ' + Number(totalAmount).toLocaleString('id-ID')"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Deposit Amount & Deposit Status (2 Kolom) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="deposit_amount" class="block text-xs font-semibold text-[#121417] mb-1.5">
                                Uang Jaminan / Deposit (Rp)
                            </label>
                            <input
                                type="number"
                                name="deposit_amount"
                                id="deposit_amount"
                                value="{{ old('deposit_amount', '0') }}"
                                min="0"
                                step="1000"
                                placeholder="0 jika tanpa deposit"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-mono font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all"
                            />
                            @error('deposit_amount')
                                <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="deposit_status" class="block text-xs font-semibold text-[#121417] mb-1.5">
                                Status Pembayaran Deposit
                            </label>
                            <select
                                name="deposit_status"
                                id="deposit_status"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all cursor-pointer"
                            >
                                <option value="belum_dibayar" @selected(old('deposit_status') === 'belum_dibayar')>Belum Dibayar</option>
                                <option value="sudah_dibayar" @selected(old('deposit_status') === 'sudah_dibayar')>Sudah Dibayar</option>
                                <option value="lunas" @selected(old('deposit_status') === 'lunas')>Lunas</option>
                            </select>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label for="payment_method" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Metode Pembayaran Disepakati
                        </label>
                        <select
                            name="payment_method"
                            id="payment_method"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all cursor-pointer"
                        >
                            <option value="transfer_bank" @selected(old('payment_method') === 'transfer_bank')>Transfer Bank</option>
                            <option value="tunai" @selected(old('payment_method') === 'tunai')>Tunai</option>
                            <option value="qris" @selected(old('payment_method') === 'qris')>QRIS</option>
                            <option value="ewallet" @selected(old('payment_method') === 'ewallet')>e-Wallet (GoPay / OVO / Dana)</option>
                        </select>
                    </div>
                </div>

                <!-- Section: Status Kontrak & Dokumen Pendukung -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-[#121417]">
                        <span class="w-2 h-2 rounded-full bg-[#121417]"></span>
                        <span>3. Status Kontrak &amp; Berkas</span>
                    </div>

                    <!-- Status Kontrak (Draft vs Aktif) -->
                    <div>
                        <label class="block text-xs font-semibold text-[#121417] mb-2">
                            Status Awal Kontrak <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="relative flex items-start gap-3 p-4 rounded-xl border border-[#DEDAD1] bg-white cursor-pointer hover:bg-[#F9F8F5] has-checked:border-[#121417] has-checked:bg-[#E8F8D0]/30 transition-all">
                                <input
                                    type="radio"
                                    name="status"
                                    value="aktif"
                                    class="mt-1 w-4 h-4 text-[#121417] focus:ring-[#121417]"
                                    @checked(old('status', 'aktif') === 'aktif')
                                />
                                <div>
                                    <p class="text-xs font-bold text-[#121417]">Kontrak Aktif</p>
                                    <p class="text-[11px] text-[#6B6B6B] mt-0.5 leading-relaxed">
                                        Kontrak langsung berlaku sah. Kamar akan ditandai terisi dan tidak dapat disewakan ke pihak lain.
                                    </p>
                                </div>
                            </label>

                            <label class="relative flex items-start gap-3 p-4 rounded-xl border border-[#DEDAD1] bg-white cursor-pointer hover:bg-[#F9F8F5] has-checked:border-[#121417] has-checked:bg-[#FEF3C7]/40 transition-all">
                                <input
                                    type="radio"
                                    name="status"
                                    value="draft"
                                    class="mt-1 w-4 h-4 text-[#121417] focus:ring-[#121417]"
                                    @checked(old('status') === 'draft')
                                />
                                <div>
                                    <p class="text-xs font-bold text-[#121417]">Draft Kontrak</p>
                                    <p class="text-[11px] text-[#6B6B6B] mt-0.5 leading-relaxed">
                                        Draf sewa untuk disiapkan sebelum tanggal berlaku. Kontrak belum mengaktifkan status sewa penuh.
                                    </p>
                                </div>
                            </label>
                        </div>
                        @error('status')
                            <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Upload Berkas Kontrak -->
                    <div>
                        <label for="document" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Unggah Dokumen Perjanjian (Opsional)
                        </label>
                        <input
                            type="file"
                            name="document"
                            id="document"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="w-full px-3.5 py-2 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border file:border-[#DEDAD1] file:text-xs file:font-semibold file:bg-white file:text-[#121417] file:cursor-pointer"
                        />
                        <p class="text-[11px] text-[#6B6B6B] mt-1">
                            Format PDF, JPG, JPEG, atau PNG (Maksimal 10MB). File disimpan secara privat di server aman.
                        </p>
                        @error('document')
                            <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Catatan Khusus Kontrak
                        </label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="3"
                            placeholder="Contoh: Termasuk fasilitas parkir motor 1 unit. Pembayaran sewa paling lambat tanggal 5 setiap bulan."
                            class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all leading-relaxed"
                        >{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#DEDAD1]">
                    <a
                        href="{{ route('owner.contracts.index') }}"
                        class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-xs active:scale-[0.98] transition-all cursor-pointer"
                    >
                        Simpan &amp; Buat Kontrak
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.owner>
