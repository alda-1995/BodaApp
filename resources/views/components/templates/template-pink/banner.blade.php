@props([
    'name_husband' => '',
    'name_wife' => '',
    'description' => '',
])
<div class="bg-complement100 min-h-screen flex items-center justify-center">
  <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-30 w-[340px]">
    <div class="flex flex-col items-center">
      @if ($name_husband != '' && $name_wife != '')
        <h1 class="text-size-hero font-main text-white mb-8">{{ $name_wife }} & {{ $name_husband }}</h1>
      @endif
      @if ($description != '')
        <h3 class="text-white font-main text-size-subtitle mb-8">{{ $description }}</h3>
      @endif
      <x-controls.button variant="main">Comenzar viaje</x-controls.button>
    </div>
  </div>
</div>