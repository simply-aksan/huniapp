@props([
    'icon' => 'box',
    'color' => 'yellow',
    'title' => 'Belum Ada Data',
    'description' => null,
    'actionText' => null,
    'actionUrl' => null,
    'resetText' => null,
    'resetUrl' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white border border-[#DEDAD1] rounded-2xl p-8 sm:p-12 text-center shadow-[0_1px_3px_rgba(18,20,23,0.04)] max-w-lg mx-auto']) }}>
    <!-- Icon Badge -->
    <div class="w-14 h-14 rounded-2xl bg-[#F9F8F5] border border-[#EBE8E1] mx-auto flex items-center justify-center mb-4 text-[#121417]">
        <x-ui.icon :name="$icon" class="w-7 h-7" />
    </div>

    <!-- Title & Description -->
    <h3 class="text-lg sm:text-xl font-bold text-[#121417] tracking-tight">
        {{ $title }}
    </h3>

    @if($description)
        <p class="text-xs sm:text-sm text-[#767C85] mt-1.5 max-w-sm mx-auto leading-relaxed">
            {{ $description }}
        </p>
    @endif

    {{ $slot }}

    <!-- Actions -->
    @if($actionUrl || $resetUrl)
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            @if($resetUrl)
                <a href="{{ $resetUrl }}" class="px-4 py-2 rounded-xl bg-white border border-[#DEDAD1] font-semibold text-xs text-[#121417] shadow-xs hover:bg-[#F9F8F5] transition-all cursor-pointer">
                    {{ $resetText ?? 'Reset Filter' }}
                </a>
            @endif

            @if($actionUrl && $actionText)
                <a href="{{ $actionUrl }}" class="px-5 py-2.5 rounded-xl bg-[#C7F36B] border border-[#121417] font-semibold text-xs text-[#121417] shadow-[2px_2px_0_#121417] hover:bg-[#bbf055] hover:shadow-[1px_1px_0_#121417] active:scale-[0.98] transition-all cursor-pointer inline-flex items-center gap-1.5">
                    <span>+</span>
                    <span>{{ $actionText }}</span>
                </a>
            @endif
        </div>
    @endif
</div>
