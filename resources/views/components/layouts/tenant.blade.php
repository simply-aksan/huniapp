@props([
    'title' => 'HUNI — Tenant Portal',
    'activeNav' => 'dashboard',
])

<x-layouts.base :title="$title">
    <div x-data="{ mobileOpen: false }" class="h-screen flex bg-[#121417] text-[#171717] overflow-hidden">
        <!-- Tenant Sidebar Navigation -->
        <x-tenant.sidebar :active="$activeNav" />

        <!-- Main Content Column -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
            <!-- Tenant Topbar -->
            <x-tenant.topbar class="shrink-0" />

            <!-- Page Body -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto huni-canvas-bg huni-scrollbar relative">
                <div class="max-w-6xl mx-auto space-y-6">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</x-layouts.base>
