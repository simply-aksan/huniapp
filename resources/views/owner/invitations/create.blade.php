<x-layouts.owner title="Buat Undangan Baru — HUNI" activeNav="invitations">
    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Breadcrumb & Header -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-1.5">
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#6B6B6B]">
                <a href="{{ route('owner.tenants.index') }}" class="hover:text-[#121417] transition-colors">Penghuni</a>
                <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <a href="{{ route('owner.invitations.index') }}" class="hover:text-[#121417] transition-colors">Undangan</a>
                <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="font-medium text-[#121417]">Buat Undangan Baru</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-[#121417]">Buat Undangan Calon Penyewa</h1>
            <p class="text-xs text-[#6B6B6B]">
                Kirimkan tautan pendaftaran khusus kepada calon penghuni untuk kamar yang tersedia.
            </p>
        </div>

        @if(session('error'))
            <x-ui.alert type="danger" :dismissible="true">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        @php
            $initialProperty = old('property_id', $preselectedPropertyId ?? ($properties->first()?->id ?? ''));
            $initialRoom = old('room_id', $preselectedRoomId ?? '');
        @endphp

        <!-- Form Card with Alpine for Dynamic Room Filtering -->
        <div
            x-data="{
                selectedProperty: '{{ $initialProperty }}',
                roomsByProperty: {
                    @foreach($properties as $prop)
                        '{{ $prop->id }}': [
                            @foreach($prop->rooms as $r)
                                { id: '{{ $r->id }}', number: '{{ $r->room_number }}', floor: '{{ $r->floor }}', price: '{{ number_format((float) $r->price, 0, ',', '.') }}', type: '{{ $r->type }}' },
                            @endforeach
                        ],
                    @endforeach
                },
                selectedRoom: '{{ $initialRoom }}'
            }"
            class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)]"
        >
            <form method="POST" action="{{ route('owner.invitations.store') }}" class="space-y-6">
                @csrf

                <!-- Properti & Kamar (2 Kolom) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="property_id" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Pilih Properti <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="property_id"
                            id="property_id"
                            x-model="selectedProperty"
                            x-on:change="selectedRoom = ''"
                            required
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all cursor-pointer @error('property_id') border-red-500 @enderror"
                        >
                            <option value="">-- Pilih Properti --</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}">
                                    {{ $property->name }} ({{ $property->rooms->count() }} kamar kosong)
                                </option>
                            @endforeach
                        </select>
                        @error('property_id')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="room_id" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Pilih Kamar Tujuan <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="room_id"
                            id="room_id"
                            x-model="selectedRoom"
                            required
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all cursor-pointer @error('room_id') border-red-500 @enderror"
                        >
                            <option value="">-- Pilih Kamar --</option>
                            <template x-if="selectedProperty && roomsByProperty[selectedProperty]">
                                <template x-for="r in roomsByProperty[selectedProperty]" :key="r.id">
                                    <option :value="r.id" x-text="`Kamar ${r.number} (Lt. ${r.floor}) - Rp ${r.price}`"></option>
                                </template>
                            </template>
                        </select>
                        @error('room_id')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Nama & Email Calon Penyewa (2 Kolom) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Nama Calon Penyewa <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name') }}"
                            required
                            placeholder="Contoh: Budi Santoso"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('name') border-red-500 @enderror"
                        />
                        @error('name')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Alamat Email Calon Penyewa <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email') }}"
                            required
                            placeholder="Contoh: calonpenyewa@gmail.com"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('email') border-red-500 @enderror"
                        />
                        <span class="text-[11px] text-[#6B6B6B] mt-1 block">Calon penyewa wajib menggunakan email ini saat menerima undangan.</span>
                        @error('email')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Nomor Telepon & Masa Berlaku (2 Kolom) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="phone" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Nomor WhatsApp / HP
                        </label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            value="{{ old('phone') }}"
                            placeholder="Contoh: 081234567890"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all @error('phone') border-red-500 @enderror"
                        />
                        @error('phone')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="valid_days" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Masa Berlaku Undangan <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="valid_days"
                            id="valid_days"
                            class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all cursor-pointer"
                        >
                            <option value="3" {{ old('valid_days') == '3' ? 'selected' : '' }}>3 Hari</option>
                            <option value="7" {{ old('valid_days', '7') == '7' ? 'selected' : '' }}>7 Hari (Direkomendasikan)</option>
                            <option value="14" {{ old('valid_days') == '14' ? 'selected' : '' }}>14 Hari</option>
                            <option value="30" {{ old('valid_days') == '30' ? 'selected' : '' }}>30 Hari</option>
                        </select>
                        @error('valid_days')
                            <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Pesan / Catatan -->
                <div>
                    <label for="message" class="block text-xs font-semibold text-[#121417] mb-1.5">
                        Pesan Selamat Datang <span class="text-[11px] font-normal text-[#6B6B6B]">(Opsional)</span>
                    </label>
                    <textarea
                        name="message"
                        id="message"
                        rows="3"
                        placeholder="Contoh: Halo! Silakan selesaikan pendaftaran untuk konfirmasi kamar kos Anda."
                        class="w-full px-3.5 py-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all leading-relaxed @error('message') border-red-500 @enderror"
                    >{{ old('message') }}</textarea>
                    @error('message')
                        <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="pt-6 border-t border-[#DEDAD1] flex items-center justify-end gap-3">
                    <a
                        href="{{ route('owner.invitations.index') }}"
                        class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-xs active:scale-[0.98] transition-all cursor-pointer"
                    >
                        Buat Link Undangan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.owner>
