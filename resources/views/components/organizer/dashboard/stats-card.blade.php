@props([
    'title' => '',
    'value' => '',
    'iconBg' => '',
    'buttonText' => null,
    'buttonAction' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg p-6 border border-[#EBEBEB] flex flex-col space-y-2']) }}>
    <div class="space-y-3">
        <div class="w-10 h-10 rounded-lg {{ $iconBg }} flex items-center justify-center font-bold text-white">
            {{ $icon ?? '' }}
        </div>
        <p class="text-parrafo font-inter text-[#666666]">
            {{ $title }}
        </p>
        <h3 class="font-inter text-size-title">{{ $value }}</h3>
    </div>
    {{--
        El botón usa lo que le pasan. Antes traía su texto escrito y sin liga,
        así que decía "Enviar recordatorios" en todas las tarjetas y no llevaba
        a ningún lado.
    --}}
    @if ($buttonText && $buttonAction)
        <x-controls.button href="{{ $buttonAction }}" variant="secondary">{{ $buttonText }}</x-controls.button>
    @endif
</div>