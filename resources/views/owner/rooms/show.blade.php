<x-layouts.owner title="Kamar {{ $room->room_number }} — {{ $property->name }}" activeNav="properties">
    <div class="space-y-6 max-w-7xl mx-auto">
        <!-- Top Breadcrumbs & Actions Bar -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#6B6B6B] flex-wrap">
                    <a href="{{ route('owner.properties.index') }}" class="hover:text-[#121417] transition-colors">Properti &amp; Kos</a>
                    <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <a href="{{ route('owner.properties.show', $property) }}" class="hover:text-[#121417] transition-colors">{{ $property->name }}</a>
                    <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="font-medium text-[#121417]">Kamar {{ $room->room_number }}</span>
                </nav>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold tracking-tight text-[#121417]">Kamar {{ $room->room_number }}</h1>
                    @if($room->status === 'terisi')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                            Terisi
                        </span>
                    @elseif($room->status === 'kosong')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#A5A096]"></span>
                            Kosong
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#D97706]"></span>
                            Perbaikan
                        </span>
                    @endif
                </div>
                <p class="text-xs text-[#6B6B6B]">
                    {{ $property->name }} • Lantai {{ $room->floor }} • Kategori {{ $room->type }}
                </p>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex items-center gap-2.5 shrink-0 self-start sm:self-auto">
                <a
                    href="{{ route('owner.properties.rooms.edit', [$property, $room]) }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                >
                    <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    <span>Edit Kamar</span>
                </a>
                <a
                    href="{{ route('owner.properties.show', $property) }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#F1EFEA] hover:bg-[#E5E2DA] border border-[#DEDAD1] font-semibold text-xs text-[#121417] transition-colors cursor-pointer"
                >
                    <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        @if(session('success'))
            <x-ui.alert type="success" :dismissible="true">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        <!-- Top Section: Photo Hero + Status & Tenant Card -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Room Image Card (7 Cols) -->
            <div class="lg:col-span-7 bg-white border border-[#DEDAD1] rounded-2xl overflow-hidden shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div class="relative h-64 sm:h-72 bg-[#F9F8F5] border-b border-[#DEDAD1] overflow-hidden flex items-center justify-center">
                    <img src="{{ asset('images/marketing/kos-medium.png') }}" alt="Foto Kamar {{ $room->room_number }}" class="w-full h-full object-cover" />
                    <div class="absolute bottom-3 left-3 bg-[#121417]/80 text-white text-xs font-medium px-3 py-1.5 rounded-xl border border-white/20 backdrop-blur-xs flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Foto Unit Kamar {{ $room->room_number }}</span>
                    </div>
                </div>

                <div class="p-4 sm:p-5 flex items-center justify-between bg-white text-xs text-[#6B6B6B]">
                    <span>Spesifikasi: {{ $room->type }} • Lantai {{ $room->floor }}</span>
                    <span class="text-[#121417] font-semibold">Kapasitas {{ $room->capacity }} Orang</span>
                </div>
            </div>

            <!-- Status & Tenant Info (5 Cols) -->
            <div class="lg:col-span-5 bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-[#DEDAD1]">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <h2 class="text-sm font-bold text-[#121417]">Status Kamar</h2>
                        </div>
                        @if($room->status === 'terisi')
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                                Terisi
                            </span>
                        @elseif($room->status === 'kosong')
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]">
                                Kosong
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">
                                Perbaikan
                            </span>
                        @endif
                    </div>

                    @if($room->status === 'terisi' && $currentTenant)
                        <div class="mb-5">
                            <p class="text-[11px] font-semibold text-[#6B6B6B] uppercase tracking-wider mb-2">Penghuni Saat Ini</p>
                            <div class="p-3.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-[#121417] text-white flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($currentTenant->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-[#121417]">{{ $currentTenant->name }}</p>
                                        <p class="text-[11px] text-[#6B6B6B]">
                                            @if($activeContract)
                                                Masuk {{ $activeContract->start_date?->translatedFormat('d M Y') }}
                                            @else
                                                {{ $currentTenant->phone }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3 Micro Metrics -->
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="p-2.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1]">
                                <span class="text-[10px] text-[#6B6B6B] block">Jatuh Tempo</span>
                                <span class="text-[11px] font-semibold text-[#121417] block mt-0.5">
                                    {{ $activeContract?->end_date?->translatedFormat('d M Y') ?? '-' }}
                                </span>
                            </div>

                            <div class="p-2.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1]">
                                <span class="text-[10px] text-[#6B6B6B] block">Kontrak</span>
                                <span class="text-[11px] font-semibold text-[#121417] block mt-0.5">
                                    {{ $activeContract?->duration_months ?? 12 }} Bulan
                                </span>
                            </div>

                            <div class="p-2.5 rounded-xl bg-[#E8F8D0]/40 border border-[#C7F36B]/60">
                                <span class="text-[10px] text-[#15803D] font-medium block">Harga/Bulan</span>
                                <span class="text-[11px] font-mono font-bold tabular-nums text-[#15803D] block mt-0.5">
                                    Rp {{ number_format((float) $room->price, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @else
                        <!-- Vacant or Maintenance state -->
                        <div class="text-center py-6 px-4 rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1] my-2">
                            <div class="w-10 h-10 rounded-full bg-white border border-[#DEDAD1] flex items-center justify-center text-[#6B6B6B] mx-auto mb-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-[#121417]">
                                {{ $room->status === 'kosong' ? 'Kamar Kosong & Siap Dihuni' : 'Kamar Sedang Dalam Perbaikan' }}
                            </h3>
                            <p class="text-xs text-[#6B6B6B] mt-1 max-w-xs mx-auto">
                                {{ $room->status === 'kosong' ? 'Unit kamar belum ditempati oleh penghuni manapun.' : 'Unit sedang tidak dapat disewakan hingga proses perbaikan selesai.' }}
                            </p>
                            <p class="text-xs font-mono font-bold text-[#121417] mt-3">
                                Tarif Sewa: Rp {{ number_format((float) $room->price, 0, ',', '.') }} / bulan
                            </p>
                        </div>
                    @endif
                </div>

                <div class="pt-4 border-t border-[#DEDAD1] flex items-center justify-between text-xs text-[#6B6B6B]">
                    <span>Ukuran: {{ $room->size ?? 'N/A' }}</span>
                    <span>Lantai {{ $room->floor }}</span>
                </div>
            </div>
        </div>

        <!-- Detail Tabs & Information Cards Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Informasi Kamar (4 Cols) -->
            <div class="lg:col-span-4 bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-[#DEDAD1]">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h2 class="text-sm font-bold text-[#121417]">Informasi Kamar</h2>
                        </div>
                        <a href="{{ route('owner.properties.rooms.edit', [$property, $room]) }}" class="text-xs font-semibold text-[#121417] hover:underline cursor-pointer">
                            Edit
                        </a>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Nomor Kamar</span>
                            <span class="font-bold text-[#121417]">{{ $room->room_number }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Properti</span>
                            <span class="font-semibold text-[#121417]">{{ $property->name }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Lantai</span>
                            <span class="font-medium text-[#121417]">Lantai {{ $room->floor }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Kategori / Tipe</span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-[#F1EFEA] border border-[#DEDAD1] text-[#121417]">{{ $room->type }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Harga Sewa / Bulan</span>
                            <span class="font-mono font-semibold tabular-nums text-[#121417]">Rp {{ number_format((float) $room->price, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Luas Kamar</span>
                            <span class="font-medium text-[#121417]">{{ $room->size ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Kapasitas</span>
                            <span class="font-medium text-[#121417]">{{ $room->capacity }} Orang</span>
                        </div>
                    </div>

                    @if($room->description)
                        <div class="mt-4 p-3 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1]">
                            <span class="text-[10px] font-semibold uppercase text-[#6B6B6B] block mb-1">Deskripsi</span>
                            <p class="text-xs text-[#121417] leading-relaxed">{{ $room->description }}</p>
                        </div>
                    @endif
                </div>

                <!-- Internal Notes Box -->
                @if($room->internal_notes)
                    <div class="mt-4 p-3 rounded-xl bg-[#FEF3C7] border border-[#FDE68A]">
                        <span class="text-[10px] font-semibold uppercase text-[#92400E] block mb-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-[#D97706]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>Catatan Internal Pemilik</span>
                        </span>
                        <p class="text-xs text-[#78350F]">{{ $room->internal_notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Fasilitas Kamar (4 Cols) -->
            <div class="lg:col-span-4 bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-[#DEDAD1]">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                            <h2 class="text-sm font-bold text-[#121417]">Fasilitas Kamar</h2>
                        </div>
                        <a href="{{ route('owner.properties.rooms.edit', [$property, $room]) }}" class="text-xs font-semibold text-[#121417] hover:underline cursor-pointer">
                            Edit
                        </a>
                    </div>

                    @if($room->facilities->isNotEmpty())
                        <div class="grid grid-cols-2 gap-2.5">
                            @foreach($room->facilities as $fac)
                                <div class="p-2.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-white border border-[#DEDAD1] flex items-center justify-center text-[#6B6B6B] shrink-0">
                                        <svg class="w-3.5 h-3.5 text-[#15803D]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-medium text-[#121417] truncate">{{ $fac->name }}</p>
                                        <p class="text-[10px] text-[#6B6B6B] truncate">{{ ucfirst($fac->category) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10 px-4 rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1]">
                            <svg class="w-8 h-8 text-[#A5A096] mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <p class="text-xs font-semibold text-[#121417]">Belum Ada Fasilitas Ditambahkan</p>
                            <p class="text-[11px] text-[#6B6B6B] mt-0.5">Lengkapi fasilitas kamar melalui tombol edit.</p>
                        </div>
                    @endif
                </div>

                <div class="pt-4 border-t border-[#DEDAD1] text-center">
                    <span class="text-[11px] text-[#6B6B6B]">{{ $room->facilities->count() }} Fasilitas terdaftar</span>
                </div>
            </div>

            <!-- Maintenance & Riwayat Komplain Kamar (4 Cols) -->
            <div class="lg:col-span-4 bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-[#DEDAD1]">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <h2 class="text-sm font-bold text-[#121417]">Riwayat Kendala</h2>
                        </div>
                        <span class="text-xs text-[#6B6B6B] font-mono">{{ $room->complaints->count() }} Kasus</span>
                    </div>

                    @if($room->complaints->isNotEmpty())
                        <div class="space-y-2.5">
                            @foreach($room->complaints as $cmp)
                                <div class="p-3 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold text-[#121417] truncate">{{ $cmp->title }}</p>
                                        <p class="text-[10px] text-[#6B6B6B] mt-0.5">
                                            {{ $cmp->created_at->translatedFormat('d M Y') }} • {{ $cmp->category }}
                                        </p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $cmp->status === 'selesai' ? 'bg-[#E8F8D0] text-[#15803D] border-[#C7F36B]/60' : ($cmp->status === 'diproses' ? 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]' : 'bg-[#FEE2E2] text-[#B91C1C] border-[#FECACA]') }}">
                                        {{ ucfirst($cmp->status) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10 px-4 rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1]">
                            <svg class="w-8 h-8 text-[#15803D] mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-xs font-semibold text-[#121417]">Tidak Ada Kendala</p>
                            <p class="text-[11px] text-[#6B6B6B] mt-0.5">Kamar ini belum pernah memiliki riwayat keluhan atau kerusakan.</p>
                        </div>
                    @endif
                </div>

                <div class="pt-4 border-t border-[#DEDAD1] text-center">
                    <span class="text-[11px] text-[#6B6B6B]">Kondisi unit terkontrol</span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.owner>
