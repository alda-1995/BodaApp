@props(['section', 'context'])

@php
    $momentos = $section->get('moments', []);
    $foto = $section->get('event_photo');
@endphp

{{--
    Ceremonia y recepción (ItinerarySection).

    Dos mitades: la foto del destino a la izquierda, de borde a borde, con el
    clima y la hora local encima; los momentos a la derecha, uno bajo otro.
--}}
<section class="td-timeline" id="itinerario">
    <div class="td-timeline__media">
        @if ($foto)
            <img class="td-timeline__photo" src="{{ $foto }}" alt="{{ $section->get('title') }}" loading="lazy">
        @endif

        @if ($section->filled('weather') || $section->filled('timezone_label'))
            <div class="td-timeline__facts">
                @if ($section->filled('weather'))
                    <p class="td-timeline__weather">{{ $section->get('weather') }}</p>
                @endif

                @if ($section->filled('timezone_label'))
                    <p class="td-timeline__zone">{{ $section->get('timezone_label') }}</p>
                @endif
            </div>
        @endif
    </div>

    <div class="td-timeline__body">
        <h2 class="td-timeline__title">{{ $section->get('title') }}</h2>

        <ol class="td-timeline__list">
            @foreach ($momentos as $momento)
                <li class="td-timeline__moment">
                    {{--
                        El catálogo da la hora en 12 horas ("5:30 pm"); este
                        diseño la escribe en 24 ("17:30 hrs"), así que se arma
                        aquí desde la fecha en vez de cambiarla para todos.
                    --}}
                    @if (!empty($momento['date']))
                        <p class="td-timeline__time">{{ $momento['date']->format('H:i') }} hrs</p>
                    @endif

                    @if (!empty($momento['place']))
                        <p class="td-timeline__place">{{ $momento['place'] }}</p>
                    @endif

                    @if (!empty($momento['location']))
                        <p class="td-timeline__detail">{{ $momento['location'] }}</p>
                    @endif

                    @if (!empty($momento['maps']))
                        <a class="td-timeline__map" href="{{ $momento['maps'] }}" target="_blank" rel="noopener">Ver mapa →</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</section>
