@props(['section', 'context'])

@php
    $momentos = $section->get('moments', []);
    $foto = $section->get('event_photo');
@endphp

{{--
    Ceremonia y recepción (ItinerarySection).

    Dos mitades: la foto del destino a la izquierda, de borde a borde; los
    momentos a la derecha, uno bajo otro.

    Cada momento lleva su propio título —"Ceremonia", "Recepción"— porque son
    actos distintos, con su hora y su lugar. El título de la sección es
    opcional: si cada momento ya se nombra, repetirlo arriba sobra.

    Los datos de viaje del destino (el clima, la hora local) no se pintan aquí:
    vienen rotulados en la propia foto.
--}}
<section class="td-timeline" id="itinerario">
    <div class="td-timeline__media">
        @if ($foto)
            <img class="td-timeline__photo" src="{{ $foto }}" alt="Itinerario" loading="lazy">
        @endif
    </div>

    <div class="td-timeline__body">
        <ol class="td-timeline__list">
            @foreach ($momentos as $momento)
                <li class="td-timeline__moment">
                    @if (!empty($momento['name']))
                        <h2 class="td-timeline__title">{{ $momento['name'] }}</h2>
                    @endif
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
