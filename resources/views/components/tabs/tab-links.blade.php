@props([
    'tabs' => [],
    'param' => 'status',
])

@php
    $active = request($param, array_key_first($tabs));
@endphp

<div class="flex border-b border-[#dcdce5] px-4 gap-8">
    @foreach ($tabs as $key => $label)
        <a
            href="{{ request()->fullUrlWithQuery([$param => $key]) }}"
            class="flex flex-col items-center justify-center border-b-[3px] pb-[13px] pt-4
                {{ $active === $key ? 'border-b-[#111117] text-[#111117]' : 'border-b-transparent text-[#646487]' }}"
        >
            <p class="text-sm font-bold leading-normal tracking-[0.015em]">
                {{ $label }}
            </p>
        </a>
    @endforeach
</div>