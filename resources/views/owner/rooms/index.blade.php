<x-layouts.owner title="Daftar Kamar — {{ $property->name }}" activeNav="properties">
    <div class="space-y-6 max-w-7xl mx-auto">
        <!-- Breadcrumb & Header -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#6B6B6B]">
                    <a href="{{ route('owner.properties.index') }}" class="hover:text-[#121417] transition-colors">Properti &amp; Kos</a>
                    <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <a href="{{ route('owner.properties.show', $property) }}" class="hover:text-[#121417] transition-colors">{{ $property->name }}</a>
                    <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="font-medium text-[#121417]">Daftar Kamar</span>
                </nav>
                <h1 class="text-2xl font-bold tracking-tight text-[#121417]">Kamar: {{ $property->name }}</h1>
                <p class="text-xs text-[#6B6B6B]">Kelola seluruh unit kamar, ketersediaan, dan harga sewa.</p>
            </div>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('owner.properties.rooms.create', $property) }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white text-xs font-semibold shadow-[0_1px_2px_rgba(18,20,23,0.08)] active:scale-[0.98] transition-all duration-150 cursor-pointer"
                >
                    <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Kamar</span>
                </a>
            </div>
        </div>

        @if(session('success'))
            <x-ui.alert type="success" :dismissible="true">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if(session('error'))
            <x-ui.alert type="danger" :dismissible="true">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <!-- Rooms Grid -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            @if($rooms->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($rooms as $room)
                        @php
                            $activeContract = $room->contracts->firstWhere('status', 'aktif');
                            $tenantName = $activeContract?->user?->name;
                        @endphp
                        <div class="bg-white border border-[#DEDAD1] hover:border-[#121417]/40 rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.03)] flex flex-col justify-between transition-all duration-150">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-bold text-[#121417]">Kamar {{ $room->room_number }}</h3>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-medium bg-[#F1EFEA] border border-[#DEDAD1] text-[#6B6B6B]">
                                            Lt. {{ $room->floor }}
                                        </span>
                                    </div>

                                    @if($room->status === 'terisi')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                                            Terisi
                                        </span>
                                    @elseif($room->status === 'kosong')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#A5A096]"></span>
                                            Kosong
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#D97706]"></span>
                                            Perbaikan
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-3.5 space-y-2 text-xs">
                                    <div class="flex items-center justify-between text-[#6B6B6B]">
                                        <span>Tipe:</span>
                                        <span class="text-[#121417] font-medium">{{ $room->type }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[#6B6B6B]">
                                        <span>Harga Sewa:</span>
                                        <span class="text-[#121417] font-mono font-semibold tabular-nums">Rp {{ number_format((float) $room->price, 0, ',', '.') }} / bln</span>
                                    </div>
                                    @if($room->size)
                                        <div class="flex items-center justify-between text-[#6B6B6B]">
                                            <span>Ukuran:</span>
                                            <span class="text-[#121417] font-medium">{{ $room->size }}</span>
                                        </div>
                                    @endif
                                </div>

                                @if($room->status === 'terisi' && $tenantName)
                                    <div class="mt-3.5 p-2.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-[#6B6B6B] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span class="text-xs font-medium text-[#121417] truncate">{{ $tenantName }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-2 mt-4 pt-3.5 border-t border-[#DEDAD1]">
                                <a
                                    href="{{ route('owner.properties.rooms.show', [$property, $room]) }}"
                                    class="py-1.5 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] text-center hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                                >
                                    Detail
                                </a>
                                <a
                                    href="{{ route('owner.properties.rooms.edit', [$property, $room]) }}"
                                    class="py-1.5 rounded-xl bg-[#F1EFEA] hover:bg-[#E5E2DA] border border-[#DEDAD1] font-semibold text-xs text-[#121417] text-center transition-colors cursor-pointer"
                                >
                                    Edit
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 px-4 rounded-2xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1]">
                    <div class="w-12 h-12 rounded-xl bg-white border border-[#DEDAD1] flex items-center justify-center text-[#6B6B6B] mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-[#121417]">Belum Ada Kamar Terdaftar</h3>
                    <p class="text-xs text-[#6B6B6B] mt-1 max-w-sm mx-auto">
                        Tambahkan kamar pertama untuk properti {{ $property->name }} sekarang.
                    </p>
                    <a
                        href="{{ route('owner.properties.rooms.create', $property) }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs mt-4 transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Kamar</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-layouts.owner>
