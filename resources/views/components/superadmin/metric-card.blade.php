@props(['label', 'value'])

{{--
    Una cifra del panel: su rótulo, el número grande y, opcionalmente, una
    línea de contexto debajo (la comparación, el desglose, la aclaración).
--}}
<div class="rounded-xl border border-gray-200 bg-white p-5">
    <p class="text-xs text-[#808080]">{{ $label }}</p>
    <p class="mt-1 text-2xl text-black">{{ $value }}</p>

    @if (trim($slot) !== '')
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
