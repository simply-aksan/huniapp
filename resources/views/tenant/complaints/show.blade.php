<x-layouts.tenant title="Pengaduan {{ $complaint->ticket_number }} — HUNI" activeNav="complaints">
    <div class="space-y-6">
        <!-- Header Banner Card -->
        <div class="relative bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 text-xs font-medium text-[#5E6470] mb-2">
                        <a href="{{ route('tenant.dashboard') }}" class="hover:text-[#121417] transition-colors">Beranda</a>
                        <span>›</span>
                        <a href="{{ route('tenant.complaints.index') }}" class="hover:text-[#121417] transition-colors">Komplain & Pengaduan</a>
                        <span>›</span>
                        <span class="text-[#121417] font-semibold">{{ $complaint->ticket_number }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl sm:text-3xl font-bold text-[#121417] font-mono tracking-tight leading-none">
                            {{ $complaint->ticket_number }}
                        </h1>

                        @php
                            $statusBadgeVariant = match($complaint->status) {
                                \App\Enums\ComplaintStatus::Baru->value => 'amber',
                                \App\Enums\ComplaintStatus::Diproses->value => 'blue',
                                \App\Enums\ComplaintStatus::MenungguTanggapan->value => 'purple',
                                \App\Enums\ComplaintStatus::Selesai->value => 'emerald',
                                \App\Enums\ComplaintStatus::Ditutup->value => 'neutral',
                                default => 'neutral',
                            };

                            $priorityBadgeVariant = match($complaint->priority) {
                                \App\Enums\ComplaintPriority::Darurat->value => 'rose',
                                \App\Enums\ComplaintPriority::Tinggi->value => 'amber',
                                \App\Enums\ComplaintPriority::Sedang->value => 'blue',
                                \App\Enums\ComplaintPriority::Rendah->value => 'neutral',
                                default => 'neutral',
                            };
                        @endphp

                        <x-ui.badge :variant="$statusBadgeVariant">
                            {{ $complaint->statusLabel() }}
                        </x-ui.badge>

                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium border border-[#DEDAD1] bg-[#F9F8F5] text-[#5E6470]">
                            {{ $complaint->categoryLabel() }}
                        </span>

                        <x-ui.badge :variant="$priorityBadgeVariant">
                            {{ $complaint->priorityLabel() }}
                        </x-ui.badge>
                    </div>

                    <p class="text-xs text-[#5E6470] mt-3 font-mono">
                        <span class="text-[#121417] font-semibold font-sans">{{ $complaint->property?->name ?? 'Kos' }}</span>
                        — Kamar <span class="text-[#121417] font-semibold">{{ $complaint->room?->room_number ?? '-' }}</span>
                        • Diajukan: <span class="text-[#121417] font-semibold">{{ $complaint->created_at->format('d/m/Y H:i') }}</span>
                        @if($complaint->resolved_at)
                            • Diselesaikan: <span class="text-[#15803D] font-semibold">{{ $complaint->resolved_at->format('d/m/Y H:i') }}</span>
                        @endif
                        @if($complaint->closed_at)
                            • Ditutup: <span class="text-[#5E6470] font-semibold">{{ $complaint->closed_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </p>
                </div>

                <!-- Back Action -->
                <div>
                    <a
                        href="{{ route('tenant.complaints.index') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-[#DEDAD1] bg-white text-xs font-semibold text-[#121417] hover:bg-[#F4F1EA] transition-all cursor-pointer shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                    >
                        <x-ui.icon name="arrow-left" class="w-3.5 h-3.5 text-[#5E6470]" />
                        <span>Kembali ke Daftar</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <x-ui.alert type="success">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if(session('error'))
            <x-ui.alert type="error">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <!-- Complaint Initial Issue Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-4">
            <div class="border-b border-[#DEDAD1] pb-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-[#5E6470] block">Keluhan Awal</span>
                <h2 class="text-xl font-bold text-[#121417] mt-0.5">
                    {{ $complaint->title }}
                </h2>
            </div>

            <div class="text-xs text-[#121417] leading-relaxed whitespace-pre-line bg-[#F9F8F5] p-4 rounded-xl border border-[#DEDAD1]">
                {{ $complaint->description }}
            </div>

            <!-- Supporting Photo Attachment -->
            @if($complaint->photo_url)
                <div class="pt-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-[#5E6470] mb-2 flex items-center gap-1.5">
                        <x-ui.icon name="photo" class="w-3.5 h-3.5 text-[#5E6470]" />
                        <span>Foto Bukti Pendukung</span>
                    </h4>

                    <div class="inline-block rounded-xl border border-[#DEDAD1] p-2 bg-white shadow-[0_1px_2px_rgba(18,20,23,0.04)]">
                        <img
                            src="{{ route('tenant.complaints.photo', $complaint) }}"
                            alt="Foto bukti pengaduan {{ $complaint->ticket_number }}"
                            class="max-h-72 max-w-full rounded-lg border border-[#DEDAD1] object-contain bg-[#F9F8F5]"
                            loading="lazy"
                        />
                        <div class="pt-2 px-1 flex items-center justify-between gap-4">
                            <span class="text-[11px] text-[#5E6470]">Foto disimpan secara privat</span>
                            <a
                                href="{{ route('tenant.complaints.photo', ['complaint' => $complaint, 'download' => 1]) }}"
                                class="text-xs font-semibold text-[#121417] hover:underline inline-flex items-center gap-1"
                            >
                                <x-ui.icon name="arrow-down-tray" class="w-3.5 h-3.5 text-[#5E6470]" />
                                <span>Unduh Foto</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Conversation Timeline Card -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-6">
            <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                <div class="flex items-center gap-2">
                    <x-ui.icon name="chat-bubble-left-right" class="w-4 h-4 text-[#5E6470]" />
                    <h3 class="text-sm font-bold text-[#121417] uppercase tracking-wide">
                        Riwayat Percakapan
                    </h3>
                    <span class="px-2 py-0.5 rounded-full bg-[#F4F1EA] text-[#5E6470] font-mono text-xs font-semibold">
                        {{ $complaint->replies->count() }} Balasan
                    </span>
                </div>
            </div>

            @if($complaint->replies->isEmpty())
                <div class="py-8 text-center bg-[#F9F8F5] rounded-xl border border-dashed border-[#DEDAD1] p-6 space-y-1.5">
                    <x-ui.icon name="clock" class="w-6 h-6 text-[#5E6470] mx-auto" />
                    <h4 class="text-xs font-semibold text-[#121417]">Belum Ada Tanggapan</h4>
                    <p class="text-xs text-[#5E6470] max-w-md mx-auto">
                        Pengaduan Anda telah tercatat dan sedang menunggu respons dari pemilik kos. Riwayat percakapan akan muncul di sini.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($complaint->replies as $reply)
                        @php
                            $isTenantAuthor = $reply->isFromTenant();
                            $isMyReply = $reply->user_id === auth()->id();
                        @endphp

                        <div class="flex flex-col {{ $isMyReply ? 'items-end' : 'items-start' }}">
                            <div class="max-w-2xl w-full sm:w-auto {{ $isMyReply ? 'ml-auto' : 'mr-auto' }}">
                                <!-- Author & Timestamp Header -->
                                <div class="flex items-center gap-2 mb-1 px-1 {{ $isMyReply ? 'justify-end' : 'justify-start' }}">
                                    @if($isMyReply)
                                        <span class="px-2 py-0.5 rounded-md bg-[#E8F5E9] text-[#15803D] text-[10px] font-semibold border border-[#C8E6C9]">
                                            Anda (Penyewa)
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md bg-[#EFF6FF] text-blue-700 text-[10px] font-semibold border border-[#BFDBFE]">
                                            Pemilik Kos
                                        </span>
                                        <span class="text-xs font-semibold text-[#121417]">{{ $reply->user?->name ?? 'Pemilik Kos' }}</span>
                                    @endif

                                    <span class="text-[11px] text-[#5E6470] font-mono">
                                        {{ $reply->created_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>

                                <!-- Message Bubble -->
                                <div class="p-4 rounded-xl border {{ $isMyReply ? 'bg-[#F9F8F5] border-[#DEDAD1] text-[#121417]' : 'bg-white border-[#DEDAD1] text-[#121417] shadow-[0_1px_2px_rgba(18,20,23,0.04)]' }}">
                                    <div class="text-xs leading-relaxed whitespace-pre-line">
                                        {{ $reply->message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Reply Form or Closed State Notice -->
            <div class="pt-4 border-t border-[#DEDAD1]">
                @if($complaint->isResolved() && ! $complaint->isClosed())
                    <!-- Resolved Confirmation Card -->
                    <div class="mb-5 p-4 rounded-xl bg-[#E8F5E9] border border-[#C8E6C9] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <x-ui.icon name="check-circle" class="w-6 h-6 text-[#15803D] shrink-0" />
                            <div>
                                <h4 class="text-xs font-bold text-green-900">
                                    Perbaikan Telah Ditandai Selesai oleh Pemilik Kos
                                </h4>
                                <p class="text-[11px] text-green-800 mt-0.5">
                                    Jika keluhan sudah teratasi dengan baik, Anda dapat mengonfirmasi penyelesaian untuk menutup tiket secara permanen.
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('tenant.complaints.close', $complaint) }}" method="POST" class="shrink-0">
                            @csrf
                            <button
                                type="submit"
                                onclick="return confirm('Konfirmasi bahwa keluhan telah terselesaikan dan tutup tiket ini?')"
                                class="w-full sm:w-auto px-4 py-2 rounded-lg bg-[#15803D] hover:bg-[#166534] text-white font-semibold text-xs shadow-[0_1px_2px_rgba(18,20,23,0.08)] transition-all cursor-pointer flex items-center justify-center gap-1.5"
                            >
                                <x-ui.icon name="check" class="w-3.5 h-3.5 text-white" />
                                <span>Konfirmasi & Tutup Tiket</span>
                            </button>
                        </form>
                    </div>
                @endif

                @if($complaint->isClosed())
                    <!-- Permanently Closed Banner -->
                    <div class="p-4 rounded-xl bg-[#F4F1EA] border border-[#DEDAD1] flex items-center gap-3">
                        <x-ui.icon name="lock-closed" class="w-5 h-5 text-[#5E6470] shrink-0" />
                        <div>
                            <h4 class="text-xs font-bold text-[#121417]">
                                Tiket Pengaduan Ini Telah Ditutup Permanen
                            </h4>
                            <p class="text-[11px] text-[#5E6470] mt-0.5">
                                Tiket telah ditutup pada {{ $complaint->closed_at?->format('d/m/Y H:i') ?? '-' }}. Anda tidak dapat mengirimkan balasan baru pada tiket ini. Jika mengalami kendala baru, silakan buat tiket pengaduan baru.
                            </p>
                        </div>
                    </div>
                @else
                    <!-- New Reply Form -->
                    <form
                        action="{{ route('tenant.complaints.replies.store', $complaint) }}"
                        method="POST"
                        class="space-y-3"
                    >
                        @csrf

                        <div>
                            <label for="reply_message" class="block text-xs font-semibold uppercase tracking-wider text-[#121417] mb-1.5">
                                Tulis Balasan / Tanggapan Anda:
                            </label>
                            <textarea
                                name="message"
                                id="reply_message"
                                rows="3"
                                placeholder="Tulis informasi tambahan atau respon terhadap pemilik kos..."
                                class="w-full p-3.5 rounded-lg border border-[#DEDAD1] bg-[#F9F8F5] text-xs text-[#121417] placeholder-[#5E6470] focus:outline-none focus:bg-white focus:border-[#121417] transition-all"
                                required
                                minlength="2"
                                maxlength="3000"
                            >{{ old('message') }}</textarea>
                            @error('message')
                                <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <p class="text-[11px] text-[#5E6470]">
                                @if($complaint->status === \App\Enums\ComplaintStatus::MenungguTanggapan->value)
                                    Tiket sedang menunggu tanggapan Anda. Mengirim balasan akan otomatis mengembalikan status tiket ke <strong>Dalam Penanganan</strong>.
                                @else
                                    Tanggapan Anda akan langsung terbaca oleh pemilik kos.
                                @endif
                            </p>

                            <button
                                type="submit"
                                class="px-4 py-2 rounded-lg bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs shadow-[0_1px_2px_rgba(18,20,23,0.08)] transition-all cursor-pointer inline-flex items-center justify-center gap-1.5 shrink-0"
                            >
                                <x-ui.icon name="paper-airplane" class="w-3.5 h-3.5 text-[#C7F36B]" />
                                <span>Kirim Tanggapan</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-layouts.tenant>
