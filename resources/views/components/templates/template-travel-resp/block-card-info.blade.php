<section class="relative" id="scroll-animation-container">
    @if ($guestData)
        <div class="item-name">
            <div class="container">
                <div class="max-w-xl mx-auto">
                    <p class="text-parrafo font-main text-accent">Para:</p>
                    <h3 class="font-main text-size-subtitle text-accent">{{ $guestData->name ?? '' }}</h3>
                </div>
            </div>
        </div>
    @endif
    <div class="absolute top-[2%] right-[5%]">
        <div id="countdown-container" class="flex flex-col">
            <h2 class="text-accent text-parrafo parrafo font-main">Faltan:</h2>
            <div id="timer" class="flex items-center space-x-2 sm:space-x-4">
                <!-- Días -->
                <div class="flex flex-col items-center flex-1">
                    <span id="days" class="text-main text-size-heading font-main bg-white rounded-[20px] px-4">0</span>
                    <span class="mt-2 text-accent font-main text-parrafo parrafo">Días</span>
                </div>

                <!-- Horas -->
                <div class="flex flex-col items-center flex-1">
                    <span id="hours" class="text-main text-size-heading font-main bg-white rounded-[20px] px-4">0</span>
                    <span class="mt-2 text-accent font-main text-parrafo parrafo">Horas</span>
                </div>

                <!-- Minutos -->
                <div class="flex flex-col items-center flex-1">
                    <span id="minutes"
                        class="text-main text-size-heading font-main bg-white rounded-[20px] px-4">0</span>
                    <span class="mt-2 text-accent font-main text-parrafo parrafo">Minutos</span>
                </div>

                <!-- Segundos -->
                <div class="flex flex-col items-center flex-1">
                    <span id="seconds"
                        class="text-main text-size-heading font-main bg-white rounded-[20px] px-4">0</span>
                    <span class="mt-2 text-accent font-main text-parrafo parrafo">Segundos</span>
                </div>
            </div>
        </div>
    </div>
    <div id="canvas-wrapper" class="absolute top-0 left-0 w-full h-screen overflow-hidden">
        <div class="container relative z-[2]">
            <canvas id="animation-canvas" class="max-w-full w-full h-screen scale-200 md:scale-100"></canvas>
        </div>
        <div class="absolute bottom-[10%] md:bottom-[5%] left-1/2 -translate-x-1/2 flex flex-col items-center">
            <span class="scroll-indicator-animation">
                <svg class="rotate-180" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M18 15C18 15 13.58 21 12 21C10.418 21 6 15 6 15M18 3C18 3 13.58 9 12 9C10.418 9 6 3 6 3"
                        stroke="#8B8494" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M18 15C18 15 13.58 21 12 21C10.418 21 6 15 6 15M18 3C18 3 13.58 9 12 9C10.418 9 6 3 6 3"
                        stroke="black" stroke-opacity="0.2" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </span>
            <span class="inline-block text-accent text-parrafo parrafo font-main">Deslizar</span>
        </div>
    </div>
    <div class="container">
        @if ($eventData)
            <div id="card-info" class="max-w-xl mx-auto relative z-10 pt-[10vh] opacity-0 translate-y-[20%] scale-y-[.8]">
                <div id="card-info-content"
                    class="min-h-[500px] overflow-hidden p-8 md:p-10 lg:p-12 box-shadow-card bg-top bg-cover bg-no-repeat"
                    style="background-image: url('{{ asset('images/assets-travel/background-paper.jpg') }}');">
                    <div class="pb-20">
                        <div class="flex flex-col items-center text-center">
                            <div class="mb-6 max-w-[100px] mx-auto">
                                <img src="{{ asset('images/assets-travel/mark2.png') }}" class="max-w-full" alt="img marca">
                            </div>
                            <h1 class="text-main text-size-hero font-antura mb-4">{{ $eventData->name_wife ?? '' }} <span
                                    class="inline-block">&</span>
                                {{ $eventData->name_husband ?? '' }}
                            </h1>
                            @if($eventData->parents)
                                <h3 class="text-parrafo parrafo font-main text-accent mb-4">Con la bendición de Dios y nuestras
                                    Familias</h3>
                                <div class="text-size-subtitle font-antura text-main mb-4">
                                    {!! nl2br(e($eventData->parents)) !!}
                                </div>
                            @endif
                            <h3 class="text-parrafo parrafo font-main text-accent mt-6 mb-4">Te invitamos a compartir este
                                día
                                especial en el que iniciamos nuestro camino juntos.</h3>
                            <p class="font-main text-size-main text-main mb-8 uppercase">
                                {{ $eventData->start_date_only->locale('es')->isoFormat('D \d\e MMMM \d\e YYYY') }}
                            </p>
                            <p class="text-size-subtitle text-main font-main mb-4">
                                {{ \Carbon\Carbon::parse($eventData->start_time_only)->isoFormat('h:mm a') }}
                            </p>
                            <p class="text-parrafo parrafo font-main text-main mb-4">Ceremonia Religiosa</p>
                            <h3 class="text-size-subtitle text-main font-antura mb-4 line-max">
                                {{ $eventData->ceremony_place ?? '' }}
                            </h3>
                            <p class="text-accent text-parrafo parrafo font-main mb-4">
                                {{ $eventData->location_ceremony ?? '' }}
                            </p>
                            <a href="{{ $eventData->location_google_maps_ceremony ?? '' }}" target="_blank"
                                class="text-size-small-heading btn btn-ouline-main font-aboreto mb-6">ver
                                ubicación</a>
                            @if ($eventData->start_time_only_civil)
                                <h3 class="text-size-subtitle font-main text-main mt-8 mb-4">
                                    {{ \Carbon\Carbon::parse($eventData->start_time_only_civil)->isoFormat('h:mm a') }}
                                </h3>
                            @endif
                            @if ($eventData->civil_place)
                                <p class="font-main parrafo text-parrafo text-main mb-4">Ceremonia Civil y Fiesta</p>
                                <h3 class="font-antura text-main text-size-subtitle mb-4 line-max">
                                    {{ $eventData->civil_place ?? '' }}
                                </h3>
                                @if ($eventData->location_civil)
                                    <p class="text-accent parrafo text-parrafo font-main">{!! nl2br(e($eventData->location_civil)) !!}</p>
                                @endif
                                @if ($eventData->location_google_maps_civil)
                                    <a href="{{ $eventData->location_google_maps_civil ?? '' }}" target="_blank"
                                        class="text-size-small-heading btn btn-ouline-main font-aboreto mt-6">ver
                                        ubicación</a>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const targetDateString = "{{ $eventData->start_date_only->format('Y-m-d') }}T{{ $eventData->start_time_only }}";
            startCountdown(targetDateString);
        });
    </script>
@endpush