<x-layouts.base :title="'Undangan Sewa Kamar ' . $room->room_number . ' — HUNI App'">
    <div class="min-h-screen bg-[#F9F8F5] py-8 sm:py-12 px-4 sm:px-6 lg:px-8 selection:bg-[#C7F36B] selection:text-[#121417]">
        <div class="max-w-4xl mx-auto space-y-8">
            <!-- Header Brand -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pb-6 border-b border-[#DEDAD1]">
                <a href="{{ route('login') }}" class="flex items-center gap-3 group" title="HUNI App">
                    <img src="{{ asset('images/branding/huni-logo.png') }}" alt="HUNI Logo" class="h-9 w-auto object-contain transition-transform group-hover:scale-102" />
                    <span class="hidden sm:inline-block border-l border-[#DEDAD1] pl-3 text-xs font-semibold text-[#6B6B6B] uppercase tracking-wider">Undangan Penghuni</span>
                </a>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#15803D] animate-pulse"></span>
                        Undangan Resmi
                    </span>
                </div>
            </div>

            <!-- Flash Status Messages -->
            @if (session('success'))
                <x-ui.alert type="success" :dismissible="true">
                    {{ session('success') }}
                </x-ui.alert>
            @endif

            @if (session('error'))
                <x-ui.alert type="danger" :dismissible="true">
                    {{ session('error') }}
                </x-ui.alert>
            @endif

            @if ($errors->any())
                <x-ui.alert type="danger" :dismissible="true">
                    <p class="font-semibold mb-1">Terdapat kesalahan data:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-xs">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            <!-- Main Invitation Hero / Status Banner -->
            @if ($invitation->isExpired())
                <div class="bg-[#FEF2F2] border border-[#FECACA] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-white border border-[#FECACA] flex items-center justify-center text-[#DC2626] shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[#991B1B]">Undangan Ini Telah Kedaluwarsa</h2>
                        <p class="text-xs text-[#B91C1C] mt-1 leading-relaxed">
                            Masa berlaku undangan ini telah berakhir pada {{ $invitation->expires_at?->translatedFormat('d F Y, H:i') ?? 'beberapa waktu lalu' }}.
                            Silakan hubungi pengelola kos ({{ $invitation->sender?->name ?? 'Pemilik' }}) untuk meminta undangan baru.
                        </p>
                    </div>
                </div>
            @elseif ($invitation->isRevoked())
                <div class="bg-[#F9F8F5] border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-white border border-[#DEDAD1] flex items-center justify-center text-[#6B6B6B] shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[#121417]">Undangan Telah Dibatalkan</h2>
                        <p class="text-xs text-[#6B6B6B] mt-1 leading-relaxed">
                            Pemilik kos telah membatalkan undangan sewa ini sehingga kamar tidak lagi tersedia melalui tautan ini.
                        </p>
                    </div>
                </div>
            @elseif ($invitation->isAccepted())
                <div class="bg-[#E8F8D0]/40 border border-[#C7F36B]/60 rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-white border border-[#C7F36B]/60 flex items-center justify-center text-[#15803D] shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[#15803D]">Undangan Sudah Diterima</h2>
                        <p class="text-xs text-[#166534] mt-1 leading-relaxed">
                            Undangan ini telah berhasil diterima pada {{ $invitation->accepted_at?->translatedFormat('d F Y, H:i') ?? 'waktu lalu' }} oleh
                            <strong>{{ $invitation->tenant?->name ?? $invitation->name }}</strong>.
                        </p>
                        @auth
                            @if (auth()->user()->isTenant())
                                <a href="{{ route('tenant.dashboard') }}" class="inline-flex items-center gap-1.5 mt-3 text-xs font-semibold text-[#121417] underline hover:text-black">
                                    Buka Dashboard Penghuni →
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            @endif

            <!-- Content Grid: Room Info & Invitation Acceptance -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Left Column: Property & Room Overview -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-5">
                        <div class="space-y-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wider bg-[#F1EFEA] text-[#6B6B6B] border border-[#DEDAD1]">
                                Kamar Ditawarkan
                            </span>
                            <h2 class="text-xl font-bold text-[#121417]">
                                Kamar {{ $room->room_number }}
                            </h2>
                            <p class="text-xs text-[#6B6B6B]">
                                {{ $property->name }}
                            </p>
                        </div>

                        <!-- Room Details Badge Group -->
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-[#DEDAD1]">
                            <div class="p-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl">
                                <span class="text-[10px] font-medium uppercase text-[#6B6B6B] block">Tipe Kamar</span>
                                <span class="text-xs font-semibold text-[#121417]">{{ $room->type ?? 'Standar' }}</span>
                            </div>
                            <div class="p-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl">
                                <span class="text-[10px] font-medium uppercase text-[#6B6B6B] block">Lantai</span>
                                <span class="text-xs font-semibold text-[#121417]">Lantai {{ $room->floor ?? '1' }}</span>
                            </div>
                            <div class="p-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl">
                                <span class="text-[10px] font-medium uppercase text-[#6B6B6B] block">Ukuran</span>
                                <span class="text-xs font-semibold text-[#121417]">{{ $room->size ?? '3x4 m²' }}</span>
                            </div>
                            <div class="p-2.5 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl">
                                <span class="text-[10px] font-medium uppercase text-[#6B6B6B] block">Harga Sewa</span>
                                <span class="text-xs font-mono font-bold tabular-nums text-[#15803D]">Rp {{ number_format($room->price ?? 0, 0, ',', '.') }}<span class="text-[10px] text-[#6B6B6B]">/bln</span></span>
                            </div>
                        </div>

                        <!-- Facilities -->
                        @if (! empty($room->facilities))
                            <div class="space-y-2 pt-2 border-t border-[#DEDAD1]">
                                <span class="text-xs font-semibold text-[#121417] block">Fasilitas Kamar:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ((is_array($room->facilities) ? $room->facilities : json_decode($room->facilities, true) ?? []) as $facility)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-[#F9F8F5] border border-[#DEDAD1] text-[#121417]">
                                            ✓ {{ $facility }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Property Address -->
                        <div class="space-y-1 pt-2 border-t border-[#DEDAD1] text-xs">
                            <span class="font-semibold text-[#121417] block">Lokasi Kos:</span>
                            <p class="text-[#6B6B6B] leading-relaxed">
                                {{ $property->address }}, {{ $property->city }}
                            </p>
                        </div>

                        <!-- Sender / Owner Info -->
                        <div class="p-3 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-[#121417] text-white flex items-center justify-center font-bold text-xs">
                                {{ mb_substr($invitation->sender?->name ?? 'P', 0, 1) }}
                            </div>
                            <div>
                                <span class="text-[10px] font-medium uppercase text-[#6B6B6B] block">Diundang Oleh</span>
                                <span class="text-xs font-semibold text-[#121417]">{{ $invitation->sender?->name ?? 'Pemilik Kos' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Interactive Acceptance Actions -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-white border border-[#DEDAD1] rounded-2xl p-6 sm:p-8 shadow-[0_1px_3px_rgba(18,20,23,0.04)] space-y-6">
                        <!-- Recipient Target Info -->
                        <div class="flex items-center justify-between pb-4 border-b border-[#DEDAD1]">
                            <div>
                                <span class="text-[10px] font-semibold uppercase text-[#6B6B6B] block">Calon Penghuni Dituju</span>
                                <h3 class="text-base font-bold text-[#121417]">{{ $invitation->name }}</h3>
                                <p class="text-xs text-[#6B6B6B]">{{ $invitation->email }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-semibold uppercase text-[#6B6B6B] block">Batas Berlaku</span>
                                <span class="text-xs font-semibold text-[#121417]">
                                    {{ $invitation->expires_at?->format('d M Y, H:i') ?? '-' }}
                                </span>
                            </div>
                        </div>

                        @if ($invitation->isPending())
                            {{-- CASE 1: AUTHENTICATED USER --}}
                            @auth
                                @if (auth()->user()->isTenant())
                                    @if ($isEmailMatching)
                                        {{-- Tenant with matching email: Direct Accept Action --}}
                                        <div class="space-y-4">
                                            <div class="p-4 bg-[#E8F8D0]/40 border border-[#C7F36B]/60 rounded-xl space-y-1">
                                                <h4 class="text-xs font-bold text-[#15803D]">✓ Akun Terverifikasi Sesuai</h4>
                                                <p class="text-xs text-[#166534] leading-relaxed">
                                                    Anda login sebagai <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}).
                                                    Email akun Anda cocok dengan identitas penerima undangan ini.
                                                </p>
                                            </div>

                                            <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}" class="space-y-4">
                                                @csrf
                                                <div class="p-4 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-xs text-[#6B6B6B] leading-relaxed">
                                                    Dengan mengklik tombol terima di bawah, Anda mengonfirmasi kesediaan untuk menempati Kamar {{ $room->room_number }} di {{ $property->name }}. Status kamar akan diperbarui menjadi terisi.
                                                </div>

                                                <button
                                                    type="submit"
                                                    class="w-full bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs py-3 px-4 rounded-xl shadow-xs active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer"
                                                >
                                                    <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span>Terima Undangan Sewa Sekarang</span>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        {{-- Tenant with non-matching email --}}
                                        <div class="p-5 bg-[#FEF2F2] border border-[#FECACA] rounded-xl space-y-3">
                                            <div class="flex items-center gap-2 text-xs font-bold text-[#991B1B]">
                                                <svg class="w-4 h-4 text-[#DC2626]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <span>Email Akun Tidak Sesuai</span>
                                            </div>
                                            <p class="text-xs text-[#B91C1C] leading-relaxed">
                                                Anda saat ini login menggunakan akun dengan email <strong>{{ auth()->user()->email }}</strong>.
                                                Namun, undangan ini hanya dapat diterima oleh pemilik email <strong>{{ $invitation->email }}</strong>.
                                            </p>
                                            <div class="pt-2 flex items-center gap-3">
                                                <form method="POST" action="{{ route('logout') }}">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-semibold px-3 py-1.5 bg-white border border-[#DEDAD1] rounded-lg hover:bg-gray-50 cursor-pointer text-[#121417]">
                                                        Keluar (Logout) Akun Ini
                                                    </button>
                                                </form>
                                                <span class="text-xs text-[#6B6B6B]">lalu masuk dengan akun yang tepat</span>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    {{-- Authenticated as Owner --}}
                                    <div class="p-5 bg-[#FEF3C7] border border-[#FDE68A] rounded-xl space-y-3">
                                        <div class="flex items-center gap-2 text-xs font-bold text-[#92400E]">
                                            <svg class="w-4 h-4 text-[#D97706]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span>Akun Pemilik Kos Terdeteksi</span>
                                        </div>
                                        <p class="text-xs text-[#78350F] leading-relaxed">
                                            Anda sedang masuk sebagai Pemilik Kos ({{ auth()->user()->name }}). Undangan sewa kamar hanya dapat diterima oleh akun Penghuni Kos.
                                        </p>
                                        <div class="pt-2 flex items-center gap-3">
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="text-xs font-semibold px-3 py-1.5 bg-white border border-[#DEDAD1] rounded-lg hover:bg-gray-50 cursor-pointer text-[#121417]">
                                                    Keluar untuk Menerima Undangan
                                                </button>
                                            </form>
                                            <a href="{{ route('owner.invitations.show', $invitation) }}" class="text-xs font-semibold text-[#121417] underline">
                                                Kembali ke Kelola Undangan
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @else
                                {{-- CASE 2: GUEST (NOT LOGGED IN) --}}
                                @if ($userExistsForEmail)
                                    {{-- Account already exists for this email --}}
                                    <div class="space-y-4">
                                        <div class="p-5 bg-[#F0F7FF] border border-[#BAE6FD] rounded-xl space-y-2">
                                            <div class="flex items-center gap-2 text-xs font-bold text-[#0369A1]">
                                                <svg class="w-4 h-4 text-[#0284C7]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                                </svg>
                                                <span>Akun Sudah Terdaftar di HUNI</span>
                                            </div>
                                            <p class="text-xs text-[#0C4A6E] leading-relaxed">
                                                Email <strong>{{ $invitation->email }}</strong> sudah memiliki akun di sistem HUNI. Silakan login ke akun Anda untuk langsung menerima dan mengaktifkan undangan ini.
                                            </p>
                                        </div>

                                        <a
                                            href="{{ route('login', ['redirect' => route('invitations.show', $invitation->token)]) }}"
                                            class="w-full bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs py-3 px-4 rounded-xl shadow-xs active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer"
                                        >
                                            <span>Masuk ke Akun Saya &amp; Terima Undangan</span>
                                            <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                        </a>
                                    </div>
                                @else
                                    {{-- Account does not exist yet: Simultaneous Register & Accept Form --}}
                                    <div class="space-y-5">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase bg-[#E8F8D0] text-[#15803D] border border-[#C7F36B]/60">
                                                Langkah Terakhir
                                            </span>
                                            <h4 class="text-base font-bold text-[#121417]">
                                                Buat Akun Penghuni &amp; Terima Undangan
                                            </h4>
                                            <p class="text-xs text-[#6B6B6B]">
                                                Lengkapi data diri Anda untuk mengaktifkan akun penghuni di HUNI App.
                                            </p>
                                        </div>

                                        <form method="POST" action="{{ route('invitations.register', $invitation->token) }}" class="space-y-4">
                                            @csrf

                                            <!-- Email (Read-only locked to invitation) -->
                                            <div class="space-y-1.5">
                                                <label class="block text-xs font-semibold text-[#121417]">
                                                    Email Penghuni <span class="text-[#15803D] text-[11px] font-normal">(Terkunci sesuai undangan)</span>
                                                </label>
                                                <input
                                                    type="email"
                                                    value="{{ $invitation->email }}"
                                                    disabled
                                                    class="w-full bg-[#F1EFEA] border border-[#DEDAD1] rounded-xl px-3.5 py-2.5 text-[#6B6B6B] text-xs font-medium cursor-not-allowed"
                                                />
                                            </div>

                                            <!-- Full Name -->
                                            <div class="space-y-1.5">
                                                <label for="name" class="block text-xs font-semibold text-[#121417]">
                                                    Nama Lengkap <span class="text-red-500">*</span>
                                                </label>
                                                <input
                                                    type="text"
                                                    id="name"
                                                    name="name"
                                                    required
                                                    value="{{ old('name', $invitation->name) }}"
                                                    placeholder="Nama lengkap sesuai KTP"
                                                    class="w-full bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl px-3.5 py-2.5 text-[#121417] text-xs font-medium focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all"
                                                />
                                            </div>

                                            <!-- Phone -->
                                            <div class="space-y-1.5">
                                                <label for="phone" class="block text-xs font-semibold text-[#121417]">
                                                    Nomor WhatsApp / HP
                                                </label>
                                                <input
                                                    type="tel"
                                                    id="phone"
                                                    name="phone"
                                                    value="{{ old('phone', $invitation->phone) }}"
                                                    placeholder="Contoh: 08123456789"
                                                    class="w-full bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl px-3.5 py-2.5 text-[#121417] text-xs font-medium focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all"
                                                />
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <!-- NIK -->
                                                <div class="space-y-1.5">
                                                    <label for="nik" class="block text-xs font-semibold text-[#121417]">
                                                        NIK (Opsional)
                                                    </label>
                                                    <input
                                                        type="text"
                                                        id="nik"
                                                        name="nik"
                                                        value="{{ old('nik') }}"
                                                        placeholder="16 digit NIK"
                                                        class="w-full bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl px-3.5 py-2.5 text-[#121417] text-xs font-mono focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all"
                                                    />
                                                </div>

                                                <!-- Occupation -->
                                                <div class="space-y-1.5">
                                                    <label for="occupation" class="block text-xs font-semibold text-[#121417]">
                                                        Profesi / Pekerjaan
                                                    </label>
                                                    <input
                                                        type="text"
                                                        id="occupation"
                                                        name="occupation"
                                                        value="{{ old('occupation', 'Mahasiswa / Karyawan') }}"
                                                        placeholder="Contoh: Karyawan Swasta"
                                                        class="w-full bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl px-3.5 py-2.5 text-[#121417] text-xs font-medium focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all"
                                                    />
                                                </div>
                                            </div>

                                            <!-- Password & Confirmation -->
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div class="space-y-1.5">
                                                    <label for="password" class="block text-xs font-semibold text-[#121417]">
                                                        Buat Password <span class="text-red-500">*</span>
                                                    </label>
                                                    <input
                                                        type="password"
                                                        id="password"
                                                        name="password"
                                                        required
                                                        placeholder="Min. 8 karakter"
                                                        class="w-full bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl px-3.5 py-2.5 text-[#121417] text-xs font-medium focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all"
                                                    />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <label for="password_confirmation" class="block text-xs font-semibold text-[#121417]">
                                                        Ulangi Password <span class="text-red-500">*</span>
                                                    </label>
                                                    <input
                                                        type="password"
                                                        id="password_confirmation"
                                                        name="password_confirmation"
                                                        required
                                                        placeholder="Ketik ulang password"
                                                        class="w-full bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl px-3.5 py-2.5 text-[#121417] text-xs font-medium focus:outline-hidden focus:bg-white focus:border-[#121417] transition-all"
                                                    />
                                                </div>
                                            </div>

                                            <!-- Submit Register & Accept -->
                                            <button
                                                type="submit"
                                                class="w-full bg-[#121417] hover:bg-[#20242B] text-white font-semibold text-xs py-3 px-4 rounded-xl shadow-xs active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer mt-3"
                                            >
                                                <span>Daftar &amp; Terima Undangan Sekarang</span>
                                                <svg class="w-4 h-4 text-[#C7F36B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @endauth
                        @else
                            {{-- Invitation is not pending (Expired, Revoked, or Accepted) --}}
                            <div class="p-6 bg-[#F9F8F5] border border-[#DEDAD1] rounded-xl text-center space-y-2">
                                <p class="text-xs font-semibold text-[#121417]">
                                    Undangan ini sedang tidak dapat diproses.
                                </p>
                                <p class="text-xs text-[#6B6B6B]">
                                    Status saat ini: <strong class="uppercase text-[#121417]">{{ $invitation->status }}</strong>.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.base>
