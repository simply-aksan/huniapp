<x-layouts.tenant title="Tanya HUNI AI — Asisten Kos Penghuni" activeNav="chat">
    @if (! $hasActiveContract)
        <!-- Empty / Unauthorized State: No Active Contract -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-8 sm:p-12 shadow-[0_1px_3px_rgba(18,20,23,0.04)] text-center max-w-xl mx-auto my-12 space-y-5">
            <div class="w-12 h-12 bg-[#FEF3C7] border border-[#FDE68A] rounded-xl flex items-center justify-center mx-auto text-amber-700">
                <x-ui.icon name="lock-closed" class="w-6 h-6" />
            </div>
            <div class="space-y-2">
                <h2 class="text-xl font-bold text-[#121417] tracking-tight">
                    Belum Memiliki Kontrak Aktif
                </h2>
                <p class="text-xs text-[#5E6470] leading-relaxed">
                    Fitur <strong>Tanya HUNI AI</strong> dikhususkan untuk menjawab aturan dan panduan kos bagi penghuni yang memiliki kontrak sewa aktif. Saat ini akun Anda belum terhubung dengan kontrak aktif mana pun.
                </p>
            </div>
            <div class="pt-2">
                <x-ui.button href="{{ route('tenant.dashboard') }}" variant="primary" size="sm">
                    <x-ui.icon name="arrow-left" class="w-3.5 h-3.5" />
                    <span>Kembali ke Beranda</span>
                </x-ui.button>
            </div>
        </div>
    @else
        <div
            x-data="tenantChat({
                askUrl: '{{ route('tenant.chat.ask') }}',
                csrfToken: '{{ csrf_token() }}',
                propertyId: {{ $selectedProperty->id }},
                initialLogs: {{ Js::from($chatLogs) }}
            })"
            class="space-y-6"
        >
            <!-- Top Hero Banner -->
            <div class="relative bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-1">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#E8F5E9] border border-[#C8E6C9] rounded-full text-xs font-semibold text-[#15803D] mb-1">
                            <x-ui.icon name="sparkles" class="w-3.5 h-3.5 text-[#15803D]" />
                            <span>ASISTEN KOS PENGHUNI</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-[#121417] tracking-tight">
                            Tanya HUNI AI
                        </h1>
                        <p class="text-xs sm:text-sm text-[#5E6470]">
                            Jawaban resmi berdasarkan tata tertib dan FAQ kos <strong>{{ $selectedProperty->name }}</strong>.
                        </p>
                    </div>

                    <!-- Property Switcher (if tenant has multiple active properties) & Clear History -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        @if ($activeContracts->count() > 1)
                            <form method="GET" action="{{ route('tenant.chat.index') }}" class="flex items-center gap-2">
                                <label for="property-select" class="text-xs font-semibold text-[#121417]">Pilih Kos:</label>
                                <select
                                    id="property-select"
                                    name="property_id"
                                    onchange="this.form.submit()"
                                    class="text-xs font-medium bg-[#F9F8F5] border border-[#DEDAD1] rounded-lg px-3 py-1.5 focus:outline-none focus:border-[#121417] transition-all cursor-pointer"
                                >
                                    @foreach ($activeContracts as $contract)
                                        <option value="{{ $contract->property_id }}" @selected($contract->property_id === $selectedProperty->id)>
                                            {{ $contract->property->name }} (Kamar {{ $contract->room?->room_number ?? '-' }})
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <div class="inline-flex items-center gap-1.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-lg px-3 py-1.5 text-xs font-semibold text-[#121417]">
                                <x-ui.icon name="home" class="w-3.5 h-3.5 text-[#5E6470]" />
                                <span>{{ $selectedProperty->name }}</span>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('tenant.chat.clear') }}" onsubmit="return confirm('Hapus seluruh riwayat percakapan pada kos ini?');">
                            @csrf
                            <input type="hidden" name="property_id" value="{{ $selectedProperty->id }}">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#5E6470] hover:text-[#B91C1C] hover:border-[#FECACA] bg-white border border-[#DEDAD1] rounded-lg px-3 py-1.5 transition-all cursor-pointer shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                            >
                                <x-ui.icon name="trash" class="w-3.5 h-3.5" />
                                <span>Bersihkan Chat</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Chat Window Card -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl shadow-[0_4px_16px_rgba(18,20,23,0.04)] flex flex-col h-[calc(100vh-22rem)] min-h-[500px] max-h-[750px] overflow-hidden">
                <!-- Chat Window Subheader -->
                <div class="bg-[#F9F8F5] border-b border-[#DEDAD1] px-5 py-2.5 flex items-center justify-between text-xs font-medium text-[#121417]">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#15803D]"></span>
                        <span class="text-xs font-semibold text-[#121417]">Asisten Aktif — Menjawab khusus dokumen resmi kos</span>
                    </div>
                    <div class="text-[11px] text-[#5E6470] hidden sm:block">
                        Skor relevansi adalah metrik kemiripan dokumen, bukan probabilitas kebenaran
                    </div>
                </div>

                <!-- Messages Container -->
                <div
                    x-ref="messagesContainer"
                    class="flex-1 p-5 sm:p-6 overflow-y-auto space-y-4 bg-[#FAF9F6]"
                >
                    <!-- Welcome Initial Message -->
                    <div class="flex items-start gap-3 max-w-2xl">
                        <div class="w-8 h-8 rounded-lg bg-[#C7F36B] border border-[#A6E22E] flex items-center justify-center shrink-0">
                            <x-ui.icon name="sparkles" class="w-4 h-4 text-[#121417]" />
                        </div>
                        <div class="bg-white border border-[#DEDAD1] rounded-2xl rounded-tl-xs p-4 shadow-[0_1px_3px_rgba(18,20,23,0.04)] text-xs sm:text-sm text-[#121417] space-y-2">
                            <p class="font-bold text-[#121417]">Halo, {{ $tenant->name }}!</p>
                            <p class="text-xs text-[#5E6470] leading-relaxed">
                                Saya asisten virtual kos <strong>{{ $selectedProperty->name }}</strong>. Anda dapat bertanya tentang aturan jam malam, kebijakan tamu, fasilitas kamar, parkir, hingga prosedur sewa.
                            </p>
                            <div class="bg-[#EFF6FF] border border-[#BFDBFE] rounded-lg p-2.5 text-xs text-blue-950 flex items-start gap-2">
                                <x-ui.icon name="information-circle" class="w-4 h-4 text-blue-700 shrink-0 mt-0.5" />
                                <span class="text-[11px] leading-relaxed">Saya hanya menjawab hal-hal yang tercantum pada aturan dan FAQ resmi kos. Jika topik belum diatur, saya akan menyarankan Anda menghubungi pemilik kos.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Chat History Loop -->
                    <template x-for="(msg, index) in messages" :key="index">
                        <div>
                            <!-- User Message -->
                            <template x-if="msg.role === 'user'">
                                <div class="flex justify-end items-start gap-2.5">
                                    <div class="bg-[#121417] text-white rounded-2xl rounded-tr-xs p-3.5 max-w-xl text-xs sm:text-sm font-normal leading-relaxed shadow-[0_1px_2px_rgba(18,20,23,0.08)]">
                                        <p x-text="msg.text"></p>
                                    </div>
                                    <div class="w-8 h-8 rounded-lg bg-[#F4F1EA] border border-[#DEDAD1] flex items-center justify-center shrink-0 text-xs font-bold text-[#121417]">
                                        {{ substr($tenant->name, 0, 1) }}
                                    </div>
                                </div>
                            </template>

                            <!-- AI Assistant Message -->
                            <template x-if="msg.role === 'assistant'">
                                <div class="flex items-start gap-3 max-w-2xl mt-3">
                                    <div class="w-8 h-8 rounded-lg bg-[#E8F5E9] border border-[#C8E6C9] flex items-center justify-center shrink-0">
                                        <x-ui.icon name="sparkles" class="w-4 h-4 text-[#15803D]" />
                                    </div>
                                    <div class="bg-white border border-[#DEDAD1] rounded-2xl rounded-tl-xs p-4 shadow-[0_1px_3px_rgba(18,20,23,0.04)] text-xs sm:text-sm text-[#121417] space-y-3">
                                        <!-- Answer Text -->
                                        <div class="whitespace-pre-line leading-relaxed text-xs sm:text-sm text-[#121417]" x-text="msg.text"></div>

                                        <!-- Fallback Notice (if insufficient evidence) -->
                                        <template x-if="msg.is_fallback">
                                            <div class="bg-[#FEF3C7] border border-[#FDE68A] rounded-xl p-3 text-xs space-y-1">
                                                <p class="font-bold text-amber-900 flex items-center gap-1.5">
                                                    <x-ui.icon name="exclamation-triangle" class="w-3.5 h-3.5 text-amber-700" />
                                                    <span>Informasi Tambahan</span>
                                                </p>
                                                <p class="text-amber-800 text-[11px] leading-relaxed">
                                                    Topik ini belum diatur dalam aturan tertulis. Anda disarankan menghubungi Pemilik kos untuk klarifikasi langsung.
                                                </p>
                                            </div>
                                        </template>

                                        <!-- Sources Accordion / Pills -->
                                        <template x-if="msg.sources && msg.sources.length > 0">
                                            <div class="pt-2 border-t border-[#DEDAD1] space-y-2">
                                                <div class="text-[10px] font-bold uppercase tracking-wider text-[#5E6470]">
                                                    Dokumen Sumber Resmi Digunakan:
                                                </div>
                                                <div class="space-y-1.5">
                                                    <template x-for="(source, sIdx) in msg.sources" :key="sIdx">
                                                        <div class="bg-[#F9F8F5] border border-[#DEDAD1] rounded-lg p-2.5 text-xs space-y-1">
                                                            <div class="flex items-center justify-between font-semibold text-[#121417]">
                                                                <span class="flex items-center gap-1.5">
                                                                    <span class="px-1.5 py-0.5 rounded text-[10px] uppercase font-semibold"
                                                                          :class="source.source_type === 'rule' ? 'bg-[#FEE2E2] text-[#B91C1C]' : 'bg-[#EFF6FF] text-blue-700'"
                                                                          x-text="source.source_type === 'rule' ? 'Aturan' : 'FAQ'"></span>
                                                                    <span x-text="source.title" class="text-xs"></span>
                                                                </span>
                                                                <span class="text-[10px] text-[#5E6470] font-mono">
                                                                    Kemiripan: <span x-text="source.relevance_score"></span>
                                                                </span>
                                                            </div>
                                                            <p class="text-[#5E6470] text-[11px] italic" x-text="source.content"></p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Loading Bubble -->
                    <div x-show="isLoading" x-cloak class="flex items-start gap-3 max-w-md mt-3">
                        <div class="w-8 h-8 rounded-lg bg-[#E8F5E9] border border-[#C8E6C9] flex items-center justify-center shrink-0 animate-pulse">
                            <x-ui.icon name="sparkles" class="w-4 h-4 text-[#15803D]" />
                        </div>
                        <div class="bg-white border border-[#DEDAD1] rounded-2xl rounded-tl-xs px-4 py-3 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#121417] animate-pulse"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-[#121417] animate-pulse [animation-delay:0.2s]"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-[#121417] animate-pulse [animation-delay:0.4s]"></span>
                            <span class="text-xs text-[#5E6470] ml-1">Mencari dokumen resmi kos...</span>
                        </div>
                    </div>

                    <!-- Error Alert -->
                    <div x-show="errorMessage" x-cloak class="bg-[#FEE2E2] border border-[#FECACA] rounded-xl p-3 text-xs text-[#B91C1C] flex items-center justify-between">
                        <span x-text="errorMessage"></span>
                        <button type="button" @click="errorMessage = ''" class="font-bold text-sm leading-none">&times;</button>
                    </div>
                </div>

                <!-- Quick Suggestion Chips -->
                <div class="bg-white border-t border-[#DEDAD1] px-5 py-2.5 overflow-x-auto flex items-center gap-2 no-scrollbar">
                    <span class="text-[11px] font-semibold uppercase text-[#5E6470] shrink-0">Contoh Pertanyaan:</span>
                    <button
                        type="button"
                        @click="askPreset('Jam berapa pintu gerbang malam ditutup?')"
                        class="text-xs text-[#121417] bg-[#F9F8F5] hover:bg-[#F4F1EA] border border-[#DEDAD1] rounded-full px-3 py-1 whitespace-nowrap cursor-pointer inline-flex items-center gap-1.5 transition-all shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                    >
                        <x-ui.icon name="clock" class="w-3.5 h-3.5 text-[#5E6470]" />
                        <span>Jam Malam</span>
                    </button>
                    <button
                        type="button"
                        @click="askPreset('Apakah boleh membawa tamu menginap?')"
                        class="text-xs text-[#121417] bg-[#F9F8F5] hover:bg-[#F4F1EA] border border-[#DEDAD1] rounded-full px-3 py-1 whitespace-nowrap cursor-pointer inline-flex items-center gap-1.5 transition-all shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                    >
                        <x-ui.icon name="users" class="w-3.5 h-3.5 text-[#5E6470]" />
                        <span>Tamu Menginap</span>
                    </button>
                    <button
                        type="button"
                        @click="askPreset('Apakah ada biaya tambahan untuk alat elektronik?')"
                        class="text-xs text-[#121417] bg-[#F9F8F5] hover:bg-[#F4F1EA] border border-[#DEDAD1] rounded-full px-3 py-1 whitespace-nowrap cursor-pointer inline-flex items-center gap-1.5 transition-all shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                    >
                        <x-ui.icon name="bolt" class="w-3.5 h-3.5 text-[#5E6470]" />
                        <span>Listrik & Elektronik</span>
                    </button>
                    <button
                        type="button"
                        @click="askPreset('Bagaimana cara mengajukan komplain atau perbaikan kamar?')"
                        class="text-xs text-[#121417] bg-[#F9F8F5] hover:bg-[#F4F1EA] border border-[#DEDAD1] rounded-full px-3 py-1 whitespace-nowrap cursor-pointer inline-flex items-center gap-1.5 transition-all shadow-[0_1px_2px_rgba(18,20,23,0.04)]"
                    >
                        <x-ui.icon name="wrench-screwdriver" class="w-3.5 h-3.5 text-[#5E6470]" />
                        <span>Komplain Kamar</span>
                    </button>
                </div>

                <!-- Chat Input Form -->
                <div class="p-4 bg-white border-t border-[#DEDAD1]">
                    <form @submit.prevent="sendMessage()" class="flex items-center gap-2.5">
                        <input
                            type="text"
                            x-model="questionInput"
                            :disabled="isLoading"
                            placeholder="Ketik pertanyaanmu seputar aturan atau fasilitas kos..."
                            maxlength="500"
                            class="flex-1 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl px-4 py-2.5 text-xs sm:text-sm text-[#121417] placeholder-[#5E6470] focus:outline-none focus:bg-white focus:border-[#121417] transition-all disabled:opacity-50"
                        />
                        <button
                            type="submit"
                            x-bind:disabled="isLoading || !questionInput.trim()"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white text-xs font-semibold shadow-[0_1px_2px_rgba(18,20,23,0.08)] transition-all cursor-pointer disabled:opacity-50 shrink-0"
                        >
                            <span x-show="!isLoading">Kirim</span>
                            <span x-show="isLoading" x-cloak>Menjawab...</span>
                            <x-ui.icon name="paper-airplane" class="w-3.5 h-3.5 text-[#C7F36B]" />
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function tenantChat(config) {
                return {
                    askUrl: config.askUrl,
                    csrfToken: config.csrfToken,
                    propertyId: config.propertyId,
                    questionInput: '',
                    isLoading: false,
                    errorMessage: '',
                    messages: [],

                    init() {
                        // Load previous logs if available
                        if (config.initialLogs && Array.isArray(config.initialLogs)) {
                            config.initialLogs.forEach(log => {
                                this.messages.push({
                                    role: 'user',
                                    text: log.question
                                });
                                this.messages.push({
                                    role: 'assistant',
                                    text: log.answer,
                                    sources: log.sources || [],
                                    is_fallback: Boolean(log.is_fallback)
                                });
                            });
                        }
                        this.scrollToBottom();
                    },

                    askPreset(text) {
                        this.questionInput = text;
                        this.sendMessage();
                    },

                    async sendMessage() {
                        const q = this.questionInput.trim();
                        if (!q || this.isLoading) return;

                        this.questionInput = '';
                        this.errorMessage = '';
                        this.messages.push({
                            role: 'user',
                            text: q
                        });
                        this.isLoading = true;
                        this.scrollToBottom();

                        try {
                            const res = await fetch(this.askUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken
                                },
                                body: JSON.stringify({
                                    question: q,
                                    property_id: this.propertyId
                                })
                            });

                            const data = await res.json();

                            if (!res.ok || !data.success) {
                                this.errorMessage = data.message || 'Gagal memproses pertanyaan.';
                                this.messages.push({
                                    role: 'assistant',
                                    text: 'Maaf, terjadi kesalahan saat memproses pertanyaan Anda. Silakan coba kembali atau hubungi pemilik kos.',
                                    sources: [],
                                    is_fallback: true
                                });
                            } else {
                                this.messages.push({
                                    role: 'assistant',
                                    text: data.answer,
                                    sources: data.sources || [],
                                    is_fallback: Boolean(data.is_fallback)
                                });
                            }
                        } catch (err) {
                            this.errorMessage = 'Koneksi ke server terputus. Silakan coba lagi.';
                            this.messages.push({
                                role: 'assistant',
                                text: 'Maaf, terjadi gangguan jaringan saat menghubungi asisten AI.',
                                sources: [],
                                is_fallback: true
                            });
                        } finally {
                            this.isLoading = false;
                            this.scrollToBottom();
                        }
                    },

                    scrollToBottom() {
                        this.$nextTick(() => {
                            const el = this.$refs.messagesContainer;
                            if (el) {
                                el.scrollTop = el.scrollHeight;
                            }
                        });
                    }
                };
            }
        </script>
    @endif
</x-layouts.tenant>
