@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'placeholder' => '',
    'error' => null,
    'icon' => null,
])

@php
    $inputId = $id ?? $name ?? 'input-' . uniqid();
    $hasError = !empty($error);
@endphp

<div class="w-full space-y-1.5">
    @if($label)
        <label for="{{ $inputId }}" class="block text-xs font-semibold text-[#121417]">
            {{ $label }}
        </label>
    @endif

    <div class="relative flex items-center">
        @if($icon)
            <div class="absolute left-3.5 pointer-events-none text-[#767C85] flex items-center justify-center">
                <x-ui.icon :name="$icon" class="w-4 h-4" />
            </div>
        @endif

        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge([
                'class' => 'w-full bg-white border ' . ($hasError ? 'border-[#DC2626] bg-[#FEF2F2]/30 focus:border-[#DC2626] focus:ring-[#DC2626]/10' : 'border-[#DEDAD1] focus:border-[#121417] focus:ring-[#121417]/10') . ' rounded-xl ' . ($icon ? 'pl-10 pr-3.5' : 'px-3.5') . ' py-2 text-[#121417] text-sm font-medium placeholder-[#8A919E] shadow-[0_1px_2px_rgba(18,20,23,0.03)] focus:outline-none focus:ring-2 transition-all'
            ]) }}
        />
    </div>

    @if($hasError)
        <p class="text-xs font-medium text-[#DC2626] flex items-center gap-1.5 mt-1">
            <x-ui.icon name="alert-circle" class="w-3.5 h-3.5 shrink-0" />
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
