<x-layouts.owner title="Detail Undangan: {{ $invitation->name }} — HUNI" activeNav="invitations">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Breadcrumb & Navigation -->
        <div class="flex items-center justify-between gap-4">
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#9E9E9E]">
                <a href="{{ route('owner.tenants.index') }}" class="hover:text-white transition-colors">Penghuni</a>
                <svg class="w-3.5 h-3.5 text-[#757575]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <a href="{{ route('owner.invitations.index') }}" class="hover:text-white transition-colors">Undangan</a>
                <svg class="w-3.5 h-3.5 text-[#757575]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="font-medium text-white">Detail Undangan</span>
            </nav>

            <a
                href="{{ route('owner.invitations.index') }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer"
            >
                <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar</span>
            </a>
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

        <!-- Prominent Copyable Link Card -->
        @if($invitation->isPending())
            <div class="bg-[#F9F8F5] border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-3" x-data="{ copied: false }">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-white border border-[#DEDAD1] flex items-center justify-center text-[#121417]">
                        <svg class="w-4 h-4 text-[#15803D]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-[#121417]">Link Undangan Siap Dibagikan</h2>
                        <p class="text-xs text-[#6B6B6B]">Kirimkan tautan ini langsung ke calon penyewa melalui WhatsApp atau pesan pribadi.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input
                        type="text"
                        readonly
                        value="{{ $invitation->invitation_url }}"
                        class="flex-1 px-3.5 py-2.5 bg-white border border-[#DEDAD1] rounded-xl text-xs font-mono font-medium text-[#121417] focus:outline-hidden select-all"
                    />
                    <button
                        type="button"
                        x-on:click="navigator.clipboard.writeText('{{ $invitation->invitation_url }}'); copied = true; setTimeout(() => copied = false, 2500)"
                        class="px-4 py-2.5 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-xs active:scale-[0.98] transition-all cursor-pointer whitespace-nowrap flex items-center gap-1.5"
                    >
                        <span x-show="!copied">Salin Link</span>
                        <span x-show="copied" x-cloak class="text-[#C7F36B] font-bold">✓ Tersalin!</span>
                    </button>
                </div>
            </div>
        @endif

        <!-- Invitation Details Main Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-[#DEDAD1] flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-[#121417] text-white flex items-center justify-center font-bold text-base">
                        {{ mb_substr($invitation->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg font-bold text-[#121417]">{{ $invitation->name }}</h2>
                            @if($invitation->isAccepted())
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">Diterima</span>
                            @elseif($invitation->isRevoked())
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#FEE2E2] text-[#B91C1C] border border-[#FECACA]">Dibatalkan</span>
                            @elseif($invitation->isExpired())
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]">Kedaluwarsa</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">Menunggu Konfirmasi</span>
                            @endif
                        </div>
                        <p class="text-xs text-[#6B6B6B] mt-0.5">{{ $invitation->email }} • {{ $invitation->phone ?? 'No HP tidak dicantumkan' }}</p>
                    </div>
                </div>

                @if($invitation->isPending())
                    <form method="POST" action="{{ route('owner.invitations.revoke', $invitation) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan undangan ini?');">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 rounded-xl border border-red-300 bg-red-50 hover:bg-red-100 text-red-700 font-semibold text-xs transition-colors cursor-pointer active:scale-[0.98]">
                            Batalkan Undangan
                        </button>
                    </form>
                @endif
            </div>

            <!-- 2-Column Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Kamar & Properti -->
                <div class="p-4 sm:p-5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] space-y-3">
                    <h3 class="text-xs font-semibold uppercase text-[#6B6B6B] tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Kamar Tujuan</span>
                    </h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Properti</span>
                            <span class="font-semibold text-[#121417]">{{ $invitation->room->property->name }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Nomor Kamar</span>
                            <span class="font-medium text-[#121417]">Kamar {{ $invitation->room->room_number }} (Lantai {{ $invitation->room->floor }})</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Kategori</span>
                            <span class="font-medium text-[#121417]">{{ $invitation->room->type }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Tarif Sewa</span>
                            <span class="font-mono font-bold tabular-nums text-[#121417]">Rp {{ number_format((float) $invitation->room->price, 0, ',', '.') }} / bln</span>
                        </div>
                    </div>
                </div>

                <!-- Masa Berlaku & Waktu -->
                <div class="p-4 sm:p-5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] space-y-3">
                    <h3 class="text-xs font-semibold uppercase text-[#6B6B6B] tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Masa Berlaku</span>
                    </h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Dibuat Pada</span>
                            <span class="font-medium text-[#121417]">{{ $invitation->created_at->translatedFormat('d M Y, H:i') }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Batas Waktu</span>
                            <span class="font-medium text-[#121417]">{{ $invitation->valid_until->translatedFormat('d M Y, H:i') }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Sisa Waktu</span>
                            @if($invitation->isPending())
                                <span class="text-[#15803D] font-medium">{{ $invitation->valid_until->diffForHumans() }}</span>
                            @else
                                <span class="text-[#6B6B6B]">Telah berakhir</span>
                            @endif
                        </div>
                        @if($invitation->isAccepted())
                            <div class="flex justify-between py-1 border-b border-[#DEDAD1]/60">
                                <span class="text-[#6B6B6B]">Diterima Pada</span>
                                <span class="text-[#15803D] font-semibold">{{ $invitation->accepted_at?->translatedFormat('d M Y, H:i') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Pesan Undangan (if any) -->
            @if($invitation->message)
                <div class="p-4 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1]">
                    <span class="text-[10px] font-semibold uppercase text-[#6B6B6B] block mb-1">Pesan Selamat Datang:</span>
                    <p class="text-xs text-[#121417] italic leading-relaxed">"{{ $invitation->message }}"</p>
                </div>
            @endif

            <!-- Jika Undangan Sudah Diterima -->
            @if($invitation->isAccepted() && $invitation->tenant)
                <div class="p-4 sm:p-5 rounded-xl bg-[#E8F8D0]/30 border border-[#C7F36B]/60 flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-[#121417] text-white flex items-center justify-center text-xs font-bold">
                            {{ mb_substr($invitation->tenant->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-xs font-bold text-[#121417]">Akun Penyewa Terhubung</p>
                            <p class="text-[11px] text-[#6B6B6B]">{{ $invitation->tenant->name }} ({{ $invitation->tenant->email }})</p>
                        </div>
                    </div>
                    <a
                        href="{{ route('owner.tenants.show', $invitation->tenant) }}"
                        class="px-3.5 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                    >
                        Lihat Profil Penyewa →
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-layouts.owner>
