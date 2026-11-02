<x-layouts.owner title="Detail Penyewa: {{ $tenant->name }} — HUNI" activeNav="tenants">
    <div class="space-y-6 max-w-5xl mx-auto">
        <!-- Breadcrumb & Top Bar -->
        <div class="flex items-center justify-between gap-4">
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#9E9E9E]">
                <a href="{{ route('owner.tenants.index') }}" class="hover:text-white transition-colors">Penghuni</a>
                <svg class="w-3.5 h-3.5 text-[#757575]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="font-medium text-white">{{ $tenant->name }}</span>
            </nav>

            <a
                href="{{ route('owner.tenants.index') }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer"
            >
                <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar</span>
            </a>
        </div>

        <!-- Tenant Profile Header Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4 sm:gap-5">
                <div class="w-16 h-16 rounded-2xl bg-[#121417] text-white flex items-center justify-center font-bold text-xl shrink-0">
                    {{ mb_substr($tenant->name, 0, 1) }}
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#121417]">{{ $tenant->name }}</h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                            Penyewa
                        </span>
                    </div>
                    <p class="text-xs text-[#6B6B6B]">{{ $tenant->occupation ?? 'Penyewa Terdaftar di HUNI' }}</p>
                    @if($tenant->nik)
                        <p class="text-[11px] font-mono text-[#A5A096]">NIK: {{ $tenant->nik }}</p>
                    @endif
                </div>
            </div>

            <div class="p-4 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] space-y-2 text-xs shrink-0">
                <div class="flex items-center gap-2 text-[#121417]">
                    <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>{{ $tenant->email }}</span>
                </div>
                <div class="flex items-center gap-2 text-[#121417]">
                    <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span>{{ $tenant->phone ?? '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Active Tenancy / Contract Info Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <div class="flex items-center justify-between pb-3 mb-5 border-b border-[#DEDAD1]">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-center justify-center text-[#121417]">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-[#121417]">Status Penyewaan Saat Ini</h2>
                        <p class="text-xs text-[#6B6B6B]">Kondisi sewa kamar pada properti Anda</p>
                    </div>
                </div>

                @if($activeContract)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                        Sewa Aktif
                    </span>
                @elseif($invitations->isNotEmpty() && $invitations->first()->isAccepted())
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#D97706]"></span>
                        Undangan Diterima
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]">
                        Tidak Ada Sewa Aktif
                    </span>
                @endif
            </div>

            @if($activeContract)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="p-4 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1]">
                        <span class="text-[10px] font-semibold uppercase text-[#6B6B6B] block">Properti</span>
                        <p class="text-xs font-bold text-[#121417] mt-0.5">{{ $activeContract->property->name }}</p>
                    </div>

                    <div class="p-4 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1]">
                        <span class="text-[10px] font-semibold uppercase text-[#6B6B6B] block">Kamar</span>
                        <p class="text-xs font-bold text-[#121417] mt-0.5">
                            Kamar {{ $activeContract->room->room_number }} (Lt. {{ $activeContract->room->floor }})
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1]">
                        <span class="text-[10px] font-semibold uppercase text-[#6B6B6B] block">Harga Sewa</span>
                        <p class="text-xs font-mono font-bold tabular-nums text-[#121417] mt-0.5">
                            Rp {{ number_format((float) $activeContract->monthly_rent, 0, ',', '.') }} / bln
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1]">
                        <span class="text-[10px] font-semibold uppercase text-[#6B6B6B] block">Periode Kontrak</span>
                        <p class="text-xs font-semibold text-[#121417] mt-0.5">
                            {{ $activeContract->start_date?->translatedFormat('d M Y') }} — {{ $activeContract->end_date?->translatedFormat('d M Y') }}
                        </p>
                    </div>
                </div>

                @if($activeContract->notes)
                    <div class="p-4 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] text-xs">
                        <span class="font-semibold text-[#121417] block mb-1">Catatan Sewa:</span>
                        <p class="text-[#6B6B6B] leading-relaxed">{{ $activeContract->notes }}</p>
                    </div>
                @endif
            @elseif($invitations->isNotEmpty() && $invitations->first()->isAccepted())
                @php $firstInv = $invitations->first(); @endphp
                <div class="p-4 sm:p-5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-center justify-between flex-wrap gap-4">
                    <div class="space-y-0.5">
                        <h3 class="text-sm font-bold text-[#121417]">Undangan Telah Diterima</h3>
                        <p class="text-xs text-[#6B6B6B]">
                            Penyewa telah menyetujui undangan untuk <strong class="text-[#121417]">{{ $firstInv->room->property->name }} — Kamar {{ $firstInv->room->room_number }}</strong> pada {{ $firstInv->accepted_at?->translatedFormat('d F Y, H:i') }}.
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-lg bg-[#E8F8D0] border border-[#C7F36B]/60 text-xs font-semibold text-[#15803D]">
                        Siap Pembuatan Kontrak
                    </span>
                </div>
            @else
                <div class="text-center py-8 px-4 rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1]">
                    <p class="text-sm font-semibold text-[#121417]">Tidak Ada Kontrak Aktif</p>
                    <p class="text-xs text-[#6B6B6B] mt-1">Penyewa ini sebelumnya pernah menyewa atau telah menyelesaikan masa sewanya.</p>
                </div>
            @endif
        </div>

        <!-- History: Contracts & Invitations Split Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Riwayat Kontrak -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center gap-2 pb-3 mb-4 border-b border-[#DEDAD1]">
                    <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-sm font-bold text-[#121417]">Riwayat Kontrak</h3>
                </div>

                @if($contracts->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($contracts as $c)
                            <div class="p-3.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-mono font-bold text-[#121417]">{{ $c->contract_number }}</p>
                                    <p class="text-xs font-medium text-[#121417] mt-0.5">{{ $c->property->name }} • Kamar {{ $c->room->room_number }}</p>
                                    <p class="text-[11px] text-[#6B6B6B] mt-0.5">
                                        {{ $c->start_date?->translatedFormat('d M Y') }} s/d {{ $c->end_date?->translatedFormat('d M Y') }}
                                    </p>
                                </div>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $c->status === 'aktif' ? 'bg-[#E8F8D0] text-[#15803D] border-[#C7F36B]/60' : 'bg-white text-[#6B6B6B] border-[#DEDAD1]' }}">
                                    {{ ucfirst($c->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1]">
                        <p class="text-xs text-[#6B6B6B]">Belum ada riwayat kontrak.</p>
                    </div>
                @endif
            </div>

            <!-- Riwayat Undangan Diterima -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex items-center gap-2 pb-3 mb-4 border-b border-[#DEDAD1]">
                    <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-sm font-bold text-[#121417]">Riwayat Undangan</h3>
                </div>

                @if($invitations->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($invitations as $inv)
                            <div class="p-3.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-medium text-[#121417]">{{ $inv->room->property->name }} • Kamar {{ $inv->room->room_number }}</p>
                                    <p class="text-[11px] text-[#6B6B6B] mt-0.5">
                                        Diterima pada {{ $inv->accepted_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                                    </p>
                                    @if($inv->message)
                                        <p class="text-[11px] italic text-[#6B6B6B] mt-1 leading-relaxed">"{{ $inv->message }}"</p>
                                    @endif
                                </div>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                                    Diterima
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1]">
                        <p class="text-xs text-[#6B6B6B]">Tidak ada data undangan.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.owner>
