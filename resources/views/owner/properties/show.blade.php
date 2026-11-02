<x-layouts.owner title="{{ $property->name }} — HUNI" activeNav="properties">
    <div class="space-y-6">
        @if(session('success'))
            <x-ui.alert variant="success">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if(session('error'))
            <x-ui.alert variant="danger">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <!-- Property Hero Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col md:flex-row md:items-start justify-between gap-6">
            <div class="space-y-2.5 max-w-2xl">
                <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-1">
                    <a href="{{ route('owner.properties.index') }}" class="hover:text-[#121417] transition-colors">Properti &amp; Kos</a>
                    <span class="text-[#DEDAD1]">/</span>
                    <span class="text-[#121417] font-semibold">{{ $property->name }}</span>
                </nav>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">{{ $property->name }}</h1>
                    @if($property->is_primary)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-[#FFFBEB] border border-[#FDE68A] text-[#92400E]">
                            <x-ui.icon name="award" class="w-3.5 h-3.5 text-[#D97706]" />
                            Properti Utama
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $property->status === 'aktif' ? 'bg-[#ECFDF5] border border-[#A7F3D0] text-[#065F46]' : 'bg-[#F3F1EC] border border-[#DEDAD1] text-[#767C85]' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $property->status === 'aktif' ? 'bg-[#15803D]' : 'bg-[#767C85]' }}"></span>
                        {{ ucfirst($property->status) }}
                    </span>
                </div>

                <p class="text-xs sm:text-sm text-[#767C85] flex items-start gap-1.5">
                    <x-ui.icon name="map-pin" class="w-4 h-4 text-[#8A919E] shrink-0 mt-0.5" />
                    <span>{{ $property->address }}{{ $property->district ? ', ' . $property->district : '' }}, {{ $property->city }} {{ $property->postal_code }}</span>
                </p>

                @if($property->description)
                    <p class="text-xs text-[#474D57] pt-2 leading-relaxed bg-[#F9F8F5] p-3 rounded-xl border border-[#EBE8E1]">
                        {{ $property->description }}
                    </p>
                @endif

                <!-- Facility Badges -->
                @if($property->facilities->isNotEmpty())
                    <div class="flex items-center gap-1.5 flex-wrap pt-1">
                        <span class="text-xs font-semibold text-[#767C85]">Fasilitas umum:</span>
                        @foreach($property->facilities as $facility)
                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-[#F9F8F5] border border-[#EBE8E1] text-[#474D57]">
                                {{ $facility->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <a href="{{ route('owner.properties.knowledge.index', $property) }}" class="px-3.5 py-2 rounded-xl bg-[#EFF6FF] text-[#1E40AF] border border-[#BFDBFE] font-semibold text-xs shadow-xs hover:bg-[#DBEAFE] transition-all cursor-pointer flex items-center gap-1.5">
                    <x-ui.icon name="bot" class="w-3.5 h-3.5" />
                    <span>Knowledge Base (Aturan & FAQ)</span>
                </a>
                <a href="{{ route('owner.properties.edit', $property) }}" class="px-3.5 py-2 rounded-xl bg-white text-[#121417] border border-[#DEDAD1] font-semibold text-xs shadow-xs hover:bg-[#F9F8F5] hover:border-[#121417] transition-all cursor-pointer flex items-center gap-1.5">
                    <x-ui.icon name="edit" class="w-3.5 h-3.5 text-[#767C85]" />
                    <span>Edit Properti</span>
                </a>
                <a href="{{ route('owner.properties.rooms.create', $property) }}" class="px-4 py-2 rounded-xl bg-[#C7F36B] hover:bg-[#bbf055] text-[#121417] border border-[#121417] font-semibold text-xs shadow-[2px_2px_0_#121417] active:scale-[0.98] transition-all cursor-pointer flex items-center gap-1.5">
                    <x-ui.icon name="plus" class="w-3.5 h-3.5" />
                    <span>Tambah Kamar</span>
                </a>
            </div>
        </div>

        <!-- 4 Quick Stats Grid for this property -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-[#767C85] tracking-wide">Total Kamar</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#121417] mt-1 font-mono tabular-nums">{{ $totalRooms }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#474D57]">
                    <x-ui.icon name="door" class="w-5 h-5" />
                </div>
            </div>

            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-[#767C85] tracking-wide">Kamar Terisi</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#121417] mt-1 font-mono tabular-nums">{{ $occupiedRooms }} <span class="text-xs font-semibold text-[#15803D]">({{ $occupancyRate }}%)</span></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#ECFDF5] border border-[#A7F3D0] flex items-center justify-center text-[#065F46]">
                    <x-ui.icon name="check-circle" class="w-5 h-5" />
                </div>
            </div>

            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-[#767C85] tracking-wide">Kamar Kosong</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#121417] mt-1 font-mono tabular-nums">{{ $vacantRooms }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#474D57]">
                    <x-ui.icon name="key" class="w-5 h-5" />
                </div>
            </div>

            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-[#767C85] tracking-wide">Dalam Perbaikan</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#121417] mt-1 font-mono tabular-nums">{{ $maintenanceRooms }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#FFFBEB] border border-[#FDE68A] flex items-center justify-center text-[#92400E]">
                    <x-ui.icon name="wrench" class="w-5 h-5" />
                </div>
            </div>
        </div>

        <!-- Room Management Section -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-[#EBE8E1]">
                <div>
                    <h2 class="text-lg font-bold text-[#121417] tracking-tight">Manajemen Kamar</h2>
                    <p class="text-xs text-[#767C85] mt-0.5">Daftar seluruh kamar pada {{ $property->name }}.</p>
                </div>

                <a href="{{ route('owner.properties.rooms.create', $property) }}" class="px-4 py-2 rounded-xl bg-[#C7F36B] hover:bg-[#bbf055] text-[#121417] border border-[#121417] font-semibold text-xs shadow-[2px_2px_0_#121417] active:scale-[0.98] transition-all flex items-center gap-1.5 self-start sm:self-auto cursor-pointer">
                    <x-ui.icon name="plus" class="w-3.5 h-3.5" />
                    <span>Tambah Kamar</span>
                </a>
            </div>

            @if($rooms->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                    @foreach($rooms as $room)
                        @php
                            $activeContract = $room->contracts->firstWhere('status', 'aktif');
                            $tenantName = $activeContract?->user?->name;
                        @endphp
                        <div class="bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:border-[#121417] transition-all">
                            <div>
                                <!-- Room Card Top Bar -->
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-bold text-[#121417]">Kamar {{ $room->room_number }}</h3>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-white border border-[#DEDAD1] text-[#474D57]">
                                            Lt. {{ $room->floor }}
                                        </span>
                                    </div>

                                    @if($room->status === 'terisi')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#ECFDF5] border border-[#A7F3D0] text-[#065F46]">
                                            • Terisi
                                        </span>
                                    @elseif($room->status === 'kosong')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-white border border-[#DEDAD1] text-[#474D57]">
                                            • Kosong
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#FFFBEB] border border-[#FDE68A] text-[#92400E]">
                                            • Perbaikan
                                        </span>
                                    @endif
                                </div>

                                <!-- Room Meta -->
                                <div class="mt-3 space-y-1.5 text-xs text-[#767C85]">
                                    <div class="flex items-center justify-between">
                                        <span>Tipe:</span>
                                        <span class="text-[#121417] font-medium">{{ $room->type }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Harga Sewa:</span>
                                        <span class="text-[#121417] font-bold font-mono tabular-nums">Rp {{ number_format((float) $room->price, 0, ',', '.') }} / bln</span>
                                    </div>
                                    @if($room->size)
                                        <div class="flex items-center justify-between">
                                            <span>Ukuran:</span>
                                            <span class="text-[#121417] font-medium">{{ $room->size }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between">
                                        <span>Kapasitas:</span>
                                        <span class="text-[#121417] font-medium">{{ $room->capacity }} Orang</span>
                                    </div>
                                </div>

                                <!-- Tenant Info (if occupied) -->
                                @if($room->status === 'terisi' && $tenantName)
                                    <div class="mt-3 p-2 rounded-lg bg-white border border-[#EBE8E1] flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-[#EFF6FF] border border-[#BFDBFE] flex items-center justify-center text-[#1E40AF]">
                                            <x-ui.icon name="user" class="w-3.5 h-3.5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-bold text-[#121417] truncate">{{ $tenantName }}</p>
                                            <p class="text-[10px] text-[#767C85]">Penghuni Aktif</p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Buttons Footer -->
                            <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t border-[#EBE8E1]">
                                <a href="{{ route('owner.properties.rooms.show', [$property, $room]) }}" class="py-1.5 rounded-lg bg-white border border-[#DEDAD1] hover:border-[#121417] font-semibold text-xs text-center shadow-xs transition-all cursor-pointer text-[#121417]">
                                    Detail Kamar
                                </a>
                                <a href="{{ route('owner.properties.rooms.edit', [$property, $room]) }}" class="py-1.5 rounded-lg bg-white border border-[#DEDAD1] hover:border-[#121417] font-semibold text-xs text-center shadow-xs transition-all cursor-pointer text-[#121417]">
                                    Edit Kamar
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 px-4 rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1]">
                    <div class="w-10 h-10 rounded-xl bg-white border border-[#EBE8E1] mx-auto flex items-center justify-center text-[#767C85] mb-2">
                        <x-ui.icon name="door" class="w-5 h-5" />
                    </div>
                    <h3 class="text-base font-bold text-[#121417]">Belum Ada Kamar Terdaftar</h3>
                    <p class="text-xs text-[#767C85] mt-1 max-w-sm mx-auto">
                        Tambahkan kamar pertama untuk properti {{ $property->name }} untuk mulai mengelola nomor kamar, harga, dan ketersediaan.
                    </p>
                    <a href="{{ route('owner.properties.rooms.create', $property) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#C7F36B] hover:bg-[#bbf055] border border-[#121417] font-semibold text-xs text-[#121417] shadow-[2px_2px_0_#121417] mt-4 cursor-pointer">
                        <x-ui.icon name="plus" class="w-3.5 h-3.5" />
                        <span>Tambah Kamar Sekarang</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-layouts.owner>
