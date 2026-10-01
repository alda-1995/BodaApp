@props(['section', 'context'])

@php
    $title = $section->get('title');
    $moments = $section->get('moments', []);
    $photo = $section->get('event_photo');

    /*
    | La foto acompaña a la segunda tarjeta: se monta sobre ella, corrida hacia
    | la derecha. Si no hay una segunda, no se cuelga de nada y ocupa su propia
    | celda, para que la sección se sostenga con una tarjeta, con tres o con la
    | pura foto.
    */
    $photoCell = $photo && count($moments) >= 2 ? 1 : null;
@endphp

{{--
    Ceremonia y recepción.

    Cada momento es una tarjeta de papel gris. En escritorio van en dos columnas
    y las pares bajan; la foto es hermana de su tarjeta, no hija, así que puede
    salirse hacia la derecha y quedar encima sin depender de su ancho.

    Su CSS vive aparte, en blocks/itinerary.css.
--}}
<section class="te-itinerary" id="evento">
    <div class="te-container">
        @if ($title)
            <h2 class="te-itinerary__title">{{ $title }}</h2>
        @endif

        <div class="te-itinerary__grid">
            @foreach ($moments as $moment)
                @php
                    $withPhoto = $loop->index === $photoCell;

                    // El diseño muestra la hora en 24 horas: "17:30 hrs".
                    $time = $moment['date']?->format('H:i') ?? $moment['time'];
                @endphp

                <div @class(['te-itinerary__cell', 'te-itinerary__cell--lowered' => $loop->iteration % 2 === 0])>
                    @if ($withPhoto)
                        <figure class="te-itinerary__photo">
                            <img src="{{ $photo }}" alt="{{ $moment['place'] }}" loading="lazy">
                        </figure>
                    @endif

                    <article @class(['te-itinerary__card', 'te-itinerary__card--under-photo' => $withPhoto])>
                        @if ($moment['name'])
                            <p class="te-itinerary__kicker">{{ mb_strtoupper($moment['name']) }}</p>
                        @endif

                        @if ($time)
                            <p class="te-itinerary__time">{{ $time }} hrs</p>
                        @endif

                        <h3 class="te-itinerary__place">{{ $moment['place'] }}</h3>

                        @if ($moment['location'])
                            <p class="te-itinerary__address">{{ $moment['location'] }}</p>
                        @endif

                        @if ($moment['maps'])
                            <a class="te-itinerary__map" href="{{ $moment['maps'] }}" target="_blank" rel="noopener">
                                Ver mapa →
                            </a>
                        @endif
                    </article>
                </div>
            @endforeach

            {{-- Sin segunda tarjeta de la que colgarse, la foto va sola. --}}
            @if ($photo && $photoCell === null)
                <div class="te-itinerary__cell">
                    <figure class="te-itinerary__photo te-itinerary__photo--alone">
                        <img src="{{ $photo }}" alt="{{ $title }}" loading="lazy">
                    </figure>
                </div>
            @endif
        </div>
    </div>
</section>
