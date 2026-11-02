@props([
    'variant' => null,
    'type' => null,
    'message' => null,
    'dismissible' => true,
])

@php
    $rawVariant = $variant ?? $type ?? 'info';
    $normalizedVariant = match ($rawVariant) {
        'error', 'danger' => 'danger',
        'success' => 'success',
        'warning' => 'warning',
        default => 'info',
    };

    $config = match ($normalizedVariant) {
        'success' => [
            'classes' => 'bg-[#ECFDF5] border border-[#A7F3D0] text-[#065F46]',
            'icon' => 'check',
            'iconBg' => 'bg-[#D1FAE5] text-[#065F46]',
        ],
        'danger' => [
            'classes' => 'bg-[#FEF2F2] border border-[#FECACA] text-[#991B1B]',
            'icon' => 'alert-circle',
            'iconBg' => 'bg-[#FEE2E2] text-[#991B1B]',
        ],
        'warning' => [
            'classes' => 'bg-[#FFFBEB] border border-[#FDE68A] text-[#92400E]',
            'icon' => 'alert-triangle',
            'iconBg' => 'bg-[#FEF3C7] text-[#92400E]',
        ],
        'info' => [
            'classes' => 'bg-[#EFF6FF] border border-[#BFDBFE] text-[#1E40AF]',
            'icon' => 'info',
            'iconBg' => 'bg-[#DBEAFE] text-[#1E40AF]',
        ],
    };
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="{{ $config['classes'] }} rounded-xl p-3.5 shadow-xs flex items-start justify-between gap-3 text-xs select-none"
    role="alert"
>
    <div class="flex items-start gap-2.5 min-w-0">
        <div class="w-5 h-5 rounded-md {{ $config['iconBg'] }} flex items-center justify-center shrink-0 mt-0.5">
            <x-ui.icon :name="$config['icon']" class="w-3.5 h-3.5" />
        </div>
        <div class="min-w-0 leading-relaxed font-medium">
            @if($message)
                {{ $message }}
            @else
                {{ $slot }}
            @endif
        </div>
    </div>

    @if($dismissible)
        <button
            type="button"
            x-on:click="show = false"
            class="w-5 h-5 rounded-md text-current opacity-60 hover:opacity-100 flex items-center justify-center transition-opacity cursor-pointer shrink-0"
            aria-label="Tutup notifikasi"
        >
            <x-ui.icon name="x" class="w-3.5 h-3.5" />
        </button>
    @endif
</div>
