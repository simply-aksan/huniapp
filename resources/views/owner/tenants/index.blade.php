<x-layouts.owner title="Manajemen Penghuni — HUNI" activeNav="tenants">
    <div class="space-y-6 max-w-7xl mx-auto">
        <!-- Header Banner Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1.5">
                    <h1 class="text-2xl font-bold tracking-tight text-[#121417]">
                        Manajemen Penghuni
                    </h1>
                    <p class="text-xs text-[#6B6B6B]">
                        Pantau seluruh data penyewa kos yang terhubung dengan properti Anda.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <a
                        href="{{ route('owner.invitations.index') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>Kelola Undangan</span>
                    </a>
                    <a
                        href="{{ route('owner.invitations.create') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-[0_1px_2px_rgba(18,20,23,0.08)] active:scale-[0.98] transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Buat Undangan Baru</span>
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <x-ui.alert type="success" :dismissible="true">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        <!-- 3 Quick Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <x-ui.stat-card
                label="Total Penyewa"
                :value="$totalTenants"
                icon="users"
            />
            <x-ui.stat-card
                label="Kontrak Aktif"
                :value="$activeTenantsCount"
                icon="check-circle"
            />
            <x-ui.stat-card
                label="Undangan Diterima / Baru"
                :value="$newTenantsCount"
                icon="mail"
            />
        </div>

        <!-- Filters & Search Toolbar -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-4 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Filter Tabs -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                <a href="{{ route('owner.tenants.index', ['property_id' => $selectedProperty, 'search' => $searchQuery]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer whitespace-nowrap {{ $activeStatus === 'semua' ? 'bg-[#121417] text-white shadow-xs' : 'bg-white text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] border border-[#DEDAD1]' }}">
                    Semua ({{ $totalTenants }})
                </a>
                <a href="{{ route('owner.tenants.index', ['status' => 'aktif', 'property_id' => $selectedProperty, 'search' => $searchQuery]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer whitespace-nowrap {{ $activeStatus === 'aktif' ? 'bg-[#121417] text-white shadow-xs' : 'bg-white text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] border border-[#DEDAD1]' }}">
                    Aktif ({{ $activeTenantsCount }})
                </a>
                <a href="{{ route('owner.tenants.index', ['status' => 'baru', 'property_id' => $selectedProperty, 'search' => $searchQuery]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer whitespace-nowrap {{ $activeStatus === 'baru' ? 'bg-[#121417] text-white shadow-xs' : 'bg-white text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] border border-[#DEDAD1]' }}">
                    Baru / Undangan ({{ $newTenantsCount }})
                </a>
                <a href="{{ route('owner.tenants.index', ['status' => 'selesai', 'property_id' => $selectedProperty, 'search' => $searchQuery]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer whitespace-nowrap {{ $activeStatus === 'selesai' ? 'bg-[#121417] text-white shadow-xs' : 'bg-white text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] border border-[#DEDAD1]' }}">
                    Selesai
                </a>
            </div>

            <!-- Search and Property Form -->
            <form method="GET" action="{{ route('owner.tenants.index') }}" class="flex flex-wrap items-center gap-2">
                @if($activeStatus && $activeStatus !== 'semua')
                    <input type="hidden" name="status" value="{{ $activeStatus }}" />
                @endif

                @if($ownerProperties->count() > 1)
                    <select
                        name="property_id"
                        onchange="this.form.submit()"
                        class="px-3 py-1.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] cursor-pointer"
                    >
                        <option value="">Semua Properti</option>
                        @foreach($ownerProperties as $prop)
                            <option value="{{ $prop->id }}" {{ (string)$selectedProperty === (string)$prop->id ? 'selected' : '' }}>
                                {{ $prop->name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <div class="relative min-w-[200px]">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#A5A096]">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        name="search"
                        value="{{ $searchQuery }}"
                        placeholder="Cari nama, email, hp..."
                        class="w-full pl-9 pr-3 py-1.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all"
                    />
                </div>

                <button type="submit" class="px-3 py-1.5 rounded-xl bg-[#F1EFEA] hover:bg-[#E5E2DA] border border-[#DEDAD1] font-semibold text-xs text-[#121417] cursor-pointer transition-colors">
                    Cari
                </button>

                @if($searchQuery || $selectedProperty)
                    <a href="{{ route('owner.tenants.index', ['status' => $activeStatus]) }}" class="px-2.5 py-1.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#6B6B6B] hover:text-[#121417] cursor-pointer">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Tenants List Table / Cards -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl shadow-[0_1px_3px_rgba(18,20,23,0.04)] overflow-hidden">
            @if($tenants->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#DEDAD1] bg-[#F9F8F5] text-[#6B6B6B] uppercase font-semibold text-[10px] tracking-wider">
                                <th class="py-3 px-5">Penyewa</th>
                                <th class="py-3 px-5">Kontak</th>
                                <th class="py-3 px-5">Properti &amp; Kamar</th>
                                <th class="py-3 px-5">Status</th>
                                <th class="py-3 px-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DEDAD1]/60 font-medium">
                            @foreach($tenants as $t)
                                @php
                                    $activeContract = $t->contracts->firstWhere('status', 'aktif');
                                    $latestInvitation = $t->acceptedInvitations->first();
                                    $assignedProperty = $activeContract?->property ?? $latestInvitation?->room?->property;
                                    $assignedRoom = $activeContract?->room ?? $latestInvitation?->room;
                                @endphp
                                <tr class="hover:bg-[#F9F8F5]/80 transition-colors">
                                    <!-- Tenant Info -->
                                    <td class="py-3.5 px-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-[#121417] text-white flex items-center justify-center font-bold text-xs shrink-0">
                                                {{ mb_substr($t->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-[#121417] text-xs">{{ $t->name }}</p>
                                                <p class="text-[11px] text-[#6B6B6B]">{{ $t->occupation ?? 'Penyewa Kos' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Contact Info -->
                                    <td class="py-3.5 px-5">
                                        <p class="font-medium text-[#121417]">{{ $t->email }}</p>
                                        <p class="text-[11px] text-[#6B6B6B]">{{ $t->phone ?? '-' }}</p>
                                    </td>

                                    <!-- Property & Room -->
                                    <td class="py-3.5 px-5">
                                        @if($assignedProperty && $assignedRoom)
                                            <p class="font-semibold text-[#121417]">{{ $assignedProperty->name }}</p>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono bg-[#F1EFEA] border border-[#DEDAD1] text-[#6B6B6B] mt-0.5">
                                                Kamar {{ $assignedRoom->room_number }}
                                            </span>
                                        @else
                                            <span class="text-[#A5A096] italic text-xs">Belum ditentukan</span>
                                        @endif
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-3.5 px-5">
                                        @if($activeContract)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                                                Sewa Aktif
                                            </span>
                                        @elseif($latestInvitation)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#D97706]"></span>
                                                Undangan Diterima
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]">
                                                Selesai
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Action Button -->
                                    <td class="py-3.5 px-5 text-right">
                                        <a href="{{ route('owner.tenants.show', $t) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer">
                                            <span>Detail</span>
                                            <svg class="w-3 h-3 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-12 px-4 rounded-2xl bg-[#F9F8F5] max-w-xl mx-auto my-6">
                    <div class="w-12 h-12 rounded-xl bg-white border border-[#DEDAD1] flex items-center justify-center text-[#6B6B6B] mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-[#121417]">Belum Ada Penyewa Ditemukan</h3>
                    <p class="text-xs text-[#6B6B6B] mt-1 max-w-sm mx-auto">
                        @if($searchQuery || $selectedProperty)
                            Tidak ada penyewa yang sesuai dengan filter pencarian Anda.
                        @else
                            Properti Anda belum memiliki penyewa aktif. Anda dapat mulai mengundang calon penghuni untuk kamar yang tersedia.
                        @endif
                    </p>
                    <div class="mt-4 flex items-center justify-center gap-2.5">
                        @if($searchQuery || $selectedProperty)
                            <a href="{{ route('owner.tenants.index') }}" class="px-3.5 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#6B6B6B] hover:text-[#121417] cursor-pointer">
                                Reset Filter
                            </a>
                        @endif
                        <a href="{{ route('owner.invitations.create') }}" class="px-4 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-xs active:scale-[0.98] transition-all cursor-pointer">
                            + Buat Undangan Penyewa
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.owner>
