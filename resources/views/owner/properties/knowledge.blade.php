<x-layouts.owner title="Knowledge Base — {{ $property->name }}" activeNav="properties">
    <div
        x-data="{
            activeTab: 'rules',
            ruleModalOpen: false,
            ruleMode: 'create',
            ruleFormAction: '{{ route('owner.properties.knowledge.rules.store', $property) }}',
            ruleData: { id: null, title: '', category: 'umum', content: '', order: 0, is_active: true },

            faqModalOpen: false,
            faqMode: 'create',
            faqFormAction: '{{ route('owner.properties.knowledge.faqs.store', $property) }}',
            faqData: { id: null, question: '', answer: '', category: '', order: 0, is_active: true },

            deleteModalOpen: false,
            deleteFormAction: '',
            deleteItemTitle: '',

            openCreateRule() {
                this.ruleMode = 'create';
                this.ruleFormAction = '{{ route('owner.properties.knowledge.rules.store', $property) }}';
                this.ruleData = { id: null, title: '', category: 'umum', content: '', order: 0, is_active: true };
                this.ruleModalOpen = true;
            },
            openEditRule(rule) {
                this.ruleMode = 'edit';
                this.ruleFormAction = '{{ route('owner.properties.knowledge.rules.update', [$property, ':ruleId']) }}'.replace(':ruleId', rule.id);
                this.ruleData = {
                    id: rule.id,
                    title: rule.title,
                    category: rule.category || 'umum',
                    content: rule.content,
                    order: rule.order,
                    is_active: Boolean(rule.is_active)
                };
                this.ruleModalOpen = true;
            },
            openCreateFaq() {
                this.faqMode = 'create';
                this.faqFormAction = '{{ route('owner.properties.knowledge.faqs.store', $property) }}';
                this.faqData = { id: null, question: '', answer: '', category: '', order: 0, is_active: true };
                this.faqModalOpen = true;
            },
            openEditFaq(faq) {
                this.faqMode = 'edit';
                this.faqFormAction = '{{ route('owner.properties.knowledge.faqs.update', [$property, ':faqId']) }}'.replace(':faqId', faq.id);
                this.faqData = {
                    id: faq.id,
                    question: faq.question,
                    answer: faq.answer,
                    category: faq.category || '',
                    order: faq.order,
                    is_active: Boolean(faq.is_active)
                };
                this.faqModalOpen = true;
            },
            confirmDelete(actionUrl, title) {
                this.deleteFormAction = actionUrl;
                this.deleteItemTitle = title;
                this.deleteModalOpen = true;
            }
        }"
        class="space-y-6 max-w-7xl mx-auto"
    >
        <!-- Feedback Notifications -->
        @if(session('success'))
            <x-ui.alert type="success" :dismissible="true">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if(session('sync_warning'))
            <x-ui.alert type="warning" :dismissible="true">
                {{ session('sync_warning') }}
            </x-ui.alert>
        @endif

        @if(session('error'))
            <x-ui.alert type="danger" :dismissible="true">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <!-- Header Panel with Sync Integration Status -->
        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-7 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#6B6B6B] mb-1">
                    <a href="{{ route('owner.properties.index') }}" class="hover:text-[#121417] transition-colors">Properti &amp; Kos</a>
                    <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <a href="{{ route('owner.properties.show', $property) }}" class="hover:text-[#121417] transition-colors">{{ $property->name }}</a>
                    <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="font-medium text-[#121417]">Knowledge Base</span>
                </nav>

                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#121417]">
                        Knowledge Base: {{ $property->name }}
                    </h1>
                    
                    @if($syncStatus['status'] === 'synced')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#15803D] animate-pulse"></span>
                            Tersinkronisasi ke AI Service
                        </span>
                    @elseif($syncStatus['status'] === 'stale')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#D97706]"></span>
                            Ada Perubahan Belum Disinkronkan
                        </span>
                    @elseif($syncStatus['status'] === 'failed')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#FEE2E2] text-[#B91C1C] border border-[#FECACA]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#DC2626]"></span>
                            Sinkronisasi Terakhir Gagal
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#A5A096]"></span>
                            Belum Pernah Disinkronkan
                        </span>
                    @endif
                </div>

                <p class="text-xs text-[#6B6B6B] max-w-2xl leading-relaxed">
                    Kelola aturan tata tertib dan FAQ resmi kos. Dokumen aktif digunakan sebagai basis pengetahuan (<span class="font-mono text-[11px] text-[#121417]">knowledge corpus</span>) untuk asisten AI penghuni.
                </p>

                @if($syncStatus['synced_at'])
                    <div class="flex items-center gap-2 pt-1 text-[11px] text-[#6B6B6B]">
                        <svg class="w-3.5 h-3.5 text-[#A5A096]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Terakhir disinkronkan: <strong class="text-[#121417] font-semibold">{{ \Carbon\Carbon::parse($syncStatus['synced_at'])->translatedFormat('d M Y, H:i') }} WIB</strong> ({{ $syncStatus['total_active_documents'] }} dokumen aktif)</span>
                    </div>
                @endif
            </div>

            <!-- Manual Sync Button -->
            <form action="{{ route('owner.properties.knowledge.sync', $property) }}" method="POST" class="shrink-0">
                @csrf
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white text-xs font-semibold shadow-[0_1px_2px_rgba(18,20,23,0.08)] active:scale-[0.98] transition-all duration-150 cursor-pointer"
                >
                    <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Sinkronkan ke AI Service</span>
                </button>
            </form>
        </div>

        <!-- RAG Architecture Notice Banner -->
        <div class="p-4 rounded-xl bg-[#F0F7FF] border border-[#BAE6FD] flex items-start gap-3">
            <svg class="w-5 h-5 text-[#0284C7] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-xs text-[#0369A1] space-y-0.5">
                <p class="font-semibold text-[#0C4A6E]">Retrieval-Augmented Generation (RAG Architecture)</p>
                <p class="text-[#0369A1]/90 leading-relaxed">
                    Sinkronisasi mengirimkan snapshot lengkap dokumen aktif properti ini ke AI microservice. Vector embeddings diperbarui secara otomatis untuk menjawab pertanyaan penghuni kos.
                </p>
            </div>
        </div>

        <!-- Tab Controls -->
        <div class="flex items-center gap-2 border-b border-[#DEDAD1] pb-2">
            <button
                type="button"
                x-on:click="activeTab = 'rules'"
                :class="activeTab === 'rules' ? 'bg-[#121417] text-white shadow-xs' : 'bg-white text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] border border-[#DEDAD1]'"
                class="px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-150 cursor-pointer flex items-center gap-2 active:scale-[0.98]"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Tata Tertib Kos</span>
                <span :class="activeTab === 'rules' ? 'bg-[#20242B] text-[#C7F36B]' : 'bg-[#F1EFEA] text-[#6B6B6B]'" class="px-1.5 py-0.5 rounded-md text-[10px] font-mono font-medium">
                    {{ $rules->count() }}
                </span>
            </button>
            <button
                type="button"
                x-on:click="activeTab = 'faqs'"
                :class="activeTab === 'faqs' ? 'bg-[#121417] text-white shadow-xs' : 'bg-white text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA] border border-[#DEDAD1]'"
                class="px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-150 cursor-pointer flex items-center gap-2 active:scale-[0.98]"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Tanya Jawab / FAQ</span>
                <span :class="activeTab === 'faqs' ? 'bg-[#20242B] text-[#C7F36B]' : 'bg-[#F1EFEA] text-[#6B6B6B]'" class="px-1.5 py-0.5 rounded-md text-[10px] font-mono font-medium">
                    {{ $faqs->count() }}
                </span>
            </button>
        </div>

        <!-- TAB 1: RULES SECTION -->
        <div x-show="activeTab === 'rules'" class="space-y-4">
            <!-- Rules Filter & Action Bar -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-4 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col md:flex-row items-center justify-between gap-4">
                <form method="GET" action="{{ route('owner.properties.knowledge.index', $property) }}" class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                    <div class="relative w-full sm:w-64">
                        <input
                            type="text"
                            name="rule_search"
                            value="{{ request('rule_search') }}"
                            placeholder="Cari judul / isi aturan..."
                            class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:bg-white transition-all"
                        />
                        <svg class="w-4 h-4 text-[#A5A096] absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <select
                        name="rule_category"
                        class="px-3 py-2 text-xs font-medium rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] text-[#121417] focus:outline-hidden focus:border-[#121417] focus:bg-white cursor-pointer"
                    >
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->value }}" {{ request('rule_category') === $category->value ? 'selected' : '' }}>
                                {{ $category->label() }}
                            </option>
                        @endforeach
                    </select>
                    <button
                        type="submit"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-[#F1EFEA] hover:bg-[#E5E2DA] text-[#121417] border border-[#DEDAD1] cursor-pointer transition-colors"
                    >
                        Filter
                    </button>
                    @if(request('rule_search') || request('rule_category'))
                        <a
                            href="{{ route('owner.properties.knowledge.index', $property) }}"
                            class="text-xs text-[#6B6B6B] hover:text-[#121417] underline ml-1"
                        >
                            Reset
                        </a>
                    @endif
                </form>

                <button
                    type="button"
                    x-on:click="openCreateRule()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white text-xs font-semibold shadow-xs active:scale-[0.98] transition-all cursor-pointer shrink-0"
                >
                    <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Aturan Kos</span>
                </button>
            </div>

            <!-- Rules List -->
            @if($rules->isEmpty())
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-10 text-center shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-center justify-center text-[#6B6B6B] mx-auto">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-[#121417]">Belum ada tata tertib kos</h3>
                    <p class="text-xs text-[#6B6B6B] max-w-sm mx-auto">
                        Tambahkan aturan seperti jam malam, kebijakan tamu, kebersihan, atau penggunaan fasilitas bersama.
                    </p>
                    <button
                        type="button"
                        x-on:click="openCreateRule()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#121417] text-white text-xs font-semibold hover:bg-[#20242B] cursor-pointer transition-colors"
                    >
                        <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Aturan Pertama</span>
                    </button>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($rules as $rule)
                        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col md:flex-row md:items-start justify-between gap-4 hover:border-[#121417]/30 transition-all">
                            <div class="space-y-2 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-bold text-[#121417]">{{ $rule->title }}</h3>
                                    
                                    @if($rule->categoryEnum())
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">
                                            {{ $rule->categoryEnum()->label() }}
                                        </span>
                                    @endif

                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-medium {{ $rule->is_active ? 'bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60' : 'bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]' }}">
                                        {{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>

                                    <span class="text-[11px] font-mono text-[#A5A096]">#{{ $rule->order }}</span>
                                </div>
                                <div class="text-xs text-[#20242B] leading-relaxed whitespace-pre-line bg-[#F9F8F5] p-3.5 rounded-xl border border-[#DEDAD1]/80">
                                    {{ $rule->content }}
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-1.5 shrink-0 self-end md:self-start">
                                <!-- Toggle Active/Inactive -->
                                <form action="{{ route('owner.properties.knowledge.rules.toggle', [$property, $rule]) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        class="px-2.5 py-1.5 rounded-lg border text-[11px] font-medium cursor-pointer transition-colors {{ $rule->is_active ? 'border-[#DEDAD1] text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA]' : 'border-[#C7F36B] bg-[#E8F8D0] text-[#15803D] hover:bg-[#ddf5bd]' }}"
                                        title="{{ $rule->is_active ? 'Nonaktifkan aturan' : 'Aktifkan aturan' }}"
                                    >
                                        {{ $rule->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>

                                <!-- Edit Button -->
                                <button
                                    type="button"
                                    x-on:click="openEditRule({{ json_encode($rule) }})"
                                    class="px-2.5 py-1.5 rounded-lg bg-white border border-[#DEDAD1] text-[#121417] text-[11px] font-medium hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                                >
                                    Edit
                                </button>

                                <!-- Delete Button -->
                                <button
                                    type="button"
                                    x-on:click="confirmDelete('{{ route('owner.properties.knowledge.rules.destroy', [$property, $rule]) }}', '{{ addslashes($rule->title) }}')"
                                    class="px-2.5 py-1.5 rounded-lg border border-[#FECACA] bg-[#FEE2E2] text-[#DC2626] text-[11px] font-medium hover:bg-[#fca5a5]/30 transition-colors cursor-pointer"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- TAB 2: FAQS SECTION -->
        <div x-show="activeTab === 'faqs'" x-cloak class="space-y-4">
            <!-- FAQ Filter & Action Bar -->
            <div class="bg-white border border-[#DEDAD1] rounded-2xl p-4 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col md:flex-row items-center justify-between gap-4">
                <form method="GET" action="{{ route('owner.properties.knowledge.index', $property) }}" class="flex items-center gap-2.5 w-full md:w-auto">
                    <div class="relative w-full sm:w-72">
                        <input
                            type="text"
                            name="faq_search"
                            value="{{ request('faq_search') }}"
                            placeholder="Cari pertanyaan / jawaban FAQ..."
                            class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] text-[#121417] placeholder-[#A5A096] focus:outline-hidden focus:border-[#121417] focus:bg-white transition-all"
                        />
                        <svg class="w-4 h-4 text-[#A5A096] absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <button
                        type="submit"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-[#F1EFEA] hover:bg-[#E5E2DA] text-[#121417] border border-[#DEDAD1] cursor-pointer transition-colors"
                    >
                        Filter
                    </button>
                    @if(request('faq_search'))
                        <a
                            href="{{ route('owner.properties.knowledge.index', $property) }}"
                            class="text-xs text-[#6B6B6B] hover:text-[#121417] underline ml-1"
                        >
                            Reset
                        </a>
                    @endif
                </form>

                <button
                    type="button"
                    x-on:click="openCreateFaq()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white text-xs font-semibold shadow-xs active:scale-[0.98] transition-all cursor-pointer shrink-0"
                >
                    <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah FAQ Resmi</span>
                </button>
            </div>

            <!-- FAQ List -->
            @if($faqs->isEmpty())
                <div class="bg-white border border-[#DEDAD1] rounded-2xl p-10 text-center shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] flex items-center justify-center text-[#6B6B6B] mx-auto">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-[#121417]">Belum ada FAQ resmi</h3>
                    <p class="text-xs text-[#6B6B6B] max-w-sm mx-auto">
                        Tambahkan pertanyaan umum seperti prosedur pembayaran, fasilitas wifi, parkir, atau tamu menginap.
                    </p>
                    <button
                        type="button"
                        x-on:click="openCreateFaq()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#121417] text-white text-xs font-semibold hover:bg-[#20242B] cursor-pointer transition-colors"
                    >
                        <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah FAQ Pertama</span>
                    </button>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($faqs as $faq)
                        <div class="bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col md:flex-row md:items-start justify-between gap-4 hover:border-[#121417]/30 transition-all">
                            <div class="space-y-2 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-bold text-[#121417]">Q: {{ $faq->question }}</h3>

                                    @if($faq->category)
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-[#E0F2FE] text-[#0369A1] border border-[#BAE6FD]">
                                            {{ $faq->category }}
                                        </span>
                                    @endif

                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-medium {{ $faq->is_active ? 'bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60' : 'bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]' }}">
                                        {{ $faq->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>

                                    <span class="text-[11px] font-mono text-[#A5A096]">#{{ $faq->order }}</span>
                                </div>
                                <div class="text-xs text-[#20242B] leading-relaxed whitespace-pre-line bg-[#F9F8F5] p-3.5 rounded-xl border border-[#DEDAD1]/80">
                                    <span class="font-semibold text-[#6B6B6B] block mb-1">Jawaban Resmi:</span>
                                    {{ $faq->answer }}
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-1.5 shrink-0 self-end md:self-start">
                                <form action="{{ route('owner.properties.knowledge.faqs.toggle', [$property, $faq]) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        class="px-2.5 py-1.5 rounded-lg border text-[11px] font-medium cursor-pointer transition-colors {{ $faq->is_active ? 'border-[#DEDAD1] text-[#6B6B6B] hover:text-[#121417] hover:bg-[#F1EFEA]' : 'border-[#C7F36B] bg-[#E8F8D0] text-[#15803D] hover:bg-[#ddf5bd]' }}"
                                        title="{{ $faq->is_active ? 'Nonaktifkan FAQ' : 'Aktifkan FAQ' }}"
                                    >
                                        {{ $faq->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>

                                <button
                                    type="button"
                                    x-on:click="openEditFaq({{ json_encode($faq) }})"
                                    class="px-2.5 py-1.5 rounded-lg bg-white border border-[#DEDAD1] text-[#121417] text-[11px] font-medium hover:bg-[#F1EFEA] transition-colors cursor-pointer"
                                >
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    x-on:click="confirmDelete('{{ route('owner.properties.knowledge.faqs.destroy', [$property, $faq]) }}', '{{ addslashes($faq->question) }}')"
                                    class="px-2.5 py-1.5 rounded-lg border border-[#FECACA] bg-[#FEE2E2] text-[#DC2626] text-[11px] font-medium hover:bg-[#fca5a5]/30 transition-colors cursor-pointer"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- MODAL: ADD / EDIT RULE -->
        <div
            x-show="ruleModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#121417]/50 backdrop-blur-xs"
        >
            <div
                x-show="ruleModalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-on:click.outside="ruleModalOpen = false"
                class="bg-white border border-[#DEDAD1] rounded-2xl p-6 max-w-lg w-full shadow-[0_20px_25px_-5px_rgba(0,0,0,0.1)] space-y-5"
            >
                <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                    <h3 class="text-base font-bold text-[#121417]" x-text="ruleMode === 'create' ? 'Tambah Aturan Kos Baru' : 'Edit Aturan Kos'"></h3>
                    <button type="button" x-on:click="ruleModalOpen = false" class="text-[#A5A096] hover:text-[#121417] cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="ruleFormAction" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="ruleMode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-xs font-semibold text-[#121417] mb-1">Judul Aturan <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="title"
                            x-model="ruleData.title"
                            required
                            maxlength="255"
                            placeholder="Contoh: Jam Malam Pintu Pagar"
                            class="w-full px-3.5 py-2 text-xs rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] focus:outline-hidden focus:border-[#121417] focus:bg-white text-[#121417]"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#121417] mb-1">Kategori</label>
                            <select
                                name="category"
                                x-model="ruleData.category"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] focus:outline-hidden focus:border-[#121417] focus:bg-white text-[#121417] cursor-pointer"
                            >
                                @foreach($categories as $category)
                                    <option value="{{ $category->value }}">{{ $category->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#121417] mb-1">Urutan Prioritas</label>
                            <input
                                type="number"
                                name="order"
                                x-model="ruleData.order"
                                min="0"
                                class="w-full px-3.5 py-2 text-xs rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] focus:outline-hidden focus:border-[#121417] focus:bg-white text-[#121417] font-mono"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#121417] mb-1">Isi / Ketentuan Aturan <span class="text-red-500">*</span></label>
                        <textarea
                            name="content"
                            x-model="ruleData.content"
                            required
                            rows="4"
                            placeholder="Tuliskan isi aturan secara jelas dan mudah dipahami penghuni..."
                            class="w-full px-3.5 py-2 text-xs rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] focus:outline-hidden focus:border-[#121417] focus:bg-white text-[#121417] leading-relaxed"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="rule_is_active"
                            name="is_active"
                            value="1"
                            x-model="ruleData.is_active"
                            class="rounded border border-[#DEDAD1] text-[#121417] focus:ring-[#121417] w-4 h-4 cursor-pointer"
                        />
                        <label for="rule_is_active" class="text-xs font-medium text-[#121417] cursor-pointer">
                            Aktifkan aturan ini (dapat dibaca oleh penghuni &amp; AI service)
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#DEDAD1]">
                        <button
                            type="button"
                            x-on:click="ruleModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] text-[#6B6B6B] hover:text-[#121417] text-xs font-semibold cursor-pointer transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white text-xs font-semibold cursor-pointer shadow-xs active:scale-[0.98] transition-all"
                        >
                            Simpan Aturan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: ADD / EDIT FAQ -->
        <div
            x-show="faqModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#121417]/50 backdrop-blur-xs"
        >
            <div
                x-show="faqModalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-on:click.outside="faqModalOpen = false"
                class="bg-white border border-[#DEDAD1] rounded-2xl p-6 max-w-lg w-full shadow-[0_20px_25px_-5px_rgba(0,0,0,0.1)] space-y-5"
            >
                <div class="flex items-center justify-between border-b border-[#DEDAD1] pb-3">
                    <h3 class="text-base font-bold text-[#121417]" x-text="faqMode === 'create' ? 'Tambah FAQ Baru' : 'Edit FAQ'"></h3>
                    <button type="button" x-on:click="faqModalOpen = false" class="text-[#A5A096] hover:text-[#121417] cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="faqFormAction" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="faqMode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-xs font-semibold text-[#121417] mb-1">Pertanyaan (Question) <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="question"
                            x-model="faqData.question"
                            required
                            maxlength="255"
                            placeholder="Contoh: Kapan jatuh tempo pembayaran sewa kos?"
                            class="w-full px-3.5 py-2 text-xs rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] focus:outline-hidden focus:border-[#121417] focus:bg-white text-[#121417]"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#121417] mb-1">Kategori / Topik</label>
                            <input
                                type="text"
                                name="category"
                                x-model="faqData.category"
                                placeholder="Misal: Pembayaran, WiFi"
                                class="w-full px-3.5 py-2 text-xs rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] focus:outline-hidden focus:border-[#121417] focus:bg-white text-[#121417]"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#121417] mb-1">Urutan Prioritas</label>
                            <input
                                type="number"
                                name="order"
                                x-model="faqData.order"
                                min="0"
                                class="w-full px-3.5 py-2 text-xs rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] focus:outline-hidden focus:border-[#121417] focus:bg-white text-[#121417] font-mono"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#121417] mb-1">Jawaban Resmi (Answer) <span class="text-red-500">*</span></label>
                        <textarea
                            name="answer"
                            x-model="faqData.answer"
                            required
                            rows="4"
                            placeholder="Tuliskan jawaban resmi kos..."
                            class="w-full px-3.5 py-2 text-xs rounded-xl bg-[#F9F8F5] border border-[#DEDAD1] focus:outline-hidden focus:border-[#121417] focus:bg-white text-[#121417] leading-relaxed"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="faq_is_active"
                            name="is_active"
                            value="1"
                            x-model="faqData.is_active"
                            class="rounded border border-[#DEDAD1] text-[#121417] focus:ring-[#121417] w-4 h-4 cursor-pointer"
                        />
                        <label for="faq_is_active" class="text-xs font-medium text-[#121417] cursor-pointer">
                            Aktifkan FAQ ini (dapat dibaca oleh penghuni &amp; AI service)
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#DEDAD1]">
                        <button
                            type="button"
                            x-on:click="faqModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] text-[#6B6B6B] hover:text-[#121417] text-xs font-semibold cursor-pointer transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-xl bg-[#121417] hover:bg-[#20242B] text-white text-xs font-semibold cursor-pointer shadow-xs active:scale-[0.98] transition-all"
                        >
                            Simpan FAQ
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: DELETE CONFIRMATION -->
        <div
            x-show="deleteModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#121417]/50 backdrop-blur-xs"
        >
            <div
                x-show="deleteModalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-on:click.outside="deleteModalOpen = false"
                class="bg-white border border-[#DEDAD1] rounded-2xl p-6 max-w-md w-full shadow-[0_20px_25px_-5px_rgba(0,0,0,0.1)] space-y-4"
            >
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#FEE2E2] border border-[#FECACA] flex items-center justify-center text-[#DC2626] shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#121417]">Konfirmasi Penghapusan</h3>
                        <p class="text-xs text-[#6B6B6B]">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </div>

                <p class="text-xs text-[#6B6B6B] leading-relaxed">
                    Apakah Anda yakin ingin menghapus <strong class="text-[#121417]" x-text="deleteItemTitle"></strong> dari basis pengetahuan kos? Item yang dihapus akan dikeluarkan dari indeks AI pada sinkronisasi berikutnya.
                </p>

                <form :action="deleteFormAction" method="POST" class="flex items-center justify-end gap-2.5 pt-2">
                    @csrf
                    @method('DELETE')
                    <button
                        type="button"
                        x-on:click="deleteModalOpen = false"
                        class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] text-[#6B6B6B] hover:text-[#121417] text-xs font-semibold cursor-pointer transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] text-white text-xs font-semibold cursor-pointer shadow-xs active:scale-[0.98] transition-all"
                    >
                        Hapus Permanen
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.owner>
