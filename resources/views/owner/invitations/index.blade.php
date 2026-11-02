<x-layouts.owner title="Undangan Penyewa — HUNI" activeNav="invitations">
    <div class="space-y-6 max-w-7xl mx-auto">
        <!-- Header Banner Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1.5">
                    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#6B6B6B]">
                        <a href="{{ route('owner.tenants.index') }}" class="hover:text-[#121417] transition-colors">Penghuni</a>
                        <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="font-medium text-[#121417]">Undangan Penyewa</span>
                    </nav>
                    <h1 class="text-2xl font-bold tracking-tight text-[#121417]">
                        Undangan Calon Penyewa
                    </h1>
                    <p class="text-xs text-[#6B6B6B]">
                        Buat dan kelola tautan undangan kamar untuk calon penghuni baru kos Anda.
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a
                        href="{{ route('owner.invitations.create') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-[0_1px_2px_rgba(18,20,23,0.08)] active:scale-[0.98] transition-all cursor-pointer"
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

        @if(session('error'))
            <x-ui.alert type="danger" :dismissible="true">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <!-- 4 Quick Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <x-ui.stat-card
                label="Total Undangan"
                :value="$totalInvitations"
                icon="mail"
            />
            <x-ui.stat-card
                label="Menunggu"
                :value="$activeCount"
                icon="clock"
            />
            <x-ui.stat-card
                label="Diterima"
                :value="$acceptedCount"
                icon="check-circle"
            />
            <x-ui.stat-card
                label="Kedaluwarsa / Batal"
                :value="$expiredCount"
                icon="alert-triangle"
            />
        </div>

        <!-- Toolbar: Status Filter & Search -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-4 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                <a href="{{ route('owner.invitations.index', ['search' => $searchQuery]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer whitespace-nowrap {{ $activeStatus === 'semua' ? 'bg-[#121417] text-white shadow-xs' : 'bg-white text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] border border-[#DEDAD1]' }}">
                    Semua ({{ $totalInvitations }})
                </a>
                <a href="{{ route('owner.invitations.index', ['status' => 'aktif', 'search' => $searchQuery]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer whitespace-nowrap {{ $activeStatus === 'aktif' ? 'bg-[#121417] text-white shadow-xs' : 'bg-white text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] border border-[#DEDAD1]' }}">
                    Menunggu ({{ $activeCount }})
                </a>
                <a href="{{ route('owner.invitations.index', ['status' => 'diterima', 'search' => $searchQuery]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer whitespace-nowrap {{ $activeStatus === 'diterima' ? 'bg-[#121417] text-white shadow-xs' : 'bg-white text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] border border-[#DEDAD1]' }}">
                    Diterima ({{ $acceptedCount }})
                </a>
                <a href="{{ route('owner.invitations.index', ['status' => 'kadaluarsa', 'search' => $searchQuery]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer whitespace-nowrap {{ $activeStatus === 'kadaluarsa' ? 'bg-[#121417] text-white shadow-xs' : 'bg-white text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] border border-[#DEDAD1]' }}">
                    Kedaluwarsa
                </a>
                <a href="{{ route('owner.invitations.index', ['status' => 'dibatalkan', 'search' => $searchQuery]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer whitespace-nowrap {{ $activeStatus === 'dibatalkan' ? 'bg-[#121417] text-white shadow-xs' : 'bg-white text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] border border-[#DEDAD1]' }}">
                    Dibatalkan
                </a>
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('owner.invitations.index') }}" class="flex items-center gap-2">
                @if($activeStatus && $activeStatus !== 'semua')
                    <input type="hidden" name="status" value="{{ $activeStatus }}" />
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
                        placeholder="Cari nama, email, kamar..."
                        class="w-full pl-9 pr-3 py-1.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs font-medium text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all"
                    />
                </div>
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-[#F1EFEA] hover:bg-[#E5E2DA] border border-[#DEDAD1] font-semibold text-xs text-[#121417] cursor-pointer transition-colors">
                    Cari
                </button>
            </form>
        </div>

        <!-- Invitations Table Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl shadow-[0_1px_3px_rgba(18,20,23,0.04)] overflow-hidden">
            @if($invitations->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#DEDAD1] bg-[#F9F8F5] text-[#6B6B6B] uppercase font-semibold text-[10px] tracking-wider">
                                <th class="py-3 px-5">Calon Penyewa</th>
                                <th class="py-3 px-5">Properti &amp; Kamar</th>
                                <th class="py-3 px-5">Status</th>
                                <th class="py-3 px-5">Masa Berlaku</th>
                                <th class="py-3 px-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DEDAD1]/60 font-medium">
                            @foreach($invitations as $inv)
                                <tr class="hover:bg-[#F9F8F5]/80 transition-colors">
                                    <!-- Calon Penyewa -->
                                    <td class="py-3.5 px-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-[#121417] text-white flex items-center justify-center font-bold text-xs shrink-0">
                                                {{ mb_substr($inv->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-[#121417] text-xs">{{ $inv->name }}</p>
                                                <p class="text-[11px] text-[#6B6B6B]">{{ $inv->email }}</p>
                                                @if($inv->phone)
                                                    <p class="text-[10px] text-[#A5A096]">{{ $inv->phone }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Properti & Kamar -->
                                    <td class="py-3.5 px-5">
                                        <p class="font-semibold text-[#121417]">{{ $inv->room->property->name }}</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono bg-[#F1EFEA] border border-[#DEDAD1] text-[#6B6B6B] mt-0.5">
                                            Kamar {{ $inv->room->room_number }} (Lt. {{ $inv->room->floor }})
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="py-3.5 px-5">
                                        @if($inv->isAccepted())
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                                                Diterima
                                            </span>
                                        @elseif($inv->isRevoked())
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEE2E2] text-[#B91C1C] border border-[#FECACA]">
                                                Dibatalkan
                                            </span>
                                        @elseif($inv->isExpired())
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]">
                                                Kedaluwarsa
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#D97706]"></span>
                                                Menunggu
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Masa Berlaku -->
                                    <td class="py-3.5 px-5">
                                        <p class="font-medium text-[#121417]">{{ $inv->valid_until->translatedFormat('d M Y, H:i') }}</p>
                                        @if($inv->isPending())
                                            <span class="text-[10px] font-medium text-[#15803D]">{{ $inv->valid_until->diffForHumans() }}</span>
                                        @elseif($inv->isAccepted())
                                            <span class="text-[10px] text-[#6B6B6B]">Diterima {{ $inv->accepted_at?->diffForHumans() }}</span>
                                        @else
                                            <span class="text-[10px] text-[#A5A096]">Kedaluwarsa</span>
                                        @endif
                                    </td>

                                    <!-- Aksi -->
                                    <td class="py-3.5 px-5 text-right">
                                        <div class="flex items-center justify-end gap-1.5" x-data="{ copied: false }">
                                            @if($inv->isPending())
                                                <button
                                                    type="button"
                                                    x-on:click="navigator.clipboard.writeText('{{ $inv->invitation_url }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                                    class="px-2.5 py-1.5 rounded-lg bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer flex items-center gap-1"
                                                    title="Salin Link Undangan"
                                                >
                                                    <span x-show="!copied" class="inline-flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                        </svg>
                                                        <span>Salin Link</span>
                                                    </span>
                                                    <span x-show="copied" class="text-[#15803D] inline-flex items-center gap-1 font-semibold" x-cloak>
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        <span>Tersalin!</span>
                                                    </span>
                                                </button>

                                                <form method="POST" action="{{ route('owner.invitations.revoke', $inv) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan undangan ini?');">
                                                    @csrf
                                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg border border-[#FECACA] bg-[#FEE2E2] text-[#DC2626] font-semibold text-xs hover:bg-[#fca5a5]/30 transition-colors cursor-pointer" title="Batalkan Undangan">
                                                        Batal
                                                    </button>
                                                </form>
                                            @endif

                                            <a href="{{ route('owner.invitations.show', $inv) }}" class="px-3 py-1.5 rounded-lg bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer">
                                                Detail
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12 px-4 rounded-2xl bg-[#F9F8F5] max-w-xl mx-auto my-6">
                    <div class="w-12 h-12 rounded-xl bg-white border border-[#DEDAD1] flex items-center justify-center text-[#6B6B6B] mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-[#121417]">Belum Ada Undangan Dibuat</h3>
                    <p class="text-xs text-[#6B6B6B] mt-1 max-w-sm mx-auto">
                        Buat link undangan untuk calon penyewa dan kirimkan tautan tersebut melalui pesan atau WhatsApp secara langsung.
                    </p>
                    <a
                        href="{{ route('owner.invitations.create') }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs mt-4 transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Buat Undangan Sekarang</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-layouts.owner>
