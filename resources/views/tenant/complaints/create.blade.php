<x-layouts.tenant title="Buat Pengaduan Baru — HUNI" activeNav="complaints">
    <div class="space-y-6">
        <!-- Header Banner Card -->
        <div class="relative bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs font-medium text-[#5E6470] mb-2">
                        <a href="{{ route('tenant.dashboard') }}" class="hover:text-[#121417] transition-colors">Beranda</a>
                        <span>›</span>
                        <a href="{{ route('tenant.complaints.index') }}" class="hover:text-[#121417] transition-colors">Komplain & Pengaduan</a>
                        <span>›</span>
                        <span class="text-[#121417] font-semibold">Buat Pengaduan Baru</span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-bold text-[#121417] tracking-tight">
                        BUAT PENGADUAN BARU
                    </h1>
                    <p class="text-sm text-[#5E6470] mt-1">
                        Sampaikan keluhan atau kendala fasilitas kos. Tim pemilik kos akan segera menindaklanjutinya.
                    </p>
                </div>

                <div>
                    <a
                        href="{{ route('tenant.complaints.index') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-[#DEDAD1] bg-white text-xs font-semibold text-[#121417] hover:bg-[#F4F1EA] transition-all cursor-pointer shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                    >
                        <x-ui.icon name="arrow-left" class="w-3.5 h-3.5 text-[#5E6470]" />
                        <span>Kembali ke Daftar</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Global Errors -->
        @if($errors->any())
            <x-ui.alert type="error">
                <p class="font-semibold mb-1">Mohon periksa kembali formulir pengaduan:</p>
                <ul class="list-disc list-inside space-y-0.5 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @if($activeContracts->isEmpty())
            <!-- No Active Leases Warning -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-8 text-center shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                <div class="w-12 h-12 rounded-xl bg-[#FEF3C7] text-amber-700 flex items-center justify-center mx-auto">
                    <x-ui.icon name="exclamation-triangle" class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-[#121417]">Tidak Ditemukan Kontrak Sewa Aktif</h3>
                    <p class="text-xs text-[#5E6470] mt-1 max-w-md mx-auto">
                        Pengaduan fasilitas hanya dapat diajukan oleh penyewa yang memiliki kontrak kamar kos aktif. Jika Anda merasa sudah menyewa, silakan hubungi pemilik kos untuk memperbarui status kontrak.
                    </p>
                </div>
                <div>
                    <a
                        href="{{ route('tenant.dashboard') }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#121417] hover:bg-[#20242B] text-white text-xs font-semibold shadow-[0_1px_2px_rgba(18,20,23,0.08)] transition-all"
                    >
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        @else
            <!-- Form Card -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <form
                    action="{{ route('tenant.complaints.store') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="space-y-6"
                    x-data="{
                        selectedContract: '{{ old('property_id') && old('room_id') ? old('property_id') . ':' . old('room_id') : ($activeContracts->count() === 1 ? $activeContracts->first()->property_id . ':' . $activeContracts->first()->room_id : '') }}',
                        propertyId: '{{ old('property_id', $activeContracts->count() === 1 ? $activeContracts->first()->property_id : '') }}',
                        roomId: '{{ old('room_id', $activeContracts->count() === 1 ? $activeContracts->first()->room_id : '') }}',
                        updateContract(val) {
                            if (!val) {
                                this.propertyId = '';
                                this.roomId = '';
                                return;
                            }
                            const parts = val.split(':');
                            this.propertyId = parts[0];
                            this.roomId = parts[1];
                        }
                    }"
                >
                    @csrf

                    <!-- Hidden inputs populated via selection -->
                    <input type="hidden" name="property_id" :value="propertyId">
                    <input type="hidden" name="room_id" :value="roomId">

                    <!-- Section: Location Selection -->
                    <div class="space-y-1.5">
                        <label for="contract_select" class="block text-xs font-semibold uppercase tracking-wider text-[#121417]">
                            Pilih Kamar & Properti Kos Yang Diadukan <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="contract_select"
                            x-model="selectedContract"
                            x-on:change="updateContract($event.target.value)"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-none focus:bg-white focus:border-[#121417] transition-all cursor-pointer"
                            required
                        >
                            @if($activeContracts->count() > 1)
                                <option value="">-- Pilih Kamar Kos Terkait --</option>
                            @endif
                            @foreach($activeContracts as $contract)
                                <option value="{{ $contract->property_id }}:{{ $contract->room_id }}">
                                    {{ $contract->property?->name }} — Kamar {{ $contract->room?->room_number }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-[#5E6470]">
                            Hanya menampilkan kamar dari kontrak sewa aktif Anda saat ini.
                        </p>
                    </div>

                    <!-- Grid: Category & Priority -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Category -->
                        <div class="space-y-1.5">
                            <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-[#121417]">
                                Kategori Masalah <span class="text-red-500">*</span>
                            </label>
                            <select
                                name="category"
                                id="category"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-none focus:bg-white focus:border-[#121417] transition-all cursor-pointer"
                                required
                            >
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->value }}" {{ old('category') === $cat->value ? 'selected' : '' }}>
                                        {{ $cat->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Priority -->
                        <div class="space-y-1.5">
                            <label for="priority" class="block text-xs font-semibold uppercase tracking-wider text-[#121417]">
                                Tingkat Urgensi / Prioritas <span class="text-red-500">*</span>
                            </label>
                            <select
                                name="priority"
                                id="priority"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-none focus:bg-white focus:border-[#121417] transition-all cursor-pointer"
                                required
                            >
                                @foreach($priorities as $prio)
                                    <option value="{{ $prio->value }}" {{ old('priority', \App\Enums\ComplaintPriority::Sedang->value) === $prio->value ? 'selected' : '' }}>
                                        {{ $prio->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="space-y-1.5">
                        <label for="title" class="block text-xs font-semibold uppercase tracking-wider text-[#121417]">
                            Judul Pengaduan <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="title"
                            id="title"
                            value="{{ old('title') }}"
                            placeholder="Contoh: Keran air kamar mandi bocor, Lampu utama mati"
                            class="w-full px-3.5 py-2 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] placeholder-[#5E6470] focus:outline-none focus:bg-white focus:border-[#121417] transition-all"
                            required
                            minlength="5"
                            maxlength="255"
                        />
                        <p class="text-[11px] text-[#5E6470]">
                            Tuliskan ringkasan singkat dari kendala yang dialami (minimal 5 karakter).
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-1.5">
                        <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-[#121417]">
                            Rincian / Deskripsi Lengkap <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="5"
                            placeholder="Jelaskan secara detail bagian mana yang rusak, kapan mulai terjadi, dan dampaknya..."
                            class="w-full p-3.5 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs text-[#121417] placeholder-[#5E6470] focus:outline-none focus:bg-white focus:border-[#121417] transition-all"
                            required
                            minlength="10"
                            maxlength="3000"
                        >{{ old('description') }}</textarea>
                        <p class="text-[11px] text-[#5E6470]">
                            Semakin detail informasi yang Anda berikan, semakin cepat pemilik kos dapat mempersiapkan perbaikan (minimal 10 karakter).
                        </p>
                    </div>

                    <!-- Photo Upload (Optional) -->
                    <div class="space-y-1.5">
                        <label for="photo" class="block text-xs font-semibold uppercase tracking-wider text-[#121417]">
                            Foto Bukti Pendukung (Opsional)
                        </label>
                        <div class="p-4 rounded-xl border border-dashed border-[#DEDAD1] hover:border-[#121417] bg-[#F9F8F5] space-y-2 transition-all">
                            <input
                                type="file"
                                name="photo"
                                id="photo"
                                accept="image/jpeg,image/png,image/webp"
                                class="w-full text-xs text-[#5E6470] file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-[#DEDAD1] file:text-xs file:font-semibold file:bg-white file:text-[#121417] file:cursor-pointer hover:file:bg-[#F4F1EA]"
                            />
                            <p class="text-[11px] text-[#5E6470]">
                                Format didukung: <strong>JPEG, PNG, WebP</strong>. Ukuran berkas maksimal <strong>5 MB</strong>. File akan disimpan secara aman di private storage.
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4 border-t border-[#DEDAD1] flex flex-col sm:flex-row items-center justify-end gap-2.5">
                        <a
                            href="{{ route('tenant.complaints.index') }}"
                            class="w-full sm:w-auto px-4 py-2 rounded-lg border border-[#DEDAD1] bg-white text-[#121417] font-semibold text-xs text-center hover:bg-[#F4F1EA] transition-all cursor-pointer"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="w-full sm:w-auto px-5 py-2 rounded-lg bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs text-center shadow-[0_1px_2px_rgba(18,20,23,0.08)] transition-all cursor-pointer inline-flex items-center justify-center gap-1.5"
                        >
                            <x-ui.icon name="paper-airplane" class="w-4 h-4 text-[#C7F36B]" />
                            <span>Kirim Pengaduan</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-layouts.tenant>
