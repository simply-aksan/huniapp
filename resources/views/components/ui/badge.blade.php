@props([
    'variant' => 'neutral',
    'size' => 'md',
])

@php
    $variantClasses = match ($variant) {
        'success', 'lunas', 'aktif', 'emerald' => 'bg-[#ECFDF5] text-[#065F46] border border-[#A7F3D0]',
        'danger', 'terlambat', 'belum_dibayar', 'dibatalkan', 'rose' => 'bg-[#FEF2F2] text-[#991B1B] border border-[#FECACA]',
        'warning', 'menunggu', 'draft', 'amber' => 'bg-[#FFFBEB] text-[#92400E] border border-[#FDE68A]',
        'info', 'diproses', 'perbaikan', 'selesai', 'blue' => 'bg-[#EFF6FF] text-[#1E40AF] border border-[#BFDBFE]',
        'purple' => 'bg-[#FAF5FF] text-[#6B21A8] border border-[#E9D5FF]',
        'peach' => 'bg-[#FFF7ED] text-[#9A3412] border border-[#FFEDD5]',
        default => 'bg-[#F3F1EC] text-[#474D57] border border-[#DEDAD1]',
    };

    $sizeClasses = match ($size) {
        'sm' => 'px-2 py-0.5 text-[10px]',
        default => 'px-2.5 py-0.5 text-xs',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 font-semibold rounded-full {$variantClasses} {$sizeClasses} leading-tight tracking-tight select-none"]) }}>
    {{ $slot }}
</span>
