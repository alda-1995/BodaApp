@props([
    'percentage' => 0,
    'message' => 'Completa la configuración de tu evento',
    'linkText' => 'Completar configuración',
    'linkUrl' => '#',
])

<div {{ $attributes->merge(['class' => 'bg-[#F5E8D1] rounded-lg p-6 border border-[#EBEBEB] flex flex-col space-y-2']) }}>
    <div class="space-y-2">
        <h4 class="text-parrafo font-inter">
            Estás al {{ $percentage }}%
        </h4>
        <p class="text-parrafo font-inter">
            {{ $message }}
        </p>
    </div>
    <div class="block">
        <x-controls.button class="!px-2" variant="cancel" href="{{ $linkUrl }}">{{ $linkText }}</x-controls.button>
    </div>
</div>