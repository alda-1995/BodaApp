@props([
    'start_date' => '',
    'end_date' => '', 
    'location' => '',
])

@php
    use Illuminate\Support\Carbon;

    $start = $start_date ? Carbon::parse($start_date)->locale('es') : null;
    $end = $end_date ? Carbon::parse($end_date)->locale('es') : null;
@endphp

<div class="bg-primary300 py-16 md:py-20 lg:py-36">
    <div class="container">
        <div class="container-center">
            <div id="card-w-animated" class="flex flex-col md:flex-row z-30 relative w-full">
                <div class="md:flex-1">
                    <div class="bg-white rounded-3xl relative">
                        <div class="flex flex-col px-8 sm:px-10 md:px-12 lg:px-16 py-8 md:py-10 lg:py-12">
                            <p class="font-mono text-size-small-heading text-complement300 mb-6">Fecha</p>

                            @if ($start)
                                <p class="font-mono text-size-small-heading text-complement300 mb-6">
                                    {{ ucfirst($start->translatedFormat('l d \d\e F, Y')) }}
                                </p>
                                <p class="font-mono text-size-small-heading text-complement300 mb-6">
                                    {{ $start->translatedFormat('g:i A') }}
                                </p>
                            @endif

                            @if ($end)
                                <p class="font-mono text-size-small-heading text-complement300 mb-6">
                                    a {{ $end->translatedFormat('g:i A') }}
                                </p>
                            @endif
                            @if ($location)
                                <p class="font-mono text-size-small-heading text-complement300 mb-6">Ubicación</p>
                                <p class="font-mono text-size-small-heading text-complement300">{{ $location }}</p>
                            @endif
                        </div>
                        <div class="bg-primary100 py-4 rounded-b-3xl px-8 sm:px-10 md:px-12 lg:px-16">
                            <p class="font-main text-complement300">WEDDING PASS</p>
                        </div>
                        <div class="hidden md:block absolute top-1/2 -translate-y-1/2 right-0 h-[90%] border-dashed border-r-2 border-r-complement300">
                        </div>
                    </div>
                </div>
                <div class="md:shrink-0 md:w-[320px]">
                    <div class="bg-white rounded-3xl w-full px-8 sm:px-10 h-full py-8 md:py-10 lg:py-12">
                        <div class="mb-6">
                            <x-icons.plane />
                        </div>
                        <p class="font-mono text-size-small-heading text-complement300 mb-6">Código de vestimenta</p>
                        <p class="font-mono text-size-small-heading text-complement300 mb-6">Formal</p>
                        <x-controls.button variant="main">Ver ubicación en el mapa</x-controls.button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>