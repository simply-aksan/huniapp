@props([
    'title' => '',
    'label' => null,
    'value' => '',
    'icon' => null,
    'iconBg' => 'neutral',
    'badge' => null,
    'badgeVariant' => 'success',
    'subtitle' => null,
])

@php
    $displayTitle = $title ?: ($label ?? '');
@endphp

<div {{ $attributes->merge(['class' => 'bg-white border border-[#DEDAD1] rounded-2xl p-5 shadow-[0_1px_3px_rgba(18,20,23,0.04)] flex flex-col justify-between']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold text-[#767C85] tracking-wide">{{ $displayTitle }}</p>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-[#121417] mt-1 tracking-tight truncate font-mono tabular-nums">{{ $value }}</h3>
        </div>
        @if($icon)
            <div class="w-10 h-10 shrink-0 rounded-xl bg-[#F9F8F5] border border-[#EBE8E1] flex items-center justify-center text-[#121417]">
                @if(is_string($icon) && !str_contains($icon, '<'))
                    <x-ui.icon :name="$icon" class="w-5 h-5 text-[#474D57]" />
                @else
                    {!! $icon !!}
                @endif
            </div>
        @endif
    </div>

    @if($badge || $subtitle || isset($footer))
        <div class="mt-4 pt-3 border-t border-[#EBE8E1] flex items-center justify-between text-xs font-medium">
            @if($badge)
                <x-ui.badge :variant="$badgeVariant" size="sm">
                    {{ $badge }}
                </x-ui.badge>
            @endif

            @if($subtitle)
                <span class="text-[#767C85] truncate">{{ $subtitle }}</span>
            @endif

            {{ $footer ?? '' }}
        </div>
    @endif
</div>
