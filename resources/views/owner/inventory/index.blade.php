<x-layouts.owner title="Fasilitas & Inventaris — HUNI" activeNav="inventory">
    <div x-data="{
        selectedItem: null,
        detailOpen: false,
        openDetail(item) {
            this.selectedItem = item;
            this.detailOpen = true;
        },
        closeDetail() {
            this.detailOpen = false;
        }
    }" class="space-y-6">

        <!-- Top Breadcrumbs & Actions Header -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-2">
                        <span class="hover:text-[#121417]">Fasilitas & Inventaris</span>
                        <span class="text-[#DEDAD1]">/</span>
                        <span class="text-[#121417] font-semibold">Daftar Inventaris</span>
                    </nav>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#C7F36B]/20 border border-[#C7F36B]/60 flex items-center justify-center shrink-0">
                            <x-ui.icon name="building" class="w-5 h-5 text-[#15803D]" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">Inventaris Kos</h1>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                                    {{ $totalCount }} Aset
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-[#6B6B6B] mt-0.5 max-w-2xl">
                                Kelola inventaris kos Anda. Pantau stok, kondisi, lokasi, dan jadwal perawatan agar selalu siap digunakan.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Header Action Buttons -->
                <div class="flex items-center gap-2.5 flex-wrap shrink-0">
                    <x-ui.button
                        href="{{ route('owner.facilities.index') }}"
                        variant="outline"
                        class="gap-1.5"
                    >
                        <x-ui.icon name="sparkles" class="w-4 h-4 text-[#6B6B6B]" />
                        <span>Katalog Fasilitas</span>
                    </x-ui.button>
                    <x-ui.button
                        href="{{ route('owner.inventory.create') }}"
                        variant="primary"
                        class="gap-1.5"
                    >
                        <x-ui.icon name="plus" class="w-4 h-4" />
                        <span>Tambah Inventaris</span>
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

        <!-- KPI Cards Grid (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Total Item -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-[#6B6B6B]">Total Inventaris</p>
                        <h3 class="text-2xl font-extrabold text-[#121417] font-mono tracking-tight mt-1">
                            {{ $totalItems }} <span class="text-xs font-medium text-[#6B6B6B]">unit</span>
                        </h3>
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-[#F4F2EC] border border-[#DEDAD1] flex items-center justify-center text-[#121417]">
                        <x-ui.icon name="building" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-[#DEDAD1] flex items-center justify-between text-[11px] font-medium text-[#6B6B6B]">
                    <span>{{ $totalCount }} item</span>
                    <span class="text-[#121417] font-bold font-mono">Rp {{ number_format($totalAssetValue, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- 2. Kondisi Baik -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-[#6B6B6B]">Kondisi Baik</p>
                        <h3 class="text-2xl font-extrabold text-[#15803D] font-mono tracking-tight mt-1">{{ $goodCount }}</h3>
                    </div>
                    <span class="text-xs font-bold text-[#15803D] bg-[#E8F8D0] px-2 py-0.5 rounded-full border border-[#C7F36B]/60">
                        {{ $goodPercent }}%
                    </span>
                </div>
                <div class="mt-3">
                    <div class="w-full h-1.5 rounded-full bg-[#F4F2EC] overflow-hidden">
                        <div class="h-full bg-[#15803D] rounded-full" style="width: {{ $goodPercent }}%"></div>
                    </div>
                </div>
            </div>

            <!-- 3. Perlu Perawatan -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-[#6B6B6B]">Perlu Perawatan</p>
                        <h3 class="text-2xl font-extrabold text-[#B45309] font-mono tracking-tight mt-1">{{ $needsMaintenanceCount }}</h3>
                    </div>
                    <span class="text-xs font-bold text-[#B45309] bg-[#FEF3C7] px-2 py-0.5 rounded-full border border-[#FDE68A]">
                        {{ $needsMaintenancePercent }}%
                    </span>
                </div>
                <div class="mt-3">
                    <div class="w-full h-1.5 rounded-full bg-[#F4F2EC] overflow-hidden">
                        <div class="h-full bg-[#B45309] rounded-full" style="width: {{ $needsMaintenancePercent }}%"></div>
                    </div>
                </div>
            </div>

            <!-- 4. Rusak -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-[#6B6B6B]">Rusak / Hilang</p>
                        <h3 class="text-2xl font-extrabold text-[#B91C1C] font-mono tracking-tight mt-1">{{ $damagedCount }}</h3>
                    </div>
                    <span class="text-xs font-bold text-[#B91C1C] bg-[#FEE2E2] px-2 py-0.5 rounded-full border border-[#FECACA]">
                        {{ $damagedPercent }}%
                    </span>
                </div>
                <div class="mt-3">
                    <div class="w-full h-1.5 rounded-full bg-[#F4F2EC] overflow-hidden">
                        <div class="h-full bg-[#B91C1C] rounded-full" style="width: {{ $damagedPercent }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs & Search Controls -->
        <div class="space-y-3">
            <!-- Condition Tabs -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
                @php
                    $tabs = [
                        'semua' => ['label' => "Semua ({$totalCount})", 'value' => 'semua'],
                        'baik' => ['label' => "Baik ({$goodCount})", 'value' => 'baik'],
                        'perlu_perawatan' => ['label' => "Perlu Perawatan ({$needsMaintenanceCount})", 'value' => 'perlu_perawatan'],
                        'rusak' => ['label' => "Rusak ({$damagedCount})", 'value' => 'rusak'],
                    ];
                @endphp
                @foreach($tabs as $key => $tab)
                    @php
                        $isActiveTab = $activeCondition === $tab['value'];
                        $tabParams = array_merge(request()->query(), ['condition' => $tab['value'], 'page' => 1]);
                    @endphp
                    <a
                        href="{{ route('owner.inventory.index', $tabParams) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-colors whitespace-nowrap {{
                            $isActiveTab
                                ? 'bg-[#121417] text-white shadow-[0_1px_2px_rgba(18,20,23,0.08)]'
                                : 'bg-white border border-[#DEDAD1] text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F9F8F5]'
                        }}"
                    >
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </div>

            <!-- Search & Advanced Filter Form -->
            <form method="GET" action="{{ route('owner.inventory.index') }}" class="bg-white border border-[#DEDAD1] rounded-2xl p-4 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <input type="hidden" name="condition" value="{{ $activeCondition }}" />

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                    <!-- Search Input -->
                    <div class="lg:col-span-4 relative">
                        <input
                            type="text"
                            name="search"
                            value="{{ $searchQuery }}"
                            placeholder="Cari nama inventaris, merek, nomor seri..."
                            class="w-full px-3.5 py-2 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                        />
                    </div>

                    <!-- Property Select -->
                    <div class="lg:col-span-3">
                        <select
                            name="property_id"
                            class="w-full px-3 py-2 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        >
                            <option value="">Semua Properti</option>
                            @foreach($properties as $prop)
                                <option value="{{ $prop->id }}" {{ (string) $activePropertyId === (string) $prop->id ? 'selected' : '' }}>
                                    {{ $prop->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category Select -->
                    <div class="lg:col-span-3">
                        <select
                            name="category"
                            class="w-full px-3 py-2 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        >
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ $activeCategory === $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter & Reset Buttons -->
                    <div class="lg:col-span-2 flex items-center gap-1.5">
                        <button
                            type="submit"
                            class="flex-1 py-2 px-3 rounded-xl bg-[#121417] text-white text-xs font-semibold hover:bg-[#2A2B2D] transition-colors cursor-pointer text-center"
                        >
                            Filter
                        </button>
                        @if($searchQuery || $activePropertyId || $activeCategory || $activeRoomId || $activeCondition !== 'semua')
                            <a
                                href="{{ route('owner.inventory.index') }}"
                                class="py-2 px-3 rounded-xl bg-[#F4F2EC] border border-[#DEDAD1] text-xs font-semibold text-[#6B6B6B] hover:text-[#121417] hover:bg-[#EAE6DF] transition-colors cursor-pointer"
                                title="Reset Filter"
                            >
                                ✕
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Inventory Table Container -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl overflow-hidden shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            @if($items->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#F9F8F5] border-b border-[#DEDAD1] text-[11px] font-bold uppercase tracking-wider text-[#6B6B6B]">
                                <th class="py-3 px-4">Nama Barang</th>
                                <th class="py-3 px-4">Kategori</th>
                                <th class="py-3 px-4">Lokasi</th>
                                <th class="py-3 px-4">Jumlah</th>
                                <th class="py-3 px-4">Kondisi</th>
                                <th class="py-3 px-4">Terakhir Dicek</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DEDAD1]/70 text-xs text-[#121417]">
                            @foreach($items as $item)
                                @php
                                    $itemJson = json_encode([
                                        'id' => $item->id,
                                        'name' => $item->name,
                                        'serial_number' => $item->serial_number,
                                        'category' => $item->category,
                                        'brand' => $item->brand,
                                        'model' => $item->model,
                                        'location' => $item->location ?? ($item->room ? "Kamar {$item->room->room_number}" : $item->property?->name),
                                        'quantity' => $item->quantity,
                                        'condition' => $item->condition,
                                        'condition_label' => $item->condition_label,
                                        'condition_badge' => $item->condition_badge_variant,
                                        'purchase_date' => $item->purchase_date?->translatedFormat('d M Y'),
                                        'purchase_price' => $item->purchase_price ? 'Rp ' . number_format((float) $item->purchase_price, 0, ',', '.') : null,
                                        'warranty_until' => $item->warranty_until?->translatedFormat('d M Y'),
                                        'last_checked_at' => $item->last_checked_at?->translatedFormat('d M Y'),
                                        'notes' => $item->notes,
                                        'property_name' => $item->property?->name,
                                        'room_number' => $item->room?->room_number,
                                        'edit_url' => route('owner.inventory.edit', $item),
                                        'delete_url' => route('owner.inventory.destroy', $item),
                                    ]);
                                @endphp
                                <tr class="hover:bg-[#F9F8F5]/60 transition-colors">
                                    <!-- Nama Barang -->
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-lg bg-[#F9F8F5] border border-[#DEDAD1] flex items-center justify-center text-[#121417] shrink-0">
                                                <x-ui.icon name="building" class="w-4 h-4 text-[#6B6B6B]" />
                                            </div>
                                            <div class="min-w-0">
                                                <button
                                                    type="button"
                                                    x-on:click="openDetail({{ $itemJson }})"
                                                    class="font-semibold text-xs text-[#121417] hover:underline text-left block truncate cursor-pointer"
                                                >
                                                    {{ $item->name }}
                                                </button>
                                                <p class="text-[11px] text-[#6B6B6B] truncate">
                                                    {{ $item->brand ? $item->brand . ($item->model ? " ({$item->model})" : '') : ($item->serial_number ? "SN: {$item->serial_number}" : 'Tanpa SN') }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kategori -->
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#F4F2EC] border border-[#DEDAD1] text-[#6B6B6B]">
                                            {{ $item->category }}
                                        </span>
                                    </td>

                                    <!-- Lokasi -->
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="text-xs">
                                            <p class="font-semibold text-[#121417]">
                                                {{ $item->room ? "Kamar {$item->room->room_number}" : 'Area Bersama' }}
                                            </p>
                                            <p class="text-[11px] text-[#6B6B6B]">
                                                {{ $item->property?->name }}
                                                @if($item->location)
                                                    • {{ $item->location }}
                                                @endif
                                            </p>
                                        </div>
                                    </td>

                                    <!-- Jumlah -->
                                    <td class="py-3 px-4 whitespace-nowrap font-mono">
                                        <span class="font-bold text-xs">{{ $item->quantity }}</span>
                                        <span class="text-[11px] text-[#6B6B6B]">unit</span>
                                    </td>

                                    <!-- Kondisi -->
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        @if(in_array($item->condition, ['baik', 'bagus']))
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8D0] border border-[#C7F36B]/60 text-[#15803D]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                                                Baik
                                            </span>
                                        @elseif(in_array($item->condition, ['perlu_perawatan', 'rusak_ringan']))
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] border border-[#FDE68A] text-[#B45309]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#B45309]"></span>
                                                Perlu Perawatan
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEE2E2] border border-[#FECACA] text-[#B91C1C]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#B91C1C]"></span>
                                                {{ $item->condition_label }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Terakhir Dicek -->
                                    <td class="py-3 px-4 whitespace-nowrap text-xs text-[#6B6B6B]">
                                        {{ $item->last_checked_at ? $item->last_checked_at->translatedFormat('d M Y') : ($item->purchase_date ? $item->purchase_date->translatedFormat('d M Y') : '-') }}
                                    </td>

                                    <!-- Aksi -->
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1">
                                            <button
                                                type="button"
                                                x-on:click="openDetail({{ $itemJson }})"
                                                class="p-1.5 rounded-lg bg-[#F9F8F5] border border-[#DEDAD1] text-[#121417] hover:bg-[#EAE6DF] transition-colors cursor-pointer"
                                                title="Lihat Detail"
                                            >
                                                <x-ui.icon name="eye" class="w-4 h-4" />
                                            </button>
                                            <a
                                                href="{{ route('owner.inventory.edit', $item) }}"
                                                class="p-1.5 rounded-lg bg-[#F9F8F5] border border-[#DEDAD1] text-[#121417] hover:bg-[#EAE6DF] transition-colors cursor-pointer"
                                                title="Edit"
                                            >
                                                <x-ui.icon name="edit" class="w-4 h-4" />
                                            </a>
                                            <form
                                                method="POST"
                                                action="{{ route('owner.inventory.destroy', $item) }}"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang inventaris \'{{ addslashes($item->name) }}\'?');"
                                                class="inline"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="p-1.5 rounded-lg bg-[#F9F8F5] border border-[#DEDAD1] text-[#B91C1C] hover:bg-[#FEE2E2] hover:border-[#FECACA] transition-colors cursor-pointer"
                                                    title="Hapus"
                                                >
                                                    <x-ui.icon name="trash" class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                @if($items->hasPages())
                    <div class="p-4 border-t border-[#DEDAD1] bg-[#F9F8F5]/50 flex items-center justify-between">
                        <p class="text-xs text-[#6B6B6B]">
                            Menampilkan {{ $items->firstItem() }} - {{ $items->lastItem() }} dari {{ $items->total() }} barang
                        </p>
                        <div>
                            {{ $items->links() }}
                        </div>
                    </div>
                @endif
            @else
                <!-- Empty State -->
                <div class="text-center py-16 px-4">
                    <x-ui.empty-state
                        title="Belum Ada Barang Inventaris"
                        description="Catat aset perabot, peralatan elektronik, dan inventaris lainnya untuk memudahkan pemantauan kondisi kos."
                        :actionUrl="route('owner.inventory.create')"
                        actionText="Tambah Inventaris Baru"
                    />
                </div>
            @endif
        </div>

        <!-- Detail Slide-over / Drawer -->
        <div
            x-show="detailOpen"
            x-cloak
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex justify-end"
        >
            <div
                x-show="detailOpen"
                x-transition:enter="transition-transform ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition-transform ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                x-on:click.away="closeDetail()"
                class="w-full max-w-md bg-white border-l border-[#DEDAD1] min-h-screen p-6 shadow-2xl flex flex-col justify-between overflow-y-auto"
            >
                <div class="space-y-5">
                    <!-- Drawer Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-[#DEDAD1]">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="building" class="w-5 h-5 text-[#121417]" />
                            <h2 class="text-base font-bold text-[#121417]">Detail Inventaris</h2>
                        </div>
                        <button
                            type="button"
                            x-on:click="closeDetail()"
                            class="w-7 h-7 rounded-lg border border-[#DEDAD1] bg-white flex items-center justify-center text-xs font-semibold text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F4F2EC] cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Item Hero Card -->
                    <template x-if="selectedItem">
                        <div class="space-y-4">
                            <div class="p-4 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-white border border-[#DEDAD1] flex items-center justify-center text-[#121417] shrink-0">
                                    <x-ui.icon name="building" class="w-6 h-6 text-[#15803D]" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-bold text-sm text-[#121417] truncate" x-text="selectedItem.name"></h3>
                                    <p class="text-[11px] text-[#6B6B6B]" x-text="selectedItem.serial_number ? 'SN: ' + selectedItem.serial_number : 'Tanpa Nomor Seri'"></p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                            :class="{
                                                'bg-[#E8F8D0] text-[#15803D] border-[#C7F36B]/60': selectedItem.condition === 'baik' || selectedItem.condition === 'bagus',
                                                'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]': selectedItem.condition === 'perlu_perawatan' || selectedItem.condition === 'rusak_ringan',
                                                'bg-[#FEE2E2] text-[#B91C1C] border-[#FECACA]': selectedItem.condition === 'rusak' || selectedItem.condition === 'rusak_berat' || selectedItem.condition === 'hilang'
                                            }"
                                            x-text="selectedItem.condition_label"
                                        ></span>
                                        <span class="text-[11px] font-mono font-medium text-[#121417]" x-text="selectedItem.quantity + ' unit'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action CTAs -->
                            <div class="flex items-center gap-2.5">
                                <a
                                    :href="selectedItem.edit_url"
                                    class="flex-1 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] hover:bg-[#F9F8F5] transition-colors flex items-center justify-center gap-1.5"
                                >
                                    <x-ui.icon name="edit" class="w-3.5 h-3.5" />
                                    <span>Edit</span>
                                </a>
                                <form
                                    :action="selectedItem.delete_url"
                                    method="POST"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang ini?');"
                                    class="flex-1"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="w-full py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#B91C1C] hover:bg-[#FEE2E2] hover:border-[#FECACA] transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                                    >
                                        <x-ui.icon name="trash" class="w-3.5 h-3.5" />
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            </div>

                            <!-- Detail Spec Fields -->
                            <div class="space-y-2 pt-2 text-xs">
                                <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                                    <span class="text-[#6B6B6B]">Kategori</span>
                                    <span class="font-semibold text-[#121417]" x-text="selectedItem.category"></span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                                    <span class="text-[#6B6B6B]">Properti</span>
                                    <span class="font-semibold text-[#121417]" x-text="selectedItem.property_name"></span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                                    <span class="text-[#6B6B6B]">Kamar / Lokasi</span>
                                    <span class="font-semibold text-[#121417]" x-text="selectedItem.location"></span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                                    <span class="text-[#6B6B6B]">Merek</span>
                                    <span class="font-semibold text-[#121417]" x-text="selectedItem.brand || '-'"></span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                                    <span class="text-[#6B6B6B]">Model</span>
                                    <span class="font-semibold text-[#121417]" x-text="selectedItem.model || '-'"></span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                                    <span class="text-[#6B6B6B]">Harga Beli</span>
                                    <span class="font-bold font-mono text-[#121417]" x-text="selectedItem.purchase_price || '-'"></span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                                    <span class="text-[#6B6B6B]">Tanggal Pembelian</span>
                                    <span class="font-semibold text-[#121417]" x-text="selectedItem.purchase_date || '-'"></span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                                    <span class="text-[#6B6B6B]">Masa Garansi</span>
                                    <span class="font-semibold text-[#121417]" x-text="selectedItem.warranty_until || '-'"></span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#DEDAD1]">
                                    <span class="text-[#6B6B6B]">Terakhir Dicek</span>
                                    <span class="font-semibold text-[#121417]" x-text="selectedItem.last_checked_at || '-'"></span>
                                </div>
                            </div>

                            <!-- Notes Box -->
                            <template x-if="selectedItem.notes">
                                <div class="p-3.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1]">
                                    <span class="text-[11px] font-semibold text-[#6B6B6B] block mb-1">Catatan Perawatan / Riwayat:</span>
                                    <p class="text-xs font-medium text-[#121417]" x-text="selectedItem.notes"></p>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="pt-4 border-t border-[#DEDAD1]">
                    <x-ui.button
                        type="button"
                        x-on:click="closeDetail()"
                        variant="outline"
                        class="w-full"
                    >
                        Tutup Panel
                    </x-ui.button>
                </div>
            </div>
        </div>

    </div>
</x-layouts.owner>
