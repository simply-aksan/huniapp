@props([
    'active' => 'dashboard',
])

@php
    $navItems = [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'route' => 'owner.dashboard'],
        ['id' => 'properties', 'label' => 'Properti & Kamar', 'icon' => 'building', 'route' => 'owner.properties.index'],
        ['id' => 'tenants', 'label' => 'Penghuni', 'icon' => 'users', 'route' => 'owner.tenants.index'],
        ['id' => 'invitations', 'label' => 'Undangan Sewa', 'icon' => 'mail', 'route' => 'owner.invitations.index'],
        ['id' => 'contracts', 'label' => 'Kontrak', 'icon' => 'file-text', 'route' => 'owner.contracts.index'],
        ['id' => 'bills', 'label' => 'Tagihan & Invoice', 'icon' => 'receipt', 'route' => 'owner.bills.index'],
        ['id' => 'payments', 'label' => 'Verifikasi Bayar', 'icon' => 'credit-card', 'route' => 'owner.payments.index'],
        ['id' => 'complaints', 'label' => 'Komplain', 'icon' => 'message-square', 'route' => 'owner.complaints.index'],
        ['id' => 'inventory', 'label' => 'Fasilitas & Barang', 'icon' => 'box', 'route' => 'owner.inventory.index'],
        ['id' => 'analytics', 'label' => 'Laporan & Finansial', 'icon' => 'bar-chart', 'route' => 'owner.analytics.index'],
    ];

    $owner = auth()->user();
    $primaryProperty = $owner?->properties()->where('is_primary', true)->first() ?? $owner?->properties()->first();
    $propertyName = $primaryProperty ? $primaryProperty->name : 'Belum Ada Properti';
    $propertyAddress = $primaryProperty ? ($primaryProperty->district ? $primaryProperty->district . ', ' . $primaryProperty->city : $primaryProperty->city) : 'Tambah properti pertama';
@endphp

<!-- Mobile Backdrop Drawer (Overlay) -->
<div
    x-show="mobileOpen"
    x-cloak
    x-transition:enter="transition-opacity ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-on:click="mobileOpen = false"
    class="fixed inset-0 z-40 bg-[#121417]/70 backdrop-blur-xs lg:hidden"
></div>

<!-- Sidebar Container -->
<aside
    :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed lg:static top-0 bottom-0 left-0 z-50 lg:z-auto w-64 shrink-0 h-screen lg:translate-x-0 bg-[#16191E] border-r border-[#262A30] p-4 flex flex-col justify-between select-none transition-transform duration-200 ease-in-out overflow-hidden"
>
    <!-- Top Header (Logo + Mobile Close + Property Badge) -->
    <div class="shrink-0 space-y-3 pb-2">
        <!-- Logo Header & Mobile Close Button -->
        <div class="flex items-center justify-between pb-1 px-1">
            <a href="{{ route('owner.dashboard') }}" class="inline-flex items-center bg-white px-2.5 py-1 rounded-xl shadow-xs group" title="HUNI App">
                <img src="{{ asset('images/branding/huni-logo.png') }}" alt="HUNI Logo" class="h-7 w-auto object-contain transition-transform group-hover:scale-102" />
            </a>

            <button
                type="button"
                x-on:click="mobileOpen = false"
                class="lg:hidden w-8 h-8 rounded-lg border border-[#2D323B] bg-[#1F2329] flex items-center justify-center text-[#9CA3AF] hover:text-white hover:bg-[#282D35] shadow-xs cursor-pointer"
                aria-label="Tutup menu navigasi"
            >
                <x-ui.icon name="x" class="w-4 h-4" />
            </button>
        </div>

        <!-- Property Info Selector Badge -->
        <a
            href="{{ route('owner.properties.index') }}"
            class="bg-[#1F2329] border border-[#2D323B] rounded-xl p-2.5 shadow-xs flex items-center justify-between hover:border-[#383E49] transition-all group block"
            title="Kelola Properti"
        >
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-[#282D35] text-white shrink-0 flex items-center justify-center">
                    <x-ui.icon name="building" class="w-4 h-4" />
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-xs text-white truncate leading-tight group-hover:text-[#4ADE80] transition-colors">{{ $propertyName }}</h3>
                    <p class="text-[11px] text-[#9CA3AF] truncate">{{ $propertyAddress }}</p>
                </div>
            </div>
            <x-ui.icon name="chevron-right" class="w-3.5 h-3.5 text-[#6B7280] group-hover:text-white shrink-0" />
        </a>
    </div>

    <!-- Navigation Menu (Independently scrollable on short viewports) -->
    <nav class="flex-1 overflow-y-auto space-y-0.5 py-1 pr-1 huni-scrollbar" aria-label="Owner Navigation">
        @foreach($navItems as $item)
            @php
                $isActive = $active === $item['id']
                    || ($item['id'] === 'inventory' && in_array($active, ['inventory', 'facilities'], true))
                    || ($item['id'] === 'properties' && in_array($active, ['properties', 'rooms'], true))
                    || ($item['id'] === 'bills' && in_array($active, ['bills', 'invoices'], true));
                $targetUrl = $item['route'] && \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#';
            @endphp
            <a
                href="{{ $targetUrl }}"
                class="group flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs transition-all duration-150 {{
                    $isActive
                        ? 'bg-white text-[#121417] font-bold shadow-sm'
                        : 'text-[#9CA3AF] hover:text-white hover:bg-white/5 font-medium'
                }}"
            >
                <x-ui.icon :name="$item['icon']" class="w-4 h-4 shrink-0 transition-colors {{ $isActive ? 'text-[#15803D]' : 'text-[#6B7280] group-hover:text-white' }}" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <!-- Bottom Section: Portal Status & Logout (Fixed at bottom) -->
    <div class="shrink-0 pt-3 mt-2 border-t border-[#262A30] space-y-2">
        <div class="bg-[#1F2329] border border-[#2D323B] rounded-xl px-3 py-2 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#15803D] inline-block ring-2 ring-[#15803D]/20"></span>
                <span class="text-xs font-semibold text-[#E5E7EB]">Owner Portal</span>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold text-rose-400 bg-[#1F2329] hover:bg-rose-500/10 border border-rose-500/20 hover:border-rose-500/40 rounded-xl shadow-xs transition-all cursor-pointer"
            >
                <x-ui.icon name="log-out" class="w-3.5 h-3.5" />
                <span>Keluar Akun</span>
            </button>
        </form>
    </div>
</aside>
