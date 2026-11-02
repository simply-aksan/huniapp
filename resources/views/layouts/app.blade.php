@props([
    'title' => config('app.name', 'HUNI App'),
])

<x-layouts.app :title="$title">
    {{ $slot ?? '' }}
    @yield('content')
</x-layouts.app>
