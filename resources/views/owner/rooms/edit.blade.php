<x-layouts.owner title="Edit Kamar {{ $room->room_number }} — {{ $property->name }}" activeNav="properties">
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
                <a href="{{ route('owner.properties.rooms.show', [$property, $room]) }}" class="hover:text-[#121417] transition-colors">Kamar {{ $room->room_number }}</a>
                <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="font-medium text-[#121417]">Edit Kamar</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-[#121417]">Edit Kamar {{ $room->room_number }}</h1>
            <p class="text-xs text-[#6B6B6B]">Perbarui data kamar pada <strong class="text-[#121417]">{{ $property->name }}</strong>.</p>
        </div>

        @if(session('error'))
            <x-ui.alert type="danger" :dismissible="true">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        @if($hasActiveContract)
            <div class="p-4 rounded-xl bg-[#FEF3C7] border border-[#FDE68A] flex items-start gap-3">
                <svg class="w-5 h-5 text-[#D97706] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="text-xs text-[#92400E] space-y-0.5">
                    <p class="font-semibold text-[#78350F]">Kamar ini memiliki Kontrak Sewa Aktif</p>
                    <p class="leading-relaxed">
                        Status kamar tidak dapat diubah menjadi <strong>Kosong</strong> saat masih terdapat kontrak sewa yang aktif. Kamar hanya dapat berstatus <strong>Terisi</strong> atau <strong>Perbaikan</strong>.
                    </p>
                </div>
            </div>
        @endif

        <!-- Form Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <form method="POST" action="{{ route('owner.properties.rooms.update', [$property, $room]) }}" class="space-y-6">
                @csrf
                @method('PUT')

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
                            value="{{ old('room_number', $room->room_number) }}"
                            required
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('room_number') border-red-500 @enderror"
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
                            value="{{ old('floor', $room->floor) }}"
                            required
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
                            value="{{ old('type', $room->type) }}"
                            required
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('type') border-red-500 @enderror"
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
                            value="{{ old('price', (int) $room->price) }}"
                            required
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-mono text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('price') border-red-500 @enderror"
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
                            value="{{ old('size', $room->size) }}"
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
                            value="{{ old('capacity', $room->capacity) }}"
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
                            <option value="kosong" {{ old('status', $room->status) === 'kosong' ? 'selected' : '' }}>
                                Kosong (Siap Huni)
                            </option>
                            <option value="terisi" {{ old('status', $room->status) === 'terisi' ? 'selected' : '' }}>
                                Terisi
                            </option>
                            <option value="perbaikan" {{ old('status', $room->status) === 'perbaikan' ? 'selected' : '' }}>
                                Perbaikan (Maintenance)
                            </option>
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
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all leading-relaxed @error('description') border-red-500 @enderror"
                        >{{ old('description', $room->description) }}</textarea>
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
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all leading-relaxed @error('internal_notes') border-red-500 @enderror"
                        >{{ old('internal_notes', $room->internal_notes) }}</textarea>
                        @error('internal_notes')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Fasilitas Kamar Checkbox List -->
                @if($facilities->isNotEmpty())
                    @php
                        $attachedFacilityIds = $room->facilities->pluck('id')->toArray();
                    @endphp
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
                                        {{ in_array($facility->id, old('facilities', $attachedFacilityIds)) ? 'checked' : '' }}
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
                        href="{{ route('owner.properties.rooms.show', [$property, $room]) }}"
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

        <!-- Danger Zone: Delete Room Safely -->
        <div class="bg-white border border-red-200 rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <div class="flex items-start justify-between gap-4 flex-col sm:flex-row">
                <div class="space-y-1">
                    <h3 class="text-sm font-semibold text-red-600 flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Hapus Kamar</span>
                    </h3>
                    <p class="text-xs text-[#6B6B6B] max-w-xl leading-relaxed">
                        Kamar yang memiliki riwayat kontrak sewa tidak dapat dihapus demi integritas data dan keuangan kos.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('owner.properties.rooms.destroy', [$property, $room]) }}"
                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus kamar {{ $room->room_number }}?');"
                >
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-xl border border-red-300 bg-red-50 hover:bg-red-100 text-red-700 font-semibold text-xs transition-colors cursor-pointer whitespace-nowrap active:scale-[0.98]"
                    >
                        Hapus Kamar Ini
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.owner>
