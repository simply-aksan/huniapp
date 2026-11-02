<x-layouts.owner title="Tambah Kamar — {{ $property->name }}" activeNav="properties">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Breadcrumb & Header -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-1.5">
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#6B6B6B]">
                <a href="{{ route('owner.properties.index') }}" class="hover:text-[#121417] transition-colors">Properti &amp; Kos</a>
                <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <a href="{{ route('owner.properties.show', $property) }}" class="hover:text-[#121417] transition-colors">{{ $property->name }}</a>
                <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="font-medium text-[#121417]">Tambah Kamar Baru</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-[#121417]">Tambah Kamar Baru</h1>
            <p class="text-xs text-[#6B6B6B]">Daftarkan unit kamar baru untuk properti <strong class="text-[#121417]">{{ $property->name }}</strong>.</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <form method="POST" action="{{ route('owner.properties.rooms.store', $property) }}" class="space-y-6">
                @csrf

                <!-- Nomor Kamar & Lantai (2 Kolom) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="room_number" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Nomor / Kode Kamar <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="room_number"
                            id="room_number"
                            value="{{ old('room_number') }}"
                            required
                            placeholder="Contoh: A3, 101, B2"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('room_number') border-red-500 @enderror"
                        />
                        @error('room_number')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="floor" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Lantai <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="floor"
                            id="floor"
                            min="1"
                            value="{{ old('floor', 1) }}"
                            required
                            placeholder="Contoh: 1"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-mono text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('floor') border-red-500 @enderror"
                        />
                        @error('floor')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Tipe & Harga Sewa (2 Kolom) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="type" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Tipe / Kategori Kamar <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="type"
                            id="type"
                            value="{{ old('type', 'Standard') }}"
                            required
                            placeholder="Contoh: Standard, Deluxe, VIP"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('type') border-red-500 @enderror"
                        />
                        @error('type')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="price" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Harga Sewa per Bulan (Rp) <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="price"
                            id="price"
                            min="0"
                            step="50000"
                            value="{{ old('price', 1500000) }}"
                            required
                            placeholder="Contoh: 1500000"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-mono text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('price') border-red-500 @enderror"
                        />
                        @error('price')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Ukuran, Kapasitas & Status (3 Kolom) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="size" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Ukuran Kamar
                        </label>
                        <input
                            type="text"
                            name="size"
                            id="size"
                            value="{{ old('size') }}"
                            placeholder="Contoh: 3 x 3 m (9 m²)"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('size') border-red-500 @enderror"
                        />
                        @error('size')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="capacity" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Kapasitas (Orang) <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="capacity"
                            id="capacity"
                            min="1"
                            value="{{ old('capacity', 1) }}"
                            required
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-mono text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('capacity') border-red-500 @enderror"
                        />
                        @error('capacity')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Status Kamar <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="status"
                            id="status"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] cursor-pointer"
                        >
                            <option value="kosong" {{ old('status', 'kosong') === 'kosong' ? 'selected' : '' }}>Kosong (Siap Huni)</option>
                            <option value="terisi" {{ old('status') === 'terisi' ? 'selected' : '' }}>Terisi</option>
                            <option value="perbaikan" {{ old('status') === 'perbaikan' ? 'selected' : '' }}>Perbaikan (Maintenance)</option>
                        </select>
                        @error('status')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Deskripsi & Catatan Internal (2 Kolom) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="description" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Deskripsi Kamar
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            placeholder="Contoh: Kamar menghadap taman, sirkulasi udara baik."
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all leading-relaxed @error('description') border-red-500 @enderror"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="internal_notes" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Catatan Internal Pemilik
                        </label>
                        <textarea
                            name="internal_notes"
                            id="internal_notes"
                            rows="3"
                            placeholder="Catatan rahasia pengelola (misal: AC baru servis Agustus, stop kontak dekat meja agak longgar)"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all leading-relaxed @error('internal_notes') border-red-500 @enderror"
                        >{{ old('internal_notes') }}</textarea>
                        @error('internal_notes')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Fasilitas Kamar Checkbox List -->
                @if($facilities->isNotEmpty())
                    <div class="pt-2">
                        <label class="block text-xs font-semibold text-[#121417] mb-2.5">
                            Fasilitas Kamar
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                            @foreach($facilities as $facility)
                                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] hover:border-[#121417]/40 cursor-pointer select-none transition-all">
                                    <input
                                        type="checkbox"
                                        name="facilities[]"
                                        value="{{ $facility->id }}"
                                        {{ in_array($facility->id, old('facilities', [])) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded border border-[#DEDAD1] text-[#121417] focus:ring-[#121417]"
                                    />
                                    <span class="text-xs font-medium text-[#121417] truncate">{{ $facility->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Buttons -->
                <div class="pt-6 border-t border-[#DEDAD1] flex items-center justify-end gap-3">
                    <a
                        href="{{ route('owner.properties.show', $property) }}"
                        class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-xs active:scale-[0.98] transition-all cursor-pointer"
                    >
                        Simpan Kamar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.owner>
