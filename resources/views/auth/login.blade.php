@props([
    'title' => 'Masuk — HUNI App',
    'initialRole' => 'owner',
])

<x-layouts.base :title="$title">
    <div class="min-h-screen bg-[#121417] huni-canvas-bg flex flex-col items-center justify-center p-4 sm:p-6 lg:p-8 selection:bg-[#15803D] selection:text-white relative">
        <div class="w-full max-w-md mx-auto space-y-4">
            <!-- Brand Logo Header & Official Badge -->
            <div class="flex items-center justify-between px-1">
                <a href="{{ route('login') }}" class="inline-flex items-center bg-white px-3 py-1.5 rounded-2xl shadow-xs group" title="HUNI App">
                    <img src="{{ asset('images/branding/huni-logo.png') }}" alt="HUNI Logo" class="h-8 w-auto object-contain transition-transform group-hover:scale-102" />
                </a>

                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-[#1F2329] border border-[#2D323B] text-white shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                    <span>Portal Resmi</span>
                </div>
            </div>

            <!-- Main Form Card -->
            <div
                x-data="{
                    activeRole: '{{ $initialRole }}',
                    email: '{{ old('email', '') }}',
                    password: '',
                    showPassword: false,
                    fillDemo(role) {
                        this.activeRole = role;
                        if (role === 'owner') {
                            this.email = 'aksan@huniapp.test';
                            this.password = 'password';
                        } else {
                            this.email = 'budi@huniapp.test';
                            this.password = 'password';
                        }
                    }
                }"
                class="bg-white border border-[#DEDAD1] rounded-3xl p-6 sm:p-8 shadow-[0_20px_50px_rgba(0,0,0,0.45)] space-y-5"
            >
                <!-- Title & Context -->
                <div class="space-y-1">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#121417] tracking-tight">
                        Selamat Datang Kembali
                    </h1>
                    <p class="text-xs sm:text-sm text-[#767C85]">
                        Masuk untuk mengelola kos atau mengakses informasi kamar Anda.
                    </p>
                </div>

                <!-- Role Quick Select Tabs -->
                <div class="bg-[#F3F1EC] p-1 rounded-xl flex items-center gap-1 text-xs font-semibold border border-[#E5E3DC]">
                    <button
                        type="button"
                        x-on:click="fillDemo('owner')"
                        :class="activeRole === 'owner' ? 'bg-white text-[#121417] shadow-xs' : 'text-[#767C85] hover:text-[#121417]'"
                        class="flex-1 py-2 px-3 rounded-lg transition-all text-center flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <x-ui.icon name="building" class="w-4 h-4" />
                        <span>Pemilik Kos</span>
                    </button>
                    <button
                        type="button"
                        x-on:click="fillDemo('tenant')"
                        :class="activeRole === 'tenant' ? 'bg-white text-[#121417] shadow-xs' : 'text-[#767C85] hover:text-[#121417]'"
                        class="flex-1 py-2 px-3 rounded-lg transition-all text-center flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <x-ui.icon name="door" class="w-4 h-4" />
                        <span>Penghuni Kos</span>
                    </button>
                </div>

                <!-- Flash Status Alert -->
                @if (session('status'))
                    <x-ui.alert variant="success">
                        {{ session('status') }}
                    </x-ui.alert>
                @endif

                @if ($errors->any())
                    <x-ui.alert variant="danger">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </x-ui.alert>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <!-- Email Input -->
                    <div class="space-y-1.5">
                        <label for="login-email" class="block text-xs font-semibold text-[#121417]">
                            Alamat Email
                        </label>
                        <div class="relative flex items-center">
                            <div class="absolute left-3.5 pointer-events-none text-[#767C85]">
                                <x-ui.icon name="mail" class="w-4 h-4" />
                            </div>
                            <input
                                id="login-email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                x-model="email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@email.com"
                                class="w-full bg-[#FAF9F6] border border-[#DEDAD1] rounded-xl pl-10 pr-4 py-2.5 text-[#121417] text-sm font-medium placeholder-[#8A919E] shadow-xs focus:bg-white focus:outline-none focus:border-[#121417] focus:ring-2 focus:ring-[#121417]/10 transition-all"
                            />
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="login-password" class="block text-xs font-semibold text-[#121417]">
                                Kata Sandi
                            </label>
                        </div>
                        <div class="relative flex items-center">
                            <div class="absolute left-3.5 pointer-events-none text-[#767C85]">
                                <x-ui.icon name="lock" class="w-4 h-4" />
                            </div>
                            <input
                                id="login-password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                x-model="password"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan kata sandi"
                                class="w-full bg-[#FAF9F6] border border-[#DEDAD1] rounded-xl pl-10 pr-11 py-2.5 text-[#121417] text-sm font-medium placeholder-[#8A919E] shadow-xs focus:bg-white focus:outline-none focus:border-[#121417] focus:ring-2 focus:ring-[#121417]/10 transition-all"
                            />
                            <button
                                type="button"
                                x-on:click="showPassword = !showPassword"
                                class="absolute right-3.5 text-[#767C85] hover:text-[#121417] cursor-pointer"
                                aria-label="Tampilkan atau sembunyikan password"
                            >
                                <template x-if="!showPassword">
                                    <x-ui.icon name="eye" class="w-4 h-4" />
                                </template>
                                <template x-if="showPassword">
                                    <x-ui.icon name="eye-off" class="w-4 h-4" />
                                </template>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center pt-1">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                name="remember"
                                class="w-4 h-4 rounded-md border border-[#DEDAD1] text-[#121417] focus:ring-0 focus:ring-offset-0 cursor-pointer accent-[#15803D]"
                            />
                            <span class="text-xs font-medium text-[#474D57]">Ingat saya di perangkat ini</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full bg-[#15803D] hover:bg-[#166534] text-white font-bold text-sm py-2.5 px-4 rounded-xl shadow-sm hover:shadow active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer mt-2"
                    >
                        <span>Masuk ke Akun</span>
                        <x-ui.icon name="arrow-right" class="w-4 h-4" />
                    </button>
                </form>

                <!-- Quick Demo Credentials for Testing -->
                <div class="pt-3 border-t border-[#EBE8E1] space-y-2">
                    <p class="text-[10px] font-semibold uppercase text-[#767C85] tracking-wider text-center">
                        Akses Cepat Pengujian
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            x-on:click="fillDemo('owner')"
                            class="p-2.5 bg-[#FAF9F6] hover:bg-[#F3F1EC] border border-[#DEDAD1] hover:border-[#121417] rounded-xl text-left text-xs font-medium text-[#121417] shadow-xs transition-all cursor-pointer"
                        >
                            <span class="block font-bold text-xs text-[#121417]">Pak Aksan</span>
                            <span class="text-[11px] text-[#767C85] block">Pemilik Kos</span>
                        </button>
                        <button
                            type="button"
                            x-on:click="fillDemo('tenant')"
                            class="p-2.5 bg-[#FAF9F6] hover:bg-[#F3F1EC] border border-[#DEDAD1] hover:border-[#121417] rounded-xl text-left text-xs font-medium text-[#121417] shadow-xs transition-all cursor-pointer"
                        >
                            <span class="block font-bold text-xs text-[#121417]">Mas Budi</span>
                            <span class="text-[11px] text-[#767C85] block">Penghuni Kos</span>
                        </button>
                    </div>
                </div>

                <!-- Registration / Help Note -->
                <div class="text-center pt-2">
                    <p class="text-xs text-[#767C85]">
                        Calon penghuni baru?
                        <span class="text-[#121417] font-semibold block sm:inline">
                            Buka tautan undangan dari pemilik kos.
                        </span>
                    </p>
                </div>
            </div>

            <!-- Footer Trust Indicator -->
            <div class="flex items-center justify-between text-[11px] text-[#9CA3AF] px-2 pt-2">
                <span class="flex items-center gap-1.5">
                    <x-ui.icon name="shield" class="w-3.5 h-3.5 text-[#15803D]" />
                    <span>Sistem Tata Kelola Kos Terpadu</span>
                </span>
                <span class="font-mono text-[#6B7280]">HUNI v2.0</span>
            </div>
        </div>
    </div>
</x-layouts.base>
