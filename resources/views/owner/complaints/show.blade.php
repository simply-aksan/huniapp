<x-layouts.owner title="Detail Tiket {{ $complaint->ticket_number }} — HUNI" activeNav="complaints">
    <div x-data="{ showStatusModal: false }" class="space-y-6">
        <!-- Header Banner Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.05)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-medium text-[#6B6B6B] mb-2">
                        <a href="{{ route('owner.dashboard') }}" class="hover:text-[#121417] transition-colors">Dashboard</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <a href="{{ route('owner.complaints.index') }}" class="hover:text-[#121417] transition-colors">Komplain & Pengaduan</a>
                        <span class="text-[#DEDAD1]">/</span>
                        <span class="text-[#121417] font-semibold">{{ $complaint->ticket_number }}</span>
                    </nav>

                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] font-mono tracking-tight leading-none">
                            {{ $complaint->ticket_number }}
                        </h1>

                        @php
                            $statusBadgeClass = match($complaint->status) {
                                \App\Enums\ComplaintStatus::Baru->value => 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
                                \App\Enums\ComplaintStatus::Diproses->value => 'bg-[#EFF6FF] text-[#1D4ED8] border-[#BFDBFE]',
                                \App\Enums\ComplaintStatus::MenungguTanggapan->value => 'bg-[#FFEDD5] text-[#C2410C] border-[#FED7AA]',
                                \App\Enums\ComplaintStatus::Selesai->value => 'bg-[#E8F8D0] text-[#15803D] border-[#C7F36B]/60',
                                \App\Enums\ComplaintStatus::Ditutup->value => 'bg-[#F4F2EC] text-[#6B6B6B] border-[#DEDAD1]',
                                default => 'bg-white text-[#121417] border-[#DEDAD1]',
                            };

                            $priorityBadgeClass = match($complaint->priority) {
                                \App\Enums\ComplaintPriority::Darurat->value => 'bg-[#FEE2E2] text-[#B91C1C] border-[#FECACA]',
                                \App\Enums\ComplaintPriority::Tinggi->value => 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
                                \App\Enums\ComplaintPriority::Sedang->value => 'bg-[#EFF6FF] text-[#1D4ED8] border-[#BFDBFE]',
                                \App\Enums\ComplaintPriority::Rendah->value => 'bg-[#F4F2EC] text-[#6B6B6B] border-[#DEDAD1]',
                                default => 'bg-white text-[#121417] border-[#DEDAD1]',
                            };
                        @endphp

                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusBadgeClass }}">
                            ● {{ $complaint->statusLabel() }}
                        </span>

                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-[#F4F2EC] border-[#DEDAD1] text-[#121417]">
                            {{ $complaint->categoryLabel() }}
                        </span>

                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $priorityBadgeClass }}">
                            {{ $complaint->priorityLabel() }}
                        </span>
                    </div>

                    <p class="text-xs sm:text-sm text-[#6B6B6B] mt-2">
                        Penyewa: <span class="text-[#121417] font-semibold">{{ $complaint->user?->name ?? 'Penyewa' }}</span>
                        • Properti: <span class="text-[#121417] font-semibold">{{ $complaint->property?->name ?? '-' }}</span>
                        (Kamar <span class="text-[#121417] font-semibold">{{ $complaint->room?->room_number ?? '-' }}</span>)
                        • Masuk: <span class="text-[#121417]">{{ $complaint->created_at->format('d/m/Y H:i') }}</span>
                        @if($complaint->resolved_at)
                            • Diselesaikan: <span class="text-[#15803D] font-semibold">{{ $complaint->resolved_at->format('d/m/Y H:i') }}</span>
                        @endif
                        @if($complaint->closed_at)
                            • Ditutup: <span class="text-[#6B6B6B] font-semibold">{{ $complaint->closed_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </p>
                </div>

                <!-- Action Button Group -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <x-ui.button href="{{ route('owner.complaints.index') }}" variant="outline" size="sm" class="gap-1.5">
                        <x-ui.icon name="arrow-left" class="w-4 h-4" />
                        <span>Daftar Pengaduan</span>
                    </x-ui.button>

                    <x-ui.button
                        type="button"
                        x-on:click="showStatusModal = true"
                        variant="primary"
                        size="sm"
                        class="gap-1.5"
                    >
                        <x-ui.icon name="settings" class="w-4 h-4" />
                        <span>Perbarui Status & Teknisi</span>
                    </x-ui.button>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <x-ui.alert type="success" :message="session('success')" dismissible="true" />
        @endif

        @if(session('error'))
            <x-ui.alert type="danger" :message="session('error')" dismissible="true" />
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-[#FEE2E2] border border-[#FECACA] space-y-1">
                <div class="flex items-center gap-2 font-bold text-xs text-[#B91C1C]">
                    <x-ui.icon name="alert-triangle" class="w-4 h-4 shrink-0" />
                    <span>Terjadi kesalahan pada data yang dikirim:</span>
                </div>
                <ul class="text-xs font-medium text-[#B91C1C] list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Grid Layout: Left Detail & Right Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Initial Issue & Conversation (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Initial Issue Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                    <div class="border-b border-[#DEDAD1] pb-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-[#6B6B6B] block">Uraian Pengaduan Awal</span>
                        <h2 class="text-lg sm:text-xl font-bold text-[#121417] mt-1">
                            {{ $complaint->title }}
                        </h2>
                    </div>

                    <div class="text-xs sm:text-sm text-[#121417] leading-relaxed whitespace-pre-line bg-[#F9F8F5] p-4 sm:p-5 rounded-xl border border-[#DEDAD1]">
                        {{ $complaint->description }}
                    </div>

                    <!-- Supporting Photo Attachment (Private endpoint) -->
                    @if($complaint->photo_url)
                        <div class="pt-2">
                            <h4 class="text-xs font-bold text-[#121417] mb-2 flex items-center gap-1.5">
                                <x-ui.icon name="image" class="w-4 h-4 text-[#6B6B6B]" />
                                <span>Foto Bukti dari Penyewa</span>
                            </h4>

                            <div class="inline-block rounded-xl border border-[#DEDAD1] p-2 bg-[#F9F8F5] shadow-[0_1px_2px_rgba(18,20,23,0.02)]">
                                <img
                                    src="{{ route('complaints.photo', $complaint) }}"
                                    alt="Foto bukti pengaduan {{ $complaint->ticket_number }}"
                                    class="max-h-80 max-w-full rounded-lg border border-[#DEDAD1] object-contain bg-white"
                                    loading="lazy"
                                />
                                <div class="pt-2 px-1 flex items-center justify-between gap-4">
                                    <span class="text-[11px] text-[#6B6B6B]">Lampiran privat berizin</span>
                                    <a
                                        href="{{ route('complaints.photo', ['complaint' => $complaint, 'download' => 1]) }}"
                                        class="text-xs font-semibold text-[#15803D] hover:underline inline-flex items-center gap-1"
                                    >
                                        <x-ui.icon name="download" class="w-3.5 h-3.5" />
                                        <span>Unduh Foto</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Conversation History Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-4">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="message-square" class="w-5 h-5 text-[#121417]" />
                            <h3 class="text-base font-bold text-[#121417]">
                                Riwayat Percakapan
                            </h3>
                            <span class="px-2 py-0.5 rounded-full bg-[#F4F2EC] border border-[#DEDAD1] text-xs font-semibold text-[#6B6B6B]">
                                {{ $complaint->replies->count() }} Balasan
                            </span>
                        </div>
                    </div>

                    @if($complaint->replies->isEmpty())
                        <div class="py-8 text-center bg-[#F9F8F5] rounded-xl border border-dashed border-[#DEDAD1] p-6 space-y-2">
                            <x-ui.icon name="message-square" class="w-8 h-8 text-[#A5A096] mx-auto" />
                            <h4 class="text-sm font-bold text-[#121417]">Belum Ada Tanggapan</h4>
                            <p class="text-xs text-[#6B6B6B] max-w-md mx-auto">
                                Belum ada pesan tanggapan untuk tiket ini. Kirim pesan di bawah untuk memberi instruksi atau kabar terbaru ke penyewa.
                            </p>
                        </div>
                    @else
                        <div class="space-y-3.5">
                            @foreach($complaint->replies as $reply)
                                @php
                                    $isOwnerReply = $reply->isFromOwner();
                                @endphp

                                <div class="p-4 rounded-xl border {{ $isOwnerReply ? 'bg-[#E8F8D0]/30 border-[#C7F36B]/60 ml-2 sm:ml-6' : 'bg-[#F9F8F5] border-[#DEDAD1] mr-2 sm:mr-6' }}">
                                    <div class="flex items-center justify-between gap-3 mb-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full border border-[#DEDAD1] {{ $isOwnerReply ? 'bg-[#C7F36B] text-[#15803D]' : 'bg-[#EFF6FF] text-[#1D4ED8]' }} flex items-center justify-center font-bold text-xs">
                                                {{ strtoupper(substr($reply->user?->name ?? 'U', 0, 1)) }}
                                            </div>
                                            <div>
                                                <span class="font-bold text-xs text-[#121417]">
                                                    {{ $reply->user?->name ?? 'Pengguna' }}
                                                </span>
                                                <span class="ml-1.5 px-2 py-0.2 rounded-full border text-[10px] font-semibold {{ $isOwnerReply ? 'bg-[#E8F8D0] border-[#C7F36B]/60 text-[#15803D]' : 'bg-[#EFF6FF] border-[#BFDBFE] text-[#1D4ED8]' }}">
                                                    {{ $isOwnerReply ? 'Pemilik Kos' : 'Penyewa' }}
                                                </span>
                                            </div>
                                        </div>

                                        <span class="text-[11px] text-[#6B6B6B]">
                                            {{ $reply->created_at->format('d/m/Y H:i') }}
                                        </span>
                                    </div>

                                    <div class="text-xs sm:text-sm text-[#121417] leading-relaxed whitespace-pre-line">
                                        {{ $reply->message }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Reply Input Section or Closed Banner -->
                    <div class="pt-4 border-t border-[#DEDAD1]">
                        @if($complaint->isClosed())
                            <div class="p-5 rounded-xl bg-[#F4F2EC] border border-[#DEDAD1] text-center space-y-1">
                                <h4 class="text-sm font-bold text-[#121417]">Tiket Ditutup</h4>
                                <p class="text-xs text-[#6B6B6B]">
                                    Tiket pengaduan ini telah ditutup permanen. Percakapan baru dinonaktifkan.
                                    Jika masalah berulang, Anda dapat memperbarui statusnya kembali ke "Diproses".
                                </p>
                            </div>
                        @else
                            <form method="POST" action="{{ route('owner.complaints.replies.store', $complaint) }}" class="space-y-3">
                                @csrf
                                <label for="reply_message" class="block text-xs font-semibold text-[#121417]">
                                    Kirim Balasan ke Penyewa
                                </label>
                                <textarea
                                    id="reply_message"
                                    name="message"
                                    rows="4"
                                    placeholder="Tulis tanggapan, jadwal kunjungan teknisi, atau konfirmasi perbaikan..."
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs sm:text-sm font-medium text-[#121417] placeholder:text-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                                >{{ old('message') }}</textarea>

                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-[11px] text-[#6B6B6B]">
                                        Pesan akan langsung terbaca oleh penyewa di portal mereka.
                                    </span>
                                    <x-ui.button
                                        type="submit"
                                        variant="primary"
                                        size="sm"
                                        class="shrink-0 gap-1.5"
                                    >
                                        <x-ui.icon name="send" class="w-4 h-4" />
                                        <span>Kirim Tanggapan</span>
                                    </x-ui.button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column: Status & Handling Sidebar Info (1 col) -->
            <div class="space-y-6">
                <!-- Status & Delegation Info Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
                    <div class="border-b border-[#DEDAD1] pb-3 flex items-center justify-between">
                        <h3 class="text-xs font-bold text-[#121417] uppercase tracking-wider">
                            Status & Penanganan
                        </h3>
                        <button
                            type="button"
                            x-on:click="showStatusModal = true"
                            class="text-xs font-semibold text-[#15803D] hover:underline cursor-pointer"
                        >
                            Ubah
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <!-- Status -->
                        <div>
                            <span class="text-[11px] text-[#6B6B6B] block">Status Tiket</span>
                            <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusBadgeClass }}">
                                ● {{ $complaint->statusLabel() }}
                            </span>
                        </div>

                        <!-- Teknisi / Handled By -->
                        <div>
                            <span class="text-[11px] text-[#6B6B6B] block">Penanggung Jawab / Teknisi</span>
                            <p class="font-bold text-sm text-[#121417] mt-0.5">
                                {{ $complaint->handled_by ?: 'Belum ditugaskan' }}
                            </p>
                        </div>

                        <!-- Estimasi Selesai -->
                        <div>
                            <span class="text-[11px] text-[#6B6B6B] block">Estimasi Selesai</span>
                            <p class="font-bold text-sm text-[#121417] mt-0.5">
                                {{ $complaint->estimated_completion ? $complaint->estimated_completion->format('d/m/Y') : 'Belum ditentukan' }}
                            </p>
                        </div>

                        <!-- Resolusi -->
                        @if($complaint->resolved_at)
                            <div>
                                <span class="text-[11px] text-[#15803D] font-semibold block">Waktu Selesai</span>
                                <p class="font-bold text-xs text-[#15803D] mt-0.5">
                                    {{ $complaint->resolved_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        @endif

                        <!-- Closed At -->
                        @if($complaint->closed_at)
                            <div>
                                <span class="text-[11px] text-[#6B6B6B] block">Waktu Ditutup</span>
                                <p class="font-bold text-xs text-[#6B6B6B] mt-0.5">
                                    {{ $complaint->closed_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="pt-2">
                        <button
                            type="button"
                            x-on:click="showStatusModal = true"
                            class="w-full py-2.5 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] font-semibold text-xs text-[#121417] hover:bg-[#EAE6DF] transition-colors cursor-pointer flex items-center justify-center gap-1.5"
                        >
                            <x-ui.icon name="settings" class="w-4 h-4 text-[#6B6B6B]" />
                            <span>Perbarui Status & Teknisi</span>
                        </button>
                    </div>
                </div>

                <!-- Tenant & Room Summary Card -->
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-3">
                    <h3 class="text-xs font-bold text-[#121417] uppercase tracking-wider border-b border-[#DEDAD1] pb-3">
                        Informasi Penyewa
                    </h3>

                    <div class="space-y-2 text-xs">
                        <div>
                            <span class="text-[11px] text-[#6B6B6B] block">Nama Penyewa</span>
                            <p class="font-bold text-sm text-[#121417]">
                                {{ $complaint->user?->name ?? 'Penyewa' }}
                            </p>
                        </div>

                        <div>
                            <span class="text-[11px] text-[#6B6B6B] block">Email / Telepon</span>
                            <p class="font-medium text-xs text-[#121417]">
                                {{ $complaint->user?->email ?? '-' }}
                                @if($complaint->user?->phone)
                                    • {{ $complaint->user->phone }}
                                @endif
                            </p>
                        </div>

                        <div>
                            <span class="text-[11px] text-[#6B6B6B] block">Properti</span>
                            <p class="font-semibold text-xs text-[#121417]">
                                {{ $complaint->property?->name ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <span class="text-[11px] text-[#6B6B6B] block">Nomor Kamar</span>
                            <p class="font-bold text-xs text-[#121417]">
                                Kamar {{ $complaint->room?->room_number ?? '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Status Modal (Alpine.js) -->
        <div
            x-show="showStatusModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
        >
            <div
                x-show="showStatusModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-on:click.outside="showStatusModal = false"
                class="w-full max-w-lg bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-xl space-y-5"
            >
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                    <div>
                        <h3 class="text-base font-bold text-[#121417]">Perbarui Status Tiket</h3>
                        <p class="text-xs text-[#6B6B6B] mt-0.5">Tiket: <span class="font-mono">{{ $complaint->ticket_number }}</span></p>
                    </div>
                    <button
                        type="button"
                        x-on:click="showStatusModal = false"
                        class="w-7 h-7 rounded-lg border border-[#DEDAD1] bg-white flex items-center justify-center text-xs font-semibold text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F4F2EC] cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <!-- Update Form -->
                <form method="POST" action="{{ route('owner.complaints.status.update', $complaint) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <!-- Status Selection -->
                    <div>
                        <label for="modal_status" class="block text-xs font-semibold text-[#121417] mb-1">
                            Pilih Status Baru <span class="text-[#B91C1C]">*</span>
                        </label>
                        <select
                            id="modal_status"
                            name="status"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        >
                            <option value="diproses" @selected(old('status', $complaint->status) === 'diproses')>
                                Sedang Ditangani (Diproses)
                            </option>
                            <option value="menunggu_tanggapan" @selected(old('status', $complaint->status) === 'menunggu_tanggapan')>
                                Menunggu Tanggapan Penyewa
                            </option>
                            <option value="selesai" @selected(old('status', $complaint->status) === 'selesai')>
                                Selesai Diperbaiki
                            </option>
                            <option value="ditutup" @selected(old('status', $complaint->status) === 'ditutup')>
                                Tutup Tiket
                            </option>
                        </select>
                        <p class="text-[11px] text-[#6B6B6B] mt-1">
                            Menandai 'Selesai' akan mencatat waktu penyelesaian resmi.
                        </p>
                    </div>

                    <!-- Handled By (Technician / In-Charge) -->
                    <div>
                        <label for="modal_handled_by" class="block text-xs font-semibold text-[#121417] mb-1">
                            Nama Teknisi / Penanggung Jawab
                        </label>
                        <input
                            type="text"
                            id="modal_handled_by"
                            name="handled_by"
                            value="{{ old('handled_by', $complaint->handled_by) }}"
                            placeholder="Contoh: Pak Joko (Teknisi Listrik)"
                            maxlength="100"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417]"
                        />
                    </div>

                    <!-- Estimated Completion -->
                    <div>
                        <label for="modal_estimated_completion" class="block text-xs font-semibold text-[#121417] mb-1">
                            Estimasi Tanggal Penyelesaian
                        </label>
                        <input
                            type="date"
                            id="modal_estimated_completion"
                            name="estimated_completion"
                            value="{{ old('estimated_completion', $complaint->estimated_completion?->format('Y-m-d')) }}"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#DEDAD1] text-xs font-medium text-[#121417] focus:outline-hidden focus:border-[#121417] focus:ring-1 focus:ring-[#121417] cursor-pointer"
                        />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#DEDAD1]">
                        <x-ui.button
                            type="button"
                            x-on:click="showStatusModal = false"
                            variant="outline"
                            size="sm"
                        >
                            Batal
                        </x-ui.button>
                        <x-ui.button
                            type="submit"
                            variant="primary"
                            size="sm"
                        >
                            Simpan Perubahan
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.owner>
