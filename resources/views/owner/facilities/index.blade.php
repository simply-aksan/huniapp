<x-layouts.owner title="Katalog Fasilitas — HUNI" activeNav="inventory">
    <div class="space-y-6">
        <!-- Top Breadcrumbs & Header -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-2">
                        <a href="{{ route('owner.inventory.index') }}" class="hover:text-[#121417] transition-colors">Fasilitas & Inventaris</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <span class="text-[#121417] font-semibold">Katalog Fasilitas</span>
                    </nav>

                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#C7F36B]/20 border border-[#C7F36B]/60 flex items-center justify-center shrink-0">
                            <x-ui.icon name="sparkles" class="w-5 h-5 text-[#15803D]" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">Katalog Fasilitas</h1>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                                    {{ $facilities->total() }} Item
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-[#6B6B6B] mt-0.5">
                                Kelola daftar standar fasilitas yang dapat dihubungkan ke properti dan unit kamar kos Anda.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap shrink-0">
                    <x-ui.button href="{{ route('owner.inventory.index') }}" variant="outline" class="gap-1.5">
                        <x-ui.icon name="arrow-left" class="w-4 h-4" />
                        <span>Daftar Inventaris</span>
                    </x-ui.button>
                    <x-ui.button href="{{ route('owner.facilities.create') }}" variant="primary" class="gap-1.5">
                        <x-ui.icon name="plus" class="w-4 h-4" />
                        <span>Tambah Fasilitas Baru</span>
                    </x-ui.button>
                </div>
            </div>
        </div>

        @if(session('success'))
            <x-ui.alert type="success" :message="session('success')" dismissible="true" />
        @endif

        @if(session('error'))
            <x-ui.alert type="danger" :message="session('error')" dismissible="true" />
        @endif

        <!-- Filter & Search Controls -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <form method="GET" action="{{ route('owner.facilities.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <div class="sm:col-span-6 relative">
                    <input
                        type="text"
                        name="search"
                        value="{{ $searchQuery }}"
                        placeholder="Cari nama fasilitas atau deskripsi..."
                        class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                    />
                </div>

                <div class="sm:col-span-4">
                    <select
                        name="category"
                        class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                    >
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->value }}" {{ $selectedCategory === $cat->value ? 'selected' : '' }}>
                                {{ $cat->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2 flex items-center gap-1.5">
                    <button
                        type="submit"
                        class="flex-1 py-2.5 px-3 rounded-xl bg-[#121417] text-white text-xs font-semibold hover:bg-[#2A2B2D] transition-colors cursor-pointer text-center"
                    >
                        Filter
                    </button>
                    @if($searchQuery || $selectedCategory)
                        <a
                            href="{{ route('owner.facilities.index') }}"
                            class="py-2.5 px-3 rounded-xl bg-[#F4F2EC] border border-[#DEDAD1] text-xs font-semibold text-[#6B6B6B] hover:text-[#121417] hover:bg-[#EAE6DF] transition-colors cursor-pointer"
                        >
                            ✕
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Facilities Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($facilities as $fac)
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between hover:border-[#121417]/40 transition-colors">
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-center justify-center text-[#121417] shrink-0">
                                <x-ui.icon name="sparkles" class="w-5 h-5 text-[#15803D]" />
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-[#F4F2EC] border border-[#DEDAD1] text-[#6B6B6B]">
                                {{ $fac->category }}
                            </span>
                        </div>

                        <h3 class="font-bold text-base text-[#121417]">{{ $fac->name }}</h3>
                        <p class="text-xs text-[#6B6B6B] mt-1 line-clamp-2">
                            {{ $fac->description ?? 'Tidak ada deskripsi rinci untuk fasilitas ini.' }}
                        </p>
                    </div>

                    <div class="mt-4 pt-3.5 border-t border-[#DEDAD1] flex items-center justify-between">
                        <div class="text-[11px] font-medium text-[#6B6B6B]">
                            <span>{{ $fac->properties_count }} Properti</span>
                            <span class="mx-1">•</span>
                            <span>{{ $fac->rooms_count }} Kamar</span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            @can('update', $fac)
                                <a
                                    href="{{ route('owner.facilities.edit', $fac) }}"
                                    class="p-1.5 rounded-lg bg-[#F9F8F5] border border-[#DEDAD1] text-[#121417] hover:bg-[#EAE6DF] transition-colors cursor-pointer"
                                    title="Edit Fasilitas"
                                >
                                    <x-ui.icon name="edit" class="w-4 h-4" />
                                </a>
                            @else
                                <span
                                    class="p-1.5 rounded-lg bg-[#F4F2EC] border border-[#DEDAD1] text-[#A5A096] cursor-not-allowed opacity-60"
                                    title="Digunakan oleh kos lain — tidak dapat diubah"
                                >
                                    <x-ui.icon name="lock" class="w-4 h-4" />
                                </span>
                            @endcan

                            @can('delete', $fac)
                                <form
                                    method="POST"
                                    action="{{ route('owner.facilities.destroy', $fac) }}"
                                    onsubmit="return confirm('Hapus fasilitas \'{{ addslashes($fac->name) }}\' dari katalog?');"
                                    class="inline"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="p-1.5 rounded-lg bg-[#F9F8F5] border border-[#DEDAD1] text-[#B91C1C] hover:bg-[#FEE2E2] hover:border-[#FECACA] transition-colors cursor-pointer"
                                        title="Hapus Fasilitas"
                                    >
                                        <x-ui.icon name="trash" class="w-4 h-4" />
                                    </button>
                                </form>
                            @else
                                <span
                                    class="p-1.5 rounded-lg bg-[#F4F2EC] border border-[#DEDAD1] text-[#A5A096] cursor-not-allowed opacity-60"
                                    title="Digunakan oleh kos lain — tidak dapat dihapus"
                                >
                                    <x-ui.icon name="lock" class="w-4 h-4" />
                                </span>
                            @endcan
                        </div>
                    </div>
                </div>
            @empty
                <div class="sm:col-span-3 text-center py-12 px-4 bg-white border border-dashed border-[#DEDAD1] rounded-2xl">
                    <x-ui.empty-state
                        title="Belum Ada Fasilitas di Katalog"
                        description="Tambahkan fasilitas standar seperti WiFi, AC, atau Kamar Mandi Dalam."
                        :actionUrl="route('owner.facilities.create')"
                        actionText="Tambah Fasilitas"
                    />
                </div>
            @endforelse
        </div>

        @if($facilities->hasPages())
            <div class="p-4 border border-[#DEDAD1] rounded-2xl bg-white shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                {{ $facilities->links() }}
            </div>
        @endif

        <!-- Property Facility Assignment Quick Manager -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-5">
            <div class="flex items-center gap-2 pb-3 border-b border-[#DEDAD1]">
                <x-ui.icon name="building" class="w-5 h-5 text-[#121417]" />
                <div>
                    <h2 class="text-base font-bold text-[#121417]">Fasilitas Aktif per Properti</h2>
                    <p class="text-xs text-[#6B6B6B]">Hubungkan fasilitas dari katalog ke properti kos yang Anda kelola.</p>
                </div>
            </div>

            <div class="space-y-4">
                @foreach($properties as $property)
                    <div class="p-4 sm:p-5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] space-y-3.5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h3 class="text-sm font-bold text-[#121417]">{{ $property->name }}</h3>
                                <p class="text-[11px] text-[#6B6B6B]">{{ $property->city }} • {{ $property->rooms->count() }} Kamar</p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white border border-[#DEDAD1] text-[#121417] self-start sm:self-auto">
                                {{ $property->facilities->count() }} Fasilitas Terpasang
                            </span>
                        </div>

                        <!-- Current Facilities Badges -->
                        <div class="flex flex-wrap gap-2">
                            @forelse($property->facilities as $pFac)
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] shadow-[0_1px_2px_rgba(18,20,23,0.02)]">
                                    <span>{{ $pFac->name }}</span>
                                    @if($pFac->pivot->notes)
                                        <span class="text-[10px] text-[#6B6B6B]">({{ $pFac->pivot->notes }})</span>
                                    @endif
                                    <form
                                        method="POST"
                                        action="{{ route('owner.properties.facilities.detach', [$property, $pFac]) }}"
                                        onsubmit="return confirm('Lepas fasilitas {{ addslashes($pFac->name) }} dari {{ addslashes($property->name) }}?');"
                                        class="inline"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="w-4 h-4 rounded-full bg-[#F4F2EC] text-[#B91C1C] hover:bg-[#FEE2E2] flex items-center justify-center text-[10px] cursor-pointer ml-0.5"
                                            title="Lepas fasilitas"
                                        >
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-xs text-[#6B6B6B] italic">Belum ada fasilitas yang dihubungkan ke properti ini.</p>
                            @endforelse
                        </div>

                        <!-- Quick Attach Form -->
                        <form method="POST" action="{{ route('owner.properties.facilities.assign', $property) }}" class="pt-3 border-t border-[#DEDAD1] flex flex-col sm:flex-row items-center gap-2.5">
                            @csrf
                            <select
                                name="facility_id"
                                required
                                class="flex-1 w-full sm:w-auto px-3 py-2 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] cursor-pointer"
                            >
                                <option value="">+ Pilih fasilitas untuk dipasang...</option>
                                @foreach($facilities as $fOpt)
                                    @if(! $property->facilities->contains('id', $fOpt->id))
                                        <option value="{{ $fOpt->id }}">{{ $fOpt->name }} ({{ $fOpt->category }})</option>
                                    @endif
                                @endforeach
                            </select>

                            <input
                                type="text"
                                name="notes"
                                placeholder="Catatan tambahan (opsional)"
                                class="flex-1 w-full sm:w-auto px-3 py-2 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417]"
                            />

                            <button
                                type="submit"
                                class="w-full sm:w-auto px-4 py-2 rounded-xl bg-[#121417] text-xs font-semibold text-white hover:bg-[#2A2B2D] transition-colors cursor-pointer whitespace-nowrap"
                            >
                                Pasang Fasilitas
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.owner>
