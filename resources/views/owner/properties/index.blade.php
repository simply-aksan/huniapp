<x-layouts.owner title="Properti & Kos — HUNI" activeNav="properties">
    <!-- Header Banner Card -->
    <div class="relative bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5 relative z-10">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight leading-tight">
                    Properti & Kos
                </h1>
                <p class="text-xs sm:text-sm text-[#767C85] mt-1">
                    Kelola seluruh unit properti kos dan pantau performa okupansi kamar Anda secara terpadu.
                </p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('owner.properties.create') }}" class="px-4 py-2 rounded-xl bg-[#C7F36B] hover:bg-[#bbf055] text-[#121417] border border-[#121417] font-semibold text-xs shadow-[2px_2px_0_#121417] active:scale-[0.98] transition-all flex items-center gap-1.5 cursor-pointer">
                    <x-ui.icon name="plus" class="w-4 h-4" />
                    <span>Tambah Properti</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 Stats Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center shrink-0 text-[#121417]">
                <x-ui.icon name="home" class="w-5 h-5 text-[#474D57]" />
            </div>
            <div>
                <p class="text-xs font-semibold text-[#767C85] tracking-wide">Total Properti</p>
                <p class="text-2xl font-extrabold text-[#121417] mt-0.5 font-mono tabular-nums">{{ $totalProperties }}</p>
            </div>
        </div>

        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center shrink-0 text-[#121417]">
                <x-ui.icon name="door" class="w-5 h-5 text-[#474D57]" />
            </div>
            <div>
                <p class="text-xs font-semibold text-[#767C85] tracking-wide">Total Kamar</p>
                <p class="text-2xl font-extrabold text-[#121417] mt-0.5 font-mono tabular-nums">{{ $totalRooms }}</p>
            </div>
        </div>

        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center shrink-0 text-[#121417]">
                <x-ui.icon name="users" class="w-5 h-5 text-[#474D57]" />
            </div>
            <div>
                <p class="text-xs font-semibold text-[#767C85] tracking-wide">Total Penghuni</p>
                <p class="text-2xl font-extrabold text-[#121417] mt-0.5 font-mono tabular-nums">{{ $totalTenants }}</p>
            </div>
        </div>

        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-[#ECFDF5] border border-[#A7F3D0] flex items-center justify-center shrink-0 text-[#065F46]">
                <x-ui.icon name="wallet" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-xs font-semibold text-[#767C85] tracking-wide">Total Pendapatan</p>
                <p class="text-xl font-extrabold text-[#121417] mt-0.5 font-mono tabular-nums">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3.5">
        <!-- Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 huni-scrollbar">
            <a href="{{ route('owner.properties.index', ['search' => $searchQuery]) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border transition-all cursor-pointer whitespace-nowrap {{ $activeFilter === 'semua' || empty($activeFilter) ? 'bg-[#121417] text-white border-[#121417] shadow-xs' : 'bg-white text-[#474D57] border-[#DEDAD1] hover:bg-[#F9F8F5]' }}">
                Semua ({{ $totalProperties }})
            </a>
            <a href="{{ route('owner.properties.index', ['status' => 'aktif', 'search' => $searchQuery]) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border transition-all cursor-pointer whitespace-nowrap {{ $activeFilter === 'aktif' ? 'bg-[#121417] text-white border-[#121417] shadow-xs' : 'bg-white text-[#474D57] border-[#DEDAD1] hover:bg-[#F9F8F5]' }}">
                Aktif
            </a>
            <a href="{{ route('owner.properties.index', ['status' => 'nonaktif', 'search' => $searchQuery]) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border transition-all cursor-pointer whitespace-nowrap {{ $activeFilter === 'nonaktif' ? 'bg-[#121417] text-white border-[#121417] shadow-xs' : 'bg-white text-[#474D57] border-[#DEDAD1] hover:bg-[#F9F8F5]' }}">
                Nonaktif
            </a>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('owner.properties.index') }}" class="flex items-center gap-2 max-w-md w-full md:w-auto">
            @if($activeFilter && $activeFilter !== 'semua')
                <input type="hidden" name="status" value="{{ $activeFilter }}" />
            @endif
            <div class="relative flex-1">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#767C85]">
                    <x-ui.icon name="search" class="w-3.5 h-3.5" />
                </span>
                <input
                    type="text"
                    name="search"
                    value="{{ $searchQuery }}"
                    placeholder="Cari nama properti atau alamat..."
                    class="w-full pl-9 pr-3.5 py-1.5 bg-white border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#8A919E] shadow-xs focus:outline-none focus:border-[#121417] focus:ring-2 focus:ring-[#121417]/10"
                />
            </div>
            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] shadow-xs hover:bg-[#F9F8F5] transition-all cursor-pointer">
                Cari
            </button>
            @if($searchQuery)
                <a href="{{ route('owner.properties.index', ['status' => $activeFilter]) }}" class="px-3 py-1.5 rounded-xl bg-[#FEF2F2] border border-[#FECACA] font-semibold text-xs text-[#991B1B] hover:bg-[#FEE2E2] transition-all cursor-pointer">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Properties Grid -->
    @if($properties->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
            @foreach($properties as $property)
                <div class="bg-white border border-[#DEDAD1] rounded-2xl overflow-hidden shadow-[0_1px_3px_rgba(18,20,23,0.04)] hover:border-[#121417] transition-all duration-200 flex flex-col justify-between group">
                    <div>
                        <!-- Property Image Header -->
                        <div class="relative h-44 bg-[#F9F8F5] border-b border-[#EBE8E1] overflow-hidden flex items-center justify-center">
                            @php
                                $imgIndex = ($loop->index % 3);
                                $propertyImages = [
                                    'images/marketing/kos-business.png',
                                    'images/marketing/kos-large.png',
                                    'images/marketing/kos-medium.png',
                                ];
                                $propertyImage = asset($propertyImages[$imgIndex]);
                            @endphp
                            <img src="{{ $propertyImage }}" alt="{{ $property->name }}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-102" />

                            <!-- Status Badge Overlay -->
                            <div class="absolute top-3 left-3">
                                @if($property->status === 'aktif')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/95 backdrop-blur-xs border border-[#A7F3D0] text-[#065F46] shadow-xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/95 backdrop-blur-xs border border-[#DEDAD1] text-[#767C85] shadow-xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#767C85]"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </div>

                            <!-- Edit Link -->
                            <div class="absolute top-3 right-3">
                                <a href="{{ route('owner.properties.edit', $property) }}" class="w-7 h-7 rounded-lg bg-white/95 backdrop-blur-xs border border-[#DEDAD1] flex items-center justify-center text-[#767C85] hover:text-[#121417] shadow-xs hover:border-[#121417] transition-all cursor-pointer" title="Edit Properti">
                                    <x-ui.icon name="edit" class="w-3.5 h-3.5" />
                                </a>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-lg font-bold text-[#121417] tracking-tight">
                                    {{ $property->name }}
                                </h2>
                                @if($property->is_primary)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#FFFBEB] border border-[#FDE68A] text-[#92400E]">
                                        <x-ui.icon name="award" class="w-3 h-3 text-[#D97706]" />
                                        Utama
                                    </span>
                                @endif
                            </div>

                            <!-- Address -->
                            <p class="text-xs text-[#767C85] mt-1 flex items-start gap-1">
                                <x-ui.icon name="map-pin" class="w-3.5 h-3.5 text-[#8A919E] shrink-0 mt-0.5" />
                                <span>{{ $property->address }}{{ $property->district ? ', ' . $property->district : '' }}, {{ $property->city }}</span>
                            </p>

                            <!-- Metric Stats 3-box row -->
                            <div class="grid grid-cols-3 gap-2 mt-4 pt-3 border-t border-[#EBE8E1] text-center">
                                <div class="p-2 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1]">
                                    <x-ui.icon name="door" class="w-3.5 h-3.5 text-[#767C85] mx-auto" />
                                    <span class="text-xs font-bold text-[#121417] block mt-0.5 font-mono tabular-nums">{{ $property->rooms_count }}</span>
                                    <span class="text-[10px] text-[#767C85] block">Kamar</span>
                                </div>
                                <div class="p-2 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1]">
                                    <x-ui.icon name="users" class="w-3.5 h-3.5 text-[#767C85] mx-auto" />
                                    <span class="text-xs font-bold text-[#121417] block mt-0.5 font-mono tabular-nums">{{ $property->active_tenants_count }}</span>
                                    <span class="text-[10px] text-[#767C85] block">Penghuni</span>
                                </div>
                                @php
                                    $occ = $property->rooms_count > 0 ? (int) round(($property->occupied_rooms_count / $property->rooms_count) * 100) : 0;
                                @endphp
                                <div class="p-2 rounded-xl bg-[#ECFDF5] border border-[#A7F3D0]">
                                    <x-ui.icon name="bar-chart" class="w-3.5 h-3.5 text-[#065F46] mx-auto" />
                                    <span class="text-xs font-bold text-[#065F46] block mt-0.5 font-mono tabular-nums">{{ $occ }}%</span>
                                    <span class="text-[10px] text-[#065F46] block">Terisi</span>
                                </div>
                            </div>

                            <!-- Facilities Pills -->
                            @if($property->facilities->isNotEmpty())
                                <div class="flex items-center gap-1.5 flex-wrap mt-3 pt-2">
                                    @foreach($property->facilities->take(3) as $fac)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-[#F9F8F5] border border-[#EBE8E1] text-[#474D57]">
                                            {{ $fac->name }}
                                        </span>
                                    @endforeach
                                    @if($property->facilities->count() > 3)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-[#F9F8F5] border border-[#EBE8E1] text-[#767C85]">
                                            +{{ $property->facilities->count() - 3 }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Card Actions Footer -->
                    <div class="p-5 pt-0 grid grid-cols-2 gap-2.5">
                        <a href="{{ route('owner.properties.show', $property) }}" class="w-full py-2 rounded-xl bg-white text-[#121417] border border-[#DEDAD1] font-semibold text-xs text-center shadow-xs hover:bg-[#F9F8F5] hover:border-[#121417] transition-all cursor-pointer">
                            Lihat Detail →
                        </a>
                        <a href="{{ route('owner.properties.rooms.index', $property) }}" class="w-full py-2 rounded-xl bg-[#C7F36B] hover:bg-[#bbf055] text-[#121417] border border-[#121417] font-semibold text-xs text-center shadow-[1px_1px_0_#121417] active:scale-[0.98] transition-all cursor-pointer">
                            Kelola Kamar
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <x-ui.empty-state
            icon="home"
            color="yellow"
            :title="$searchQuery ? 'Tidak Ada Properti Ditemukan' : 'Belum Ada Properti Terdaftar'"
            :description="$searchQuery ? 'Tidak ada properti yang cocok dengan kata kunci pencarian \'' . $searchQuery . '\'.' : 'Anda belum memiliki properti kos yang terdaftar. Tambahkan properti kos pertama Anda untuk mulai mengelola kamar dan penghuni.'"
            :actionText="'Tambah Properti Sekarang'"
            :actionUrl="route('owner.properties.create')"
            :resetText="$searchQuery ? 'Reset Pencarian' : null"
            :resetUrl="$searchQuery ? route('owner.properties.index') : null"
        />
    @endif

    <!-- Bottom Add Property Prompt Box -->
    <div class="bg-white border border-dashed border-[#DEDAD1] rounded-2xl p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-lg font-bold text-[#121417] shrink-0">
                +
            </div>
            <div>
                <h3 class="text-sm font-bold text-[#121417]">Tambah Properti Baru</h3>
                <p class="text-xs text-[#767C85] mt-0.5">Miliki lebih banyak properti/kos dan kelola semuanya dalam satu akun HUNI.</p>
            </div>
        </div>
        <a href="{{ route('owner.properties.create') }}" class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] hover:border-[#121417] font-semibold text-xs text-[#121417] shadow-xs hover:bg-[#F9F8F5] transition-all flex items-center justify-center gap-1.5 shrink-0 cursor-pointer">
            <span>+ Tambah Properti</span>
        </a>
    </div>
</x-layouts.owner>
