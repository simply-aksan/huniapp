@props([
    'as' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $tag = $href ? 'a' : $as;

    $variantClasses = match ($variant) {
        'primary' => 'bg-[#C7F36B] text-[#121417] border border-[#121417] shadow-[2px_2px_0_#121417] hover:bg-[#bbf055] hover:shadow-[1px_1px_0_#121417] active:shadow-none',
        'secondary', 'outline' => 'bg-white text-[#121417] border border-[#DEDAD1] shadow-[0_1px_2px_rgba(18,20,23,0.04)] hover:bg-[#F9F8F5] hover:border-[#121417]',
        'dark', 'black' => 'bg-[#121417] text-white border border-[#121417] shadow-[0_1px_2px_rgba(18,20,23,0.08)] hover:bg-[#20242B]',
        'accent' => 'bg-[#EFF6FF] text-[#1E40AF] border border-[#BFDBFE] hover:bg-[#DBEAFE]',
        'warning' => 'bg-[#FFFBEB] text-[#92400E] border border-[#FDE68A] hover:bg-[#FEF3C7]',
        'danger' => 'bg-[#FEF2F2] text-[#991B1B] border border-[#FECACA] hover:bg-[#FEE2E2]',
        'ghost' => 'bg-transparent text-[#474D57] hover:text-[#121417] hover:bg-black/5 border-transparent shadow-none',
        default => 'bg-[#C7F36B] text-[#121417] border border-[#121417] shadow-[2px_2px_0_#121417]',
    };

    $sizeClasses = match ($size) {
        'sm' => 'px-3 py-1.5 text-xs rounded-lg',
        'lg' => 'px-5 py-2.5 text-sm font-semibold rounded-xl',
        default => 'px-4 py-2 text-xs font-semibold rounded-xl',
    };

    $baseClasses = 'inline-flex items-center justify-center gap-2 transition-all duration-150 cursor-pointer select-none active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#121417] focus-visible:ring-offset-2';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => "{$baseClasses} {$variantClasses} {$sizeClasses}"]) }}>
    {{ $slot }}
</{{ $tag }}>
