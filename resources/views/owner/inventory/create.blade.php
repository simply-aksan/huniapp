<x-layouts.owner title="Tambah Inventaris — HUNI" activeNav="inventory">
    @php
        $propertiesData = $properties->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'rooms' => $p->rooms->map(fn($r) => [
                'id' => $r->id,
                'room_number' => $r->room_number,
                'floor' => $r->floor,
                'type' => $r->type,
            ]),
        ]);
    @endphp

    <div
        x-data="{
            properties: {{ json_encode($propertiesData) }},
            selectedPropertyId: '{{ old('property_id', $selectedPropertyId ?? ($properties->first()?->id ?? '')) }}',
            selectedRoomId: '{{ old('room_id', $selectedRoomId ?? '') }}',
            get availableRooms() {
                const prop = this.properties.find(p => String(p.id) === String(this.selectedPropertyId));
                return prop ? prop.rooms : [];
            }
        }"
        class="max-w-4xl mx-auto space-y-6"
    >
        <!-- Top Breadcrumbs & Header -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-2">
                        <a href="{{ route('owner.inventory.index') }}" class="hover:text-[#121417] transition-colors">Fasilitas & Inventaris</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <span class="text-[#121417] font-semibold">Tambah Barang</span>
                    </nav>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">Tambah Barang Inventaris</h1>
                    <p class="text-xs sm:text-sm text-[#6B6B6B] mt-0.5">
                        Daftarkan perabot, peralatan, atau aset fasilitas di properti kos Anda.
                    </p>
                </div>

                <x-ui.button href="{{ route('owner.inventory.index') }}" variant="outline" size="sm" class="self-start sm:self-auto gap-1.5">
                    <x-ui.icon name="arrow-left" class="w-4 h-4" />
                    <span>Kembali</span>
                </x-ui.button>
            </div>
        </div>

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-[#FEE2E2] border border-[#FECACA] space-y-1">
                <div class="flex items-center gap-2 font-bold text-xs text-[#B91C1C]">
                    <x-ui.icon name="alert-triangle" class="w-4 h-4 shrink-0" />
                    <span>Terdapat kesalahan pada input form:</span>
                </div>
                <ul class="list-disc list-inside text-xs font-medium text-[#B91C1C] pl-2 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form Card Container -->
        <form method="POST" action="{{ route('owner.inventory.store') }}" class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-6">
            @csrf

            <!-- Section 1: Lokasi & Penempatan -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                    <span class="w-6 h-6 rounded-lg bg-[#F4F2EC] text-[#121417] text-xs font-bold flex items-center justify-center">1</span>
                    <h2 class="text-sm font-bold text-[#121417]">Lokasi & Penempatan Aset</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Properti -->
                    <div>
                        <label for="property_id" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Properti Kos <span class="text-[#B91C1C]">*</span>
                        </label>
                        <select
                            id="property_id"
                            name="property_id"
                            x-model="selectedPropertyId"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        >
                            <option value="">Pilih Properti</option>
                            <template x-for="prop in properties" :key="prop.id">
                                <option :value="prop.id" x-text="prop.name" :selected="String(prop.id) === String(selectedPropertyId)"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Kamar (Optional) -->
                    <div>
                        <label for="room_id" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Unit Kamar <span class="text-[11px] font-normal text-[#6B6B6B]">(Kosongkan jika di area umum)</span>
                        </label>
                        <select
                            id="room_id"
                            name="room_id"
                            x-model="selectedRoomId"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        >
                            <option value="">Area Umum / Seluruh Properti</option>
                            <template x-for="r in availableRooms" :key="r.id">
                                <option :value="r.id" x-text="'Kamar ' + r.room_number + ' (Lt. ' + r.floor + ' • ' + r.type + ')'" :selected="String(r.id) === String(selectedRoomId)"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Lokasi Spesifik -->
                    <div class="sm:col-span-2">
                        <label for="location" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Keterangan Lokasi Spesifik
                        </label>
                        <input
                            type="text"
                            id="location"
                            name="location"
                            value="{{ old('location') }}"
                            placeholder="Contoh: Kamar Mandi Dalam, Balkon Lantai 2, Dapur Bersama"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                        />
                    </div>
                </div>
            </div>

            <!-- Section 2: Identitas & Spesifikasi Barang -->
            <div class="space-y-4 pt-2">
                <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                    <span class="w-6 h-6 rounded-lg bg-[#F4F2EC] text-[#121417] text-xs font-bold flex items-center justify-center">2</span>
                    <h2 class="text-sm font-bold text-[#121417]">Spesifikasi & Identitas Barang</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Nama Barang -->
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Nama Barang / Aset <span class="text-[#B91C1C]">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            placeholder="Contoh: AC Daikin 1 PK, Kasur Spring Bed Queen, Meja Belajar"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                        />
                    </div>

                    <!-- Kategori -->
                    <div>
                        <label for="category" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Kategori <span class="text-[#B91C1C]">*</span>
                        </label>
                        <select
                            id="category"
                            name="category"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        >
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Merek -->
                    <div>
                        <label for="brand" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Merek / Pabrikan
                        </label>
                        <input
                            type="text"
                            id="brand"
                            name="brand"
                            value="{{ old('brand') }}"
                            placeholder="Contoh: Daikin, Olympic, Sharp, LG"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                        />
                    </div>

                    <!-- Model -->
                    <div>
                        <label for="model" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Model / Tipe
                        </label>
                        <input
                            type="text"
                            id="model"
                            name="model"
                            value="{{ old('model') }}"
                            placeholder="Contoh: FTKQ25SVM4, Standar 120x200"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                        />
                    </div>

                    <!-- Nomor Seri / Kode Aset -->
                    <div>
                        <label for="serial_number" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Nomor Seri / Kode Aset
                        </label>
                        <input
                            type="text"
                            id="serial_number"
                            name="serial_number"
                            value="{{ old('serial_number') }}"
                            placeholder="Contoh: DKN00123 / AST-A3-01"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-mono font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                        />
                    </div>

                    <!-- Jumlah Unit -->
                    <div>
                        <label for="quantity" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Jumlah Unit <span class="text-[#B91C1C]">*</span>
                        </label>
                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            min="1"
                            max="10000"
                            value="{{ old('quantity', 1) }}"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-mono font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                        />
                    </div>

                    <!-- Kondisi Barang -->
                    <div class="sm:col-span-2">
                        <label for="condition" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Kondisi Barang Saat Ini <span class="text-[#B91C1C]">*</span>
                        </label>
                        <select
                            id="condition"
                            name="condition"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        >
                            @foreach($conditions as $cond)
                                <option value="{{ $cond->value }}" {{ old('condition', 'baik') === $cond->value ? 'selected' : '' }}>
                                    {{ $cond->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3: Pembelian, Garansi & Pemeliharaan -->
            <div class="space-y-4 pt-2">
                <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-3">
                    <span class="w-6 h-6 rounded-lg bg-[#F4F2EC] text-[#121417] text-xs font-bold flex items-center justify-center">3</span>
                    <h2 class="text-sm font-bold text-[#121417]">Pembelian & Masa Garansi</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Harga Pembelian -->
                    <div>
                        <label for="purchase_price" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Harga Beli (Rp)
                        </label>
                        <input
                            type="number"
                            id="purchase_price"
                            name="purchase_price"
                            step="1000"
                            min="0"
                            value="{{ old('purchase_price') }}"
                            placeholder="Contoh: 3500000"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-mono font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                        />
                    </div>

                    <!-- Tanggal Pembelian -->
                    <div>
                        <label for="purchase_date" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Tanggal Pembelian
                        </label>
                        <input
                            type="date"
                            id="purchase_date"
                            name="purchase_date"
                            value="{{ old('purchase_date') }}"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        />
                    </div>

                    <!-- Garansi Sampai -->
                    <div>
                        <label for="warranty_until" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Masa Garansi Sampai
                        </label>
                        <input
                            type="date"
                            id="warranty_until"
                            name="warranty_until"
                            value="{{ old('warranty_until') }}"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        />
                    </div>

                    <!-- Terakhir Dicek -->
                    <div>
                        <label for="last_checked_at" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Terakhir Dicek
                        </label>
                        <input
                            type="date"
                            id="last_checked_at"
                            name="last_checked_at"
                            value="{{ old('last_checked_at', now()->toDateString()) }}"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        />
                    </div>
                </div>
            </div>

            <!-- Section 4: Catatan Tambahan -->
            <div class="space-y-1.5 pt-2 border-t border-[#DEDAD1]">
                <label for="notes" class="block text-xs font-semibold text-[#121417]">
                    Catatan Tambahan & Riwayat Servis
                </label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="3"
                    placeholder="Contoh: Rutin cuci AC tiap 4 bulan, garansi resmi Daikin Indonesia."
                    class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] resize-none"
                >{{ old('notes') }}</textarea>
            </div>

            <!-- Form Action Footer -->
            <div class="pt-4 border-t border-[#DEDAD1] flex items-center justify-end gap-3">
                <x-ui.button
                    href="{{ route('owner.inventory.index') }}"
                    variant="outline"
                >
                    Batal
                </x-ui.button>
                <x-ui.button
                    type="submit"
                    variant="primary"
                >
                    Simpan Barang Inventaris
                </x-ui.button>
            </div>
        </form>
    </div>
</x-layouts.owner>
