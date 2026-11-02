<x-layouts.owner title="{{ $inventory->name }} — Inventaris HUNI" activeNav="inventory">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Top Breadcrumbs & Actions -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-2 flex-wrap">
                        <a href="{{ route('owner.inventory.index') }}" class="hover:text-[#121417] transition-colors">Fasilitas & Inventaris</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <a href="{{ route('owner.inventory.index', ['property_id' => $inventory->property_id]) }}" class="hover:text-[#121417] transition-colors">{{ $inventory->property?->name }}</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <span class="text-[#121417] font-semibold">{{ $inventory->name }}</span>
                    </nav>

                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">{{ $inventory->name }}</h1>
                        @if(in_array($inventory->condition, ['baik', 'bagus']))
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#E8F8D0] border border-[#C7F36B]/60 text-[#15803D]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                                Kondisi Baik
                            </span>
                        @elseif(in_array($inventory->condition, ['perlu_perawatan', 'rusak_ringan']))
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#FEF3C7] border border-[#FDE68A] text-[#B45309]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#B45309]"></span>
                                Perlu Perawatan
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#FEE2E2] border border-[#FECACA] text-[#B91C1C]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#B91C1C]"></span>
                                {{ $inventory->condition_label }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-[#6B6B6B] mt-1">
                        {{ $inventory->property?->name }}
                        @if($inventory->room)
                            • Kamar {{ $inventory->room->room_number }}
                        @else
                            • Area Umum Kos
                        @endif
                        • {{ $inventory->quantity }} unit terdaftar
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0 self-start sm:self-auto">
                    <x-ui.button
                        href="{{ route('owner.inventory.edit', $inventory) }}"
                        variant="primary"
                        size="sm"
                        class="gap-1.5"
                    >
                        <x-ui.icon name="edit" class="w-4 h-4" />
                        <span>Edit Aset</span>
                    </x-ui.button>
                    <form
                        method="POST"
                        action="{{ route('owner.inventory.destroy', $inventory) }}"
                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang inventaris \'{{ addslashes($inventory->name) }}\'?');"
                        class="inline"
                    >
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="px-3.5 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#B91C1C] hover:bg-[#FEE2E2] hover:border-[#FECACA] transition-colors cursor-pointer flex items-center gap-1.5"
                        >
                            <x-ui.icon name="trash" class="w-4 h-4" />
                            <span>Hapus</span>
                        </button>
                    </form>
                    <x-ui.button
                        href="{{ route('owner.inventory.index') }}"
                        variant="outline"
                        size="sm"
                    >
                        Kembali
                    </x-ui.button>
                </div>
            </div>
        </div>

        <!-- Detail Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Card 1: Identitas & Lokasi -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-[#DEDAD1]">
                    <x-ui.icon name="building" class="w-5 h-5 text-[#121417]" />
                    <h2 class="text-sm font-bold text-[#121417]">Spesifikasi & Penempatan</h2>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                        <span class="text-[#6B6B6B]">Kategori</span>
                        <span class="font-semibold text-[#121417]">{{ $inventory->category }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                        <span class="text-[#6B6B6B]">Properti</span>
                        <a href="{{ route('owner.properties.show', $inventory->property) }}" class="font-semibold text-[#15803D] hover:underline flex items-center gap-0.5">
                            <span>{{ $inventory->property?->name }}</span>
                            <span class="text-[10px]">↗</span>
                        </a>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                        <span class="text-[#6B6B6B]">Lokasi Unit</span>
                        @if($inventory->room)
                            <a href="{{ route('owner.properties.rooms.show', [$inventory->property, $inventory->room]) }}" class="font-semibold text-[#15803D] hover:underline flex items-center gap-0.5">
                                <span>Kamar {{ $inventory->room->room_number }} (Lt. {{ $inventory->room->floor }})</span>
                                <span class="text-[10px]">↗</span>
                            </a>
                        @else
                            <span class="font-semibold text-[#121417]">Area Umum Kos</span>
                        @endif
                    </div>
                    @if($inventory->location)
                        <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                            <span class="text-[#6B6B6B]">Posisi Spesifik</span>
                            <span class="font-semibold text-[#121417]">{{ $inventory->location }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                        <span class="text-[#6B6B6B]">Merek / Pabrikan</span>
                        <span class="font-semibold text-[#121417]">{{ $inventory->brand ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                        <span class="text-[#6B6B6B]">Model / Tipe</span>
                        <span class="font-semibold text-[#121417]">{{ $inventory->model ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                        <span class="text-[#6B6B6B]">Nomor Seri / Aset</span>
                        <span class="font-mono font-semibold text-[#121417]">{{ $inventory->serial_number ?? 'Tanpa nomor seri' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                        <span class="text-[#6B6B6B]">Jumlah Ketersediaan</span>
                        <span class="font-mono font-bold text-[#121417]">{{ $inventory->quantity }} unit</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Keuangan, Garansi & Pemeliharaan -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-[#DEDAD1]">
                    <x-ui.icon name="credit-card" class="w-5 h-5 text-[#121417]" />
                    <h2 class="text-sm font-bold text-[#121417]">Keuangan & Pemeliharaan</h2>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                        <span class="text-[#6B6B6B]">Harga Beli</span>
                        <span class="font-mono font-bold text-[#121417]">
                            {{ $inventory->purchase_price ? 'Rp ' . number_format((float) $inventory->purchase_price, 0, ',', '.') : '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                        <span class="text-[#6B6B6B]">Tanggal Pembelian</span>
                        <span class="font-semibold text-[#121417]">
                            {{ $inventory->purchase_date ? $inventory->purchase_date->translatedFormat('d F Y') : '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                        <span class="text-[#6B6B6B]">Masa Garansi Sampai</span>
                        <span class="font-semibold text-[#121417]">
                            {{ $inventory->warranty_until ? $inventory->warranty_until->translatedFormat('d F Y') : 'Tidak tercatat' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                        <span class="text-[#6B6B6B]">Pengecekan Terakhir</span>
                        <span class="font-semibold text-[#121417]">
                            {{ $inventory->last_checked_at ? $inventory->last_checked_at->translatedFormat('d F Y') : '-' }}
                        </span>
                    </div>
                </div>

                @if($inventory->notes)
                    <div class="pt-2">
                        <span class="text-[11px] font-semibold text-[#6B6B6B] block mb-1">Catatan Tambahan:</span>
                        <div class="p-3 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] text-xs text-[#121417] leading-relaxed whitespace-pre-line">
                            {{ $inventory->notes }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.owner>
