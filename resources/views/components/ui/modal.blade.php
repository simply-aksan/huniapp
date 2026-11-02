@props([
    'name',
    'title' => null,
    'maxWidth' => '2xl',
])

@php
    $maxWidthClass = match ($maxWidth) {
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        default => 'sm:max-w-2xl',
    };
@endphp

<div
    x-data="{ show: false }"
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
    aria-labelledby="{{ $name }}-title"
    role="dialog"
    aria-modal="true"
>
    <!-- Backdrop Overlay -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="show = false"
        class="fixed inset-0 bg-[#121417]/50 backdrop-blur-xs transition-opacity"
    ></div>

    <!-- Modal Box (Precision Card) -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-95"
        class="relative w-full {{ $maxWidthClass }} bg-white border border-[#DEDAD1] rounded-2xl shadow-[0_20px_25px_-5px_rgba(18,20,23,0.1),0_8px_10px_-6px_rgba(18,20,23,0.06)] overflow-hidden transform transition-all z-10"
    >
        @if($title)
            <div class="px-6 py-4 border-b border-[#EBE8E1] bg-[#F9F8F5] flex items-center justify-between">
                <h3 id="{{ $name }}-title" class="text-base font-bold text-[#121417] tracking-tight">
                    {{ $title }}
                </h3>
                <button
                    type="button"
                    x-on:click="show = false"
                    class="w-8 h-8 rounded-lg text-[#767C85] hover:text-[#121417] hover:bg-[#EBE8E1] flex items-center justify-center transition-colors cursor-pointer"
                    aria-label="Tutup modal"
                >
                    <x-ui.icon name="x" class="w-4 h-4" />
                </button>
            </div>
        @endif

        <div class="p-6">
            {{ $slot }}
        </div>
    </div>
</div>
