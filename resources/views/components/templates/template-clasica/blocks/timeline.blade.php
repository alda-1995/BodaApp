@props(['section', 'context'])

@php
    $momentos = $section->get('moments', []);
@endphp

{{--
    Ceremonia y recepción (ItinerarySection).

    Cada momento es una tarjeta con sus datos escritos DENTRO, no al lado: la
    foto hace de papel y el texto se lee encima, centrado.

    Las tarjetas se alternan de lado y se montan un poco entre ellas, como en el
    diseño. El lado lo pone el CSS con :nth-child, así que añadir un tercer
    momento en el wizard sigue el mismo vaivén sin tocar nada.

    El título de la sección no se pinta: cada momento se nombra solo.
--}}
<section class="tc-timeline" id="itinerario"
    style="--tc-textura: url('{{ asset('images/assets-clasica/banda-1.jpg') }}')">
    @foreach ($momentos as $momento)
        <article class="tc-timeline__momento">
            @if ($momento['photo'] ?? null)
                <img class="tc-timeline__foto" src="{{ $momento['photo'] }}" alt="" loading="lazy">
            @endif

            <div class="tc-timeline__datos">
                @if ($momento['name'] ?? null)
                    <p class="tc-timeline__rotulo">{{ $momento['name'] }}</p>
                @endif

                @if ($momento['place'] ?? null)
                    <h2 class="tc-timeline__lugar">{{ $momento['place'] }}</h2>
                @endif

                @if ($momento['location'] ?? null)
                    <p class="tc-timeline__direccion">{{ $momento['location'] }}</p>
                @endif

                @if ($momento['time'] ?? null)
                    <p class="tc-timeline__hora">{{ $momento['time'] }}</p>
                @endif

                @if ($momento['maps'] ?? null)
                    <a class="tc-timeline__mapa" href="{{ $momento['maps'] }}" target="_blank" rel="noopener">
                        Ver en mapa
                    </a>
                @endif
            </div>
        </article>
    @endforeach
</section>
