@props([
    'as' => 'div',
    'variant' => 'white',
    'size' => 'md',
])

@php
    $bgClasses = match ($variant) {
        'white' => 'bg-white',
        'subtle' => 'bg-[#F9F8F5]',
        'cream' => 'bg-[#F9F8F5]',
        'lime' => 'bg-[#F7FEE7] border-[#BEF264]',
        'pink' => 'bg-[#FEF2F2] border-[#FECACA]',
        'yellow' => 'bg-[#FFFBEB] border-[#FDE68A]',
        'blue' => 'bg-[#EFF6FF] border-[#BFDBFE]',
        default => 'bg-white',
    };

    $sizeClasses = match ($size) {
        'sm' => 'p-4 rounded-xl shadow-[0_1px_2px_rgba(18,20,23,0.03)]',
        'lg' => 'p-6 sm:p-8 rounded-2xl shadow-[0_4px_12px_rgba(18,20,23,0.06)]',
        default => 'p-5 sm:p-6 rounded-2xl shadow-[0_1px_3px_rgba(18,20,23,0.04)]',
    };
@endphp

<{{ $as }} {{ $attributes->merge(['class' => "border border-[#DEDAD1] {$bgClasses} {$sizeClasses} transition-all"]) }}>
    {{ $slot }}
</{{ $as }}>
