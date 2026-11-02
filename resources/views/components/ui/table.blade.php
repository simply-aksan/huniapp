@props([
    'headers' => [],
])

<div class="w-full overflow-hidden bg-white border border-[#DEDAD1] rounded-2xl shadow-[0_1px_3px_rgba(18,20,23,0.04)]">
    <div class="overflow-x-auto huni-scrollbar">
        <table class="w-full text-left border-collapse">
            @if(count($headers) > 0)
                <thead>
                    <tr class="bg-[#F9F8F5] border-b border-[#EBE8E1] text-[#767C85] text-[11px] font-semibold uppercase tracking-wider">
                        @foreach($headers as $header)
                            <th scope="col" class="px-5 py-3 whitespace-nowrap">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-[#EBE8E1] text-xs text-[#121417]">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
