@props([
    'title' => config('app.name', 'HUNI App'),
])

<x-layouts.base :title="$title">
    <div class="min-h-screen bg-[#121417] text-[#121417] huni-canvas-bg">
        {{ $slot }}
    </div>
</x-layouts.base>
