<x-layouts.owner title="Edit Fasilitas — HUNI" activeNav="inventory">
    <div class="max-w-2xl mx-auto space-y-6">
        <!-- Top Breadcrumbs & Header -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-2">
                        <a href="{{ route('owner.facilities.index') }}" class="hover:text-[#121417] transition-colors">Katalog Fasilitas</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <span class="text-[#121417] font-semibold">Edit {{ $facility->name }}</span>
                    </nav>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">Edit Fasilitas</h1>
                    <p class="text-xs sm:text-sm text-[#6B6B6B] mt-0.5">
                        Perbarui nama, kategori, atau deskripsi fasilitas standar ini.
                    </p>
                </div>

                <x-ui.button href="{{ route('owner.facilities.index') }}" variant="outline" size="sm" class="self-start sm:self-auto gap-1.5">
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

        <form method="POST" action="{{ route('owner.facilities.update', $facility) }}" class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-5">
            @csrf
            @method('PUT')

            <!-- Nama Fasilitas -->
            <div>
                <label for="name" class="block text-xs font-semibold text-[#121417] mb-1.5">
                    Nama Fasilitas <span class="text-[#B91C1C]">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $facility->name) }}"
                    required
                    placeholder="Contoh: WiFi 100 Mbps, AC Inverter, Smart Lock, Kolam Renang"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                />
            </div>

            <!-- Kategori -->
            <div>
                <label for="category" class="block text-xs font-semibold text-[#121417] mb-1.5">
                    Kategori Penempatan <span class="text-[#B91C1C]">*</span>
                </label>
                <select
                    id="category"
                    name="category"
                    required
                    class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                >
                    @foreach($categories as $cat)
                        <option value="{{ $cat->value }}" {{ old('category', $facility->category) === $cat->value ? 'selected' : '' }}>
                            {{ $cat->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Ikon / Emoji -->
            <div>
                <label for="icon" class="block text-xs font-semibold text-[#121417] mb-1.5">
                    Kode Ikon atau Simbol
                </label>
                <input
                    type="text"
                    id="icon"
                    name="icon"
                    value="{{ old('icon', $facility->icon) }}"
                    placeholder="Contoh: wifi, ac, lock, sparkles"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                />
            </div>

            <!-- Deskripsi -->
            <div>
                <label for="description" class="block text-xs font-semibold text-[#121417] mb-1.5">
                    Deskripsi Ringkas
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    placeholder="Penjelasan ringkas mengenai fasilitas ini..."
                    class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] resize-none"
                >{{ old('description', $facility->description) }}</textarea>
            </div>

            <!-- Form Action Footer -->
            <div class="pt-4 border-t border-[#DEDAD1] flex items-center justify-end gap-3">
                <x-ui.button
                    href="{{ route('owner.facilities.index') }}"
                    variant="outline"
                >
                    Batal
                </x-ui.button>
                <x-ui.button
                    type="submit"
                    variant="primary"
                >
                    Simpan Perubahan
                </x-ui.button>
            </div>
        </form>
    </div>
</x-layouts.owner>
