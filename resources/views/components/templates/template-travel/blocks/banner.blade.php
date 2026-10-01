@props(['section', 'context'])

@php
    // Ceremonia y fiesta salen del itinerario (ver GeneralSection).
    $ceremony = $section->get('ceremony');
    $party = $section->get('party');
@endphp

{{-- Los ids son los que animan banner.js y el contador de app.js. --}}
{{-- La secuencia del sobre la declara banner.manifest.php. --}}
<section class="tv-banner" id="scroll-animation-container" data-frames="{{ $section->get('frames_card') }}">
    @if ($context->guest)
        <div class="tv-banner__guest">
            <div class="tv-container">
                <div class="tv-narrow">
                    <p class="tv-banner__guest-label">Para:</p>
                    <h3 class="tv-banner__guest-name">{{ $context->guest->name }}</h3>
                </div>
            </div>
        </div>
    @endif

    @if ($context->eventDate)
        {{-- La fecha viaja en el HTML; la lee countdown.js de esta plantilla. --}}
        <div class="tv-countdown" id="countdown-container"
            data-countdown="{{ $context->eventDate->toIso8601String() }}">
            <h2 class="tv-countdown__label">Faltan:</h2>
            <div class="tv-countdown__items" id="timer">
                @foreach (['days' => 'Días', 'hours' => 'Horas', 'minutes' => 'Minutos', 'seconds' => 'Segundos'] as $id => $label)
                    <div class="tv-countdown__item">
                        <span class="tv-countdown__value" id="{{ $id }}">0</span>
                        <span class="tv-countdown__unit">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="tv-banner__canvas" id="canvas-wrapper">
        <div class="tv-container tv-banner__canvas-inner">
            <canvas id="animation-canvas"></canvas>
        </div>

        <div class="tv-scroll-hint">
            <span class="scroll-indicator-animation">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    style="transform: rotate(180deg);">
                    <path d="M18 15C18 15 13.58 21 12 21C10.418 21 6 15 6 15M18 3C18 3 13.58 9 12 9C10.418 9 6 3 6 3"
                        stroke="#8B8494" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M18 15C18 15 13.58 21 12 21C10.418 21 6 15 6 15M18 3C18 3 13.58 9 12 9C10.418 9 6 3 6 3"
                        stroke="black" stroke-opacity="0.2" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </span>
            <span>Deslizar</span>
        </div>
    </div>

    <div class="tv-container">
        <div class="tv-banner__card" id="card-info">
            <div class="tv-banner__paper" id="card-info-content"
                style="background-image: url('{{ asset('images/assets-travel/background-paper.jpg') }}');">
                <div class="tv-banner__inner">
                    <img class="tv-banner__mark" src="{{ asset('images/assets-travel/mark2.png') }}" alt="Monograma">

                    <h1 class="tv-banner__names">
                        {{ $context->wifeName }} <span>&</span> {{ $context->husbandName }}
                    </h1>

                    @if ($context->parents)
                        <h3 class="tv-banner__blessing">Con la bendición de Dios y nuestras Familias</h3>
                        <div class="tv-banner__parents">{!! nl2br(e($context->parents)) !!}</div>
                    @endif

                    <h3 class="tv-banner__blessing tv-banner__blessing--spaced">
                        Te invitamos a compartir este día especial en el que iniciamos nuestro camino juntos.
                    </h3>

                    @if ($context->eventDate)
                        <p class="tv-banner__date">{{ $context->formattedDate() }}</p>
                    @endif

                    @if ($ceremony)
                        @if ($ceremony['time'])
                            <p class="tv-moment__time">{{ $ceremony['time'] }}</p>
                        @endif

                        <p class="tv-moment__name">{{ $ceremony['name'] }}</p>
                        <h3 class="tv-moment__place">{{ $ceremony['place'] }}</h3>

                        @if ($ceremony['location'])
                            <p class="tv-moment__location">{{ $ceremony['location'] }}</p>
                        @endif

                        @if ($ceremony['maps'])
                            <a class="tv-link tv-moment__map" href="{{ $ceremony['maps'] }}" target="_blank"
                                rel="noopener">ver ubicación</a>
                        @endif
                    @endif

                    @if ($party)
                        @if ($party['time'])
                            <h3 class="tv-moment__time tv-moment__time--spaced">{{ $party['time'] }}</h3>
                        @endif

                        <p class="tv-moment__name">{{ $party['name'] }}</p>
                        <h3 class="tv-moment__place">{{ $party['place'] }}</h3>

                        @if ($party['location'])
                            <p class="tv-moment__location">{!! nl2br(e($party['location'])) !!}</p>
                        @endif

                        @if ($party['maps'])
                            <a class="tv-link tv-moment__map tv-moment__map--spaced" href="{{ $party['maps'] }}"
                                target="_blank" rel="noopener">ver ubicación</a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
