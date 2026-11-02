<x-layouts.owner title="Detail Kontrak {{ $contract->contract_number }} — HUNI" activeNav="contracts">
    <div x-data="{
        showUploadModal: false,
        showRenewModal: false,
        showTerminateModal: false,
        showCancelModal: false,
    }" class="space-y-6 max-w-7xl mx-auto">
        <!-- Header Banner Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1.5">
                    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#6B6B6B]">
                        <a href="{{ route('owner.dashboard') }}" class="hover:text-[#121417] transition-colors">Dashboard</a>
                        <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <a href="{{ route('owner.contracts.index') }}" class="hover:text-[#121417] transition-colors">Kontrak</a>
                        <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="font-medium text-[#121417]">{{ $contract->contract_number }}</span>
                    </nav>

                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight text-[#121417] font-mono">
                            {{ $contract->contract_number }}
                        </h1>
                        @if($contract->status === 'aktif')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                                Aktif
                            </span>
                        @elseif($contract->status === 'draft')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#D97706]"></span>
                                Draft
                            </span>
                        @elseif($contract->status === 'dibatalkan')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#FEE2E2] text-[#B91C1C] border border-[#FECACA]">
                                Dibatalkan
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]">
                                Selesai
                            </span>
                        @endif
                    </div>

                    <p class="text-xs text-[#6B6B6B]">
                        Perjanjian sewa untuk <strong class="text-[#121417]">{{ $contract->property?->name }}</strong>, Kamar <strong class="text-[#121417]">{{ $contract->room?->room_number }}</strong> bersama <strong class="text-[#121417]">{{ $contract->tenant?->name }}</strong>.
                    </p>
                </div>

                <!-- Action Button Group -->
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    @if(!$contract->isCompleted() && !$contract->isCancelled())
                        <a
                            href="{{ route('owner.contracts.edit', $contract) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <span>Edit Kontrak</span>
                        </a>
                    @endif

                    @if(!$contract->isCancelled())
                        <button
                            type="button"
                            x-on:click="showRenewModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[#F0F7FF] border border-[#BAE6FD] font-semibold text-xs text-[#0369A1] hover:bg-[#e0f2fe] transition-colors cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-[#0284C7]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Perpanjang</span>
                        </button>
                    @endif

                    @if($contract->isActive())
                        <button
                            type="button"
                            x-on:click="showTerminateModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[#FEF3C7] border border-[#FDE68A] font-semibold text-xs text-[#92400E] hover:bg-[#fde68a]/50 transition-colors cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-[#D97706]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Selesaikan</span>
                        </button>
                    @endif

                    @if(!$contract->isCompleted() && !$contract->isCancelled())
                        <button
                            type="button"
                            x-on:click="showCancelModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-red-300 bg-red-50 hover:bg-red-100 font-semibold text-xs text-red-700 transition-colors cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>Batalkan</span>
                        </button>
                    @endif
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

        <!-- Progress Timeline / Duration Countdown Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-[#6B6B6B]">Periode Sewa Berjalan</p>
                    <p class="text-base font-bold text-[#121417] mt-0.5">
                        {{ $contract->start_date?->format('d F Y') }} — {{ $contract->end_date?->format('d F Y') }}
                        <span class="text-xs font-normal text-[#6B6B6B] ml-2">({{ $contract->duration_months }} Bulan)</span>
                    </p>
                </div>

                @if($contract->isActive())
                    @php
                        $daysLeft = $contract->daysRemaining();
                        $progress = $contract->progressPercentage();
                    @endphp
                    <div class="flex items-center gap-3">
                        <div class="px-3.5 py-1.5 rounded-xl border {{ $daysLeft <= 14 ? 'border-[#FECACA] bg-[#FEE2E2]' : 'border-[#C7F36B]/60 bg-[#E8F8D0]' }} text-center">
                            <p class="text-[10px] font-medium uppercase tracking-wider text-[#6B6B6B]">Sisa Masa Sewa</p>
                            <p class="text-sm font-bold {{ $daysLeft <= 14 ? 'text-[#B91C1C]' : 'text-[#15803D]' }}">
                                {{ $daysLeft > 0 ? "{$daysLeft} Hari" : ($daysLeft === 0 ? "Hari Terakhir" : "Lewat Tempo") }}
                            </p>
                        </div>
                    </div>
                @elseif($contract->isDraft())
                    <div class="px-3.5 py-1.5 rounded-xl border border-[#FDE68A] bg-[#FEF3C7] text-center">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-[#92400E]">Status</p>
                        <p class="text-xs font-semibold text-[#92400E]">Draft Menunggu Diaktifkan</p>
                    </div>
                @elseif($contract->isCompleted())
                    <div class="px-3.5 py-1.5 rounded-xl border border-[#DEDAD1] bg-[#F1EFEA] text-center">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-[#6B6B6B]">Status</p>
                        <p class="text-xs font-semibold text-[#6B6B6B]">Selesai Masa Sewa</p>
                    </div>
                @else
                    <div class="px-3.5 py-1.5 rounded-xl border border-[#FECACA] bg-[#FEE2E2] text-center">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-[#B91C1C]">Status</p>
                        <p class="text-xs font-semibold text-[#B91C1C]">Kontrak Dibatalkan</p>
                    </div>
                @endif
            </div>

            <!-- Progress Bar -->
            @if($contract->isActive())
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs text-[#6B6B6B]">
                        <span>Progres Masa Sewa</span>
                        <span class="font-mono font-semibold text-[#121417]">{{ $progress }}%</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-[#F1EFEA] overflow-hidden">
                        <div
                            class="h-full rounded-full bg-[#15803D] transition-all duration-500"
                            style="width: {{ $progress }}%"
                        ></div>
                    </div>
                </div>
            @endif
        </div>

        <!-- 3-Column Info Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Card 1: Informasi Penyewa -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-[#121417]">Profil Penyewa</h3>
                        <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#121417] text-white font-bold text-sm flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($contract->tenant?->name ?? 'P', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-sm text-[#121417] truncate leading-tight">{{ $contract->tenant?->name ?? '-' }}</p>
                            <p class="text-xs text-[#6B6B6B] truncate mt-0.5">{{ $contract->tenant?->email ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="space-y-2 text-xs text-[#121417] pt-2">
                        <div class="flex justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Nomor Telepon:</span>
                            <span class="font-semibold">{{ $contract->tenant?->phone ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">ID Akun:</span>
                            <span class="font-mono text-[11px] text-[#A5A096]">#{{ $contract->user_id }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 mt-4 border-t border-[#DEDAD1]">
                    <a
                        href="{{ route('owner.tenants.show', $contract->user_id) }}"
                        class="w-full block py-2 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] text-center text-xs font-semibold text-[#121417] hover:bg-[#F1EFEA] transition-colors"
                    >
                        Lihat Profil Penyewa ›
                    </a>
                </div>
            </div>

            <!-- Card 2: Informasi Kamar & Properti -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-[#121417]">Kamar &amp; Kos</h3>
                        <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>

                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-[#F1EFEA] border border-[#DEDAD1] text-xs font-mono font-semibold text-[#121417]">
                            Kamar {{ $contract->room?->room_number }}
                        </div>
                        <h4 class="font-bold text-base text-[#121417] mt-2 leading-tight">
                            {{ $contract->property?->name }}
                        </h4>
                        <p class="text-xs text-[#6B6B6B] mt-0.5">
                            {{ $contract->property?->address }}, {{ $contract->property?->city }}
                        </p>
                    </div>

                    <div class="space-y-2 text-xs text-[#121417] pt-2">
                        <div class="flex justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Lantai:</span>
                            <span class="font-semibold">Lantai {{ $contract->room?->floor ?? 1 }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#DEDAD1]/60">
                            <span class="text-[#6B6B6B]">Status Okupansi:</span>
                            <span class="font-semibold px-2 py-0.5 rounded-full text-[10px] {{ $contract->room?->status === 'terisi' ? 'bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60' : 'bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]' }}">
                                {{ ucfirst($contract->room?->status ?? 'kosong') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 mt-4 border-t border-[#DEDAD1]">
                    <a
                        href="{{ route('owner.properties.rooms.show', [$contract->property_id, $contract->room_id]) }}"
                        class="w-full block py-2 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] text-center text-xs font-semibold text-[#121417] hover:bg-[#F1EFEA] transition-colors"
                    >
                        Lihat Data Kamar ›
                    </a>
                </div>
            </div>

            <!-- Card 3: Rincian Finansial Kontrak -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between">
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-[#121417]">Keuangan Sewa</h3>
                        <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <p class="text-[11px] text-[#6B6B6B]">Harga Sewa / Bulan</p>
                            <p class="text-lg font-mono font-bold tabular-nums text-[#121417]">
                                Rp {{ number_format((float) $contract->monthly_rent, 0, ',', '.') }}
                            </p>
                        </div>

                        <div class="p-3 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] space-y-1.5 text-xs text-[#121417]">
                            <div class="flex justify-between">
                                <span class="text-[#6B6B6B]">Total Masa Sewa:</span>
                                <span class="font-mono font-semibold tabular-nums">Rp {{ number_format((float) $contract->total_amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[#6B6B6B]">Uang Jaminan:</span>
                                <div class="text-right">
                                    <span class="font-mono font-semibold tabular-nums">Rp {{ number_format((float) $contract->deposit_amount, 0, ',', '.') }}</span>
                                    <span class="block text-[10px] text-[#A5A096]">({{ str_replace('_', ' ', $contract->deposit_status) }})</span>
                                </div>
                            </div>
                            <div class="flex justify-between pt-1 border-t border-[#DEDAD1]/60">
                                <span class="text-[#6B6B6B]">Metode:</span>
                                <span class="font-semibold">{{ strtoupper(str_replace('_', ' ', $contract->payment_method ?? 'transfer')) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 mt-4 border-t border-[#DEDAD1]">
                    <div class="text-[11px] text-[#6B6B6B] text-center">
                        Tagihan dan kwitansi resmi dikelola pada modul Tagihan.
                    </div>
                </div>
            </div>
        </div>

        <!-- Catatan & Riwayat Kontrak -->
        @if($contract->notes)
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-2">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-[#121417]">
                    <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Catatan Kontrak &amp; Riwayat Perubahan</span>
                </div>
                <div class="p-3.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] text-xs text-[#121417] whitespace-pre-line leading-relaxed">
                    {{ $contract->notes }}
                </div>
            </div>
        @endif

        <!-- Dokumen Kontrak Section -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#DEDAD1] pb-4">
                <div>
                    <h2 class="text-base font-bold text-[#121417] tracking-tight">Dokumen &amp; Berkas Perjanjian</h2>
                    <p class="text-xs text-[#6B6B6B] mt-0.5">
                        Berkas kontrak sewa, KTP, atau dokumen pendukung yang tersimpan aman di server privat.
                    </p>
                </div>

                <button
                    type="button"
                    x-on:click="showUploadModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-xs active:scale-[0.98] transition-all cursor-pointer shrink-0"
                >
                    <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Unggah Dokumen Baru</span>
                </button>
            </div>

            @if($contract->documents->isEmpty())
                <div class="p-8 text-center rounded-xl bg-[#F9F8F5] border border-dashed border-[#DEDAD1] space-y-2">
                    <svg class="w-8 h-8 text-[#A5A096] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <p class="text-xs font-semibold text-[#121417]">Belum Ada Dokumen Terlampir</p>
                    <p class="text-[11px] text-[#6B6B6B]">
                        Unggah berkas perjanjian resmi (surat sewa yang ditandatangani, formulir, atau identitas).
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($contract->documents as $doc)
                        <div class="p-4 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex flex-col justify-between space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-lg bg-white border border-[#DEDAD1] flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-[#121417] truncate leading-tight" title="{{ $doc->file_name }}">
                                        {{ $doc->file_name }}
                                    </p>
                                    <p class="text-[10px] text-[#6B6B6B] mt-1">
                                        {{ $doc->formattedSize() }} • {{ $doc->created_at?->format('d/m/Y') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-2 pt-2 border-t border-[#DEDAD1]/60">
                                <a
                                    href="{{ route('owner.contracts.documents.download', [$contract, $doc]) }}"
                                    class="px-2.5 py-1.5 rounded-lg bg-white border border-[#DEDAD1] text-[11px] font-semibold text-[#121417] hover:bg-[#F1EFEA] transition-colors flex items-center gap-1.5"
                                >
                                    <svg class="w-3.5 h-3.5 text-[#6B6B6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span>Unduh</span>
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route('owner.contracts.documents.destroy', [$contract, $doc]) }}"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus berkas dokumen ini? Tindakan ini tidak dapat dibatalkan.');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="p-1.5 rounded-lg border border-red-200 bg-white text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
                                        title="Hapus Dokumen"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- MODAL: Upload Dokumen Baru -->
        <div
            x-show="showUploadModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#121417]/50 backdrop-blur-xs"
        >
            <div
                x-show="showUploadModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-on:click.outside="showUploadModal = false"
                class="bg-white border border-[#DEDAD1] rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4"
            >
                <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                    <h3 class="text-sm font-bold text-[#121417]">Unggah Dokumen Kontrak</h3>
                    <button
                        type="button"
                        x-on:click="showUploadModal = false"
                        class="text-[#A5A096] hover:text-[#121417] cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form
                    method="POST"
                    action="{{ route('owner.contracts.documents.upload', $contract) }}"
                    enctype="multipart/form-data"
                    class="space-y-4"
                >
                    @csrf
                    <div>
                        <label for="title" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Judul / Keterangan Dokumen (Opsional)
                        </label>
                        <input
                            type="text"
                            name="title"
                            id="title"
                            placeholder="Contoh: Perjanjian Sewa Bertandatangan"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417]"
                        />
                    </div>

                    <div>
                        <label for="modal_document" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Pilih Berkas <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="file"
                            name="document"
                            id="modal_document"
                            accept=".pdf,.jpg,.jpeg,.png"
                            required
                            class="w-full px-3.5 py-2 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border file:border-[#DEDAD1] file:text-xs file:font-semibold file:bg-white file:text-[#121417] file:cursor-pointer"
                        />
                        <p class="text-[11px] text-[#6B6B6B] mt-1">
                            PDF, JPG, JPEG, PNG (Maksimal 10MB)
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#DEDAD1]">
                        <button
                            type="button"
                            x-on:click="showUploadModal = false"
                            class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] text-xs font-semibold text-[#6B6B6B] hover:text-[#121417] cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-xs font-semibold text-white shadow-xs active:scale-[0.98] transition-all cursor-pointer"
                        >
                            Unggah Dokumen
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: Perpanjangan Kontrak -->
        <div
            x-show="showRenewModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#121417]/50 backdrop-blur-xs"
        >
            <div
                x-show="showRenewModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-on:click.outside="showRenewModal = false"
                class="bg-white border border-[#DEDAD1] rounded-2xl max-w-lg w-full p-6 sm:p-7 shadow-xl space-y-4"
            >
                <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                    <div>
                        <h3 class="text-base font-bold text-[#121417]">Perpanjang Kontrak Sewa</h3>
                        <p class="text-xs text-[#6B6B6B]">Membuat kontrak lanjutan baru tanpa menghapus riwayat lama.</p>
                    </div>
                    <button
                        type="button"
                        x-on:click="showRenewModal = false"
                        class="text-[#A5A096] hover:text-[#121417] cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form
                    method="POST"
                    action="{{ route('owner.contracts.renew', $contract) }}"
                    enctype="multipart/form-data"
                    class="space-y-4"
                >
                    @csrf

                    @php
                        $renewalStart = $contract->end_date ? $contract->end_date->addDay()->format('Y-m-d') : now()->format('Y-m-d');
                        $renewalEnd = $contract->end_date ? $contract->end_date->addDay()->addMonths(max(1, $contract->duration_months))->format('Y-m-d') : now()->addMonths(1)->format('Y-m-d');
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="renew_start_date" class="block text-xs font-semibold text-[#121417] mb-1.5">
                                Tanggal Mulai Baru <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                name="start_date"
                                id="renew_start_date"
                                value="{{ $renewalStart }}"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417]"
                            />
                        </div>

                        <div>
                            <label for="renew_end_date" class="block text-xs font-semibold text-[#121417] mb-1.5">
                                Tanggal Selesai Baru <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                name="end_date"
                                id="renew_end_date"
                                value="{{ $renewalEnd }}"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417]"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="renew_monthly_rent" class="block text-xs font-semibold text-[#121417] mb-1.5">
                                Harga Sewa / Bulan (Rp) <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                name="monthly_rent"
                                id="renew_monthly_rent"
                                value="{{ (int)$contract->monthly_rent }}"
                                min="0"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-mono font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417]"
                            />
                        </div>

                        <div>
                            <label for="renew_deposit_amount" class="block text-xs font-semibold text-[#121417] mb-1.5">
                                Deposit Tambahan (Rp)
                            </label>
                            <input
                                type="number"
                                name="deposit_amount"
                                id="renew_deposit_amount"
                                value="0"
                                min="0"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-mono font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417]"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="renew_notes" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Catatan Perpanjangan
                        </label>
                        <textarea
                            name="notes"
                            id="renew_notes"
                            rows="2"
                            placeholder="Contoh: Kesepakatan perpanjangan periode sewa tahun kedua."
                            class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] leading-relaxed"
                        ></textarea>
                    </div>

                    <div>
                        <label for="renew_document" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Dokumen Adendum / Perpanjangan (Opsional)
                        </label>
                        <input
                            type="file"
                            name="document"
                            id="renew_document"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="w-full px-3.5 py-2 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border file:border-[#DEDAD1] file:text-xs file:font-semibold file:bg-white file:text-[#121417] file:cursor-pointer"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#DEDAD1]">
                        <button
                            type="button"
                            x-on:click="showRenewModal = false"
                            class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] text-xs font-semibold text-[#6B6B6B] hover:text-[#121417] cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-xs font-semibold text-white shadow-xs active:scale-[0.98] transition-all cursor-pointer"
                        >
                            Simpan Perpanjangan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: Selesaikan Kontrak (Terminate) -->
        <div
            x-show="showTerminateModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#121417]/50 backdrop-blur-xs"
        >
            <div
                x-show="showTerminateModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-on:click.outside="showTerminateModal = false"
                class="bg-white border border-[#DEDAD1] rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4"
            >
                <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                    <h3 class="text-base font-bold text-[#121417]">Selesaikan Kontrak Sewa</h3>
                    <button
                        type="button"
                        x-on:click="showTerminateModal = false"
                        class="text-[#A5A096] hover:text-[#121417] cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <p class="text-xs text-[#6B6B6B] leading-relaxed">
                    Menandai kontrak ini sebagai <strong class="text-[#121417]">SELESAI</strong>. Status kamar akan diperbarui menjadi kosong jika tidak ada kontrak aktif lain atau reservasi valid.
                </p>

                <form method="POST" action="{{ route('owner.contracts.terminate', $contract) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="termination_notes" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Catatan Penyelesaian (Opsional)
                        </label>
                        <textarea
                            name="termination_notes"
                            id="termination_notes"
                            rows="3"
                            placeholder="Contoh: Kunci kamar dan deposit telah diselesaikan dengan baik."
                            class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] leading-relaxed"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-[#DEDAD1]">
                        <button
                            type="button"
                            x-on:click="showTerminateModal = false"
                            class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] text-xs font-semibold text-[#6B6B6B] hover:text-[#121417] cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-xs font-semibold text-white shadow-xs active:scale-[0.98] transition-all cursor-pointer"
                        >
                            Konfirmasi Selesai
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: Batalkan Kontrak (Cancel) -->
        <div
            x-show="showCancelModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#121417]/50 backdrop-blur-xs"
        >
            <div
                x-show="showCancelModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-on:click.outside="showCancelModal = false"
                class="bg-white border border-[#DEDAD1] rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4"
            >
                <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                    <h3 class="text-base font-bold text-red-600">Batalkan Kontrak Sewa</h3>
                    <button
                        type="button"
                        x-on:click="showCancelModal = false"
                        class="text-[#A5A096] hover:text-[#121417] cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <p class="text-xs text-[#6B6B6B] leading-relaxed">
                    Kontrak yang dibatalkan tidak dapat diaktifkan kembali. Status kamar akan disinkronkan kembali.
                </p>

                <form method="POST" action="{{ route('owner.contracts.cancel', $contract) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="cancellation_reason" class="block text-xs font-semibold text-[#121417] mb-1.5">
                            Alasan Pembatalan
                        </label>
                        <textarea
                            name="cancellation_reason"
                            id="cancellation_reason"
                            rows="3"
                            placeholder="Contoh: Calon penyewa membatalkan kepindahan ke kota ini."
                            class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDAD1] bg-[#F9F8F5] text-xs font-medium text-[#121417] focus:outline-hidden focus:bg-white focus:border-[#121417] leading-relaxed"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-[#DEDAD1]">
                        <button
                            type="button"
                            x-on:click="showCancelModal = false"
                            class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] text-xs font-semibold text-[#6B6B6B] hover:text-[#121417] cursor-pointer"
                        >
                            Tutup
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-xs font-semibold text-white shadow-xs active:scale-[0.98] transition-all cursor-pointer"
                        >
                            Batalkan Kontrak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.owner>
