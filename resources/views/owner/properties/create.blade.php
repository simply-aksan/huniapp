<x-layouts.owner title="Tambah Properti Baru — HUNI" activeNav="properties">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Breadcrumb & Header -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-1.5">
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#6B6B6B]">
                <a href="{{ route('owner.properties.index') }}" class="hover:text-[#121417] transition-colors">Properti &amp; Kos</a>
                <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="font-medium text-[#121417]">Tambah Properti Baru</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-[#121417]">Tambah Properti Baru</h1>
            <p class="text-xs text-[#6B6B6B]">Lengkapi data properti kos Anda di bawah ini.</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <form method="POST" action="{{ route('owner.properties.store') }}" class="space-y-6">
                @csrf

                <!-- Nama Properti -->
                <div>
                    <label for="name" class="block text-xs font-semibold text-[#121417] mb-1.5">
                        Nama Properti / Kos <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name') }}"
                        placeholder="Contoh: Kos Harmoni Putri"
                        required
                        class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('name') border-red-500 @enderror"
                    />
                    @error('name')
                        <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slug (Opsional) -->
                <div>
                    <label for="slug" class="block text-xs font-semibold text-[#121417] mb-1.5">
                        Slug URL <span class="text-[11px] font-normal text-[#6B6B6B]">(Opsional - otomatis dibuat dari nama jika dikosongkan)</span>
                    </label>
                    <input
                        type="text"
                        name="slug"
                        id="slug"
                        value="{{ old('slug') }}"
                        placeholder="contoh: kos-harmoni-putri"
                        class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-mono text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('slug') border-red-500 @enderror"
                    />
                    @error('slug')
                        <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Alamat Lengkap -->
                <div>
                    <label for="address" class="block text-xs font-semibold text-[#121417] mb-1.5">
                        Alamat Lengkap <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        name="address"
                        id="address"
                        rows="3"
                        required
                        placeholder="Contoh: Jl. Pogung Baru Blok F No. 12, Sinduadi"
                        class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all leading-relaxed @error('address') border-red-500 @enderror"
                    >{{ old('address') }}</textarea>
                    @error('address')
                        <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kota, Kecamatan, Kode Pos (3 Kolom) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="city" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Kota / Kabupaten <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="city"
                            id="city"
                            value="{{ old('city', 'Sleman') }}"
                            required
                            placeholder="Contoh: Sleman"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('city') border-red-500 @enderror"
                        />
                        @error('city')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="district" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Kecamatan
                        </label>
                        <input
                            type="text"
                            name="district"
                            id="district"
                            value="{{ old('district') }}"
                            placeholder="Contoh: Mlati"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('district') border-red-500 @enderror"
                        />
                        @error('district')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="postal_code" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Kode Pos
                        </label>
                        <input
                            type="text"
                            name="postal_code"
                            id="postal_code"
                            value="{{ old('postal_code') }}"
                            placeholder="Contoh: 55284"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-mono text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('postal_code') border-red-500 @enderror"
                        />
                        @error('postal_code')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Deskripsi Properti -->
                <div>
                    <label for="description" class="block text-xs font-semibold text-[#121417] mb-1.5">
                        Deskripsi Singkat
                    </label>
                    <textarea
                        name="description"
                        id="description"
                        rows="3"
                        placeholder="Contoh: Kos eksklusif dekat kampus UGM, lingkungan tenang dan aman dengan penjagaan 24 jam."
                        class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all leading-relaxed @error('description') border-red-500 @enderror"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status & Primary Checkbox -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                    <div>
                        <label for="status" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Status Properti <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="status"
                            id="status"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] cursor-pointer"
                        >
                            <option value="aktif" {{ old('status', 'aktif') === 'aktif' ? 'selected' : '' }}>Aktif (Beroperasi)</option>
                            <option value="nonaktif" {{ old('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif (Tidak Beroperasi)</option>
                        </select>
                        @error('status')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center pt-2 sm:pt-6">
                        <label class="flex items-start gap-3 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                name="is_primary"
                                value="1"
                                {{ old('is_primary') ? 'checked' : '' }}
                                class="mt-0.5 w-4 h-4 rounded border border-[#DEDAD1] text-[#121417] focus:ring-[#121417]"
                            />
                            <div>
                                <span class="text-xs font-semibold text-[#121417] block">Jadikan Properti Utama</span>
                                <span class="text-[11px] text-[#6B6B6B] block">Ditampilkan di ringkasan awal dashboard utama</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="pt-6 border-t border-[#DEDAD1] flex items-center justify-end gap-3">
                    <a
                        href="{{ route('owner.properties.index') }}"
                        class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-xs active:scale-[0.98] transition-all cursor-pointer"
                    >
                        Simpan Properti
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.owner>
