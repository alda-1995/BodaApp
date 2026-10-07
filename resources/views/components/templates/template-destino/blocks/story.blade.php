@props(['section', 'context'])

@php
    $anios = $section->get('milestones', []);

    /*
    | El giro y la caída de cada polaroid se calculan aquí y no en el CSS.
    |
    | En una tira que da vueltas no hay "primera" ni "última": el patrón se
    | repite cada cinco, que es el ritmo del diseño, así que la composición se
    | ve igual con cinco años que con nueve. Hacerlo en PHP evita depender de
    | mod() en CSS, que es muy reciente.
    */
    $PATRON = 5;
    $CENTRO = 2;

    $tarjetas = collect($anios)->values()->map(function (array $anio, int $indice) use ($PATRON, $CENTRO) {
        $posicion = $indice % $PATRON;
        $distancia = abs($posicion - $CENTRO);

        return [
            'indice' => $indice,
            'year' => $anio['year'],
            'image' => $anio['image'] ?? null,
            // Las de los lados giran más; la del centro va derecha.
            'giro' => ($posicion - $CENTRO) * 7,
            // Y caen un poco, como cartas sobre una mesa.
            'caida' => $distancia * 1.25,
        ];
    })->all();
@endphp

{{--
    Nuestra historia (MilestonesSection).

    Una tira de polaroids que gira en bucle. La segunda lista es una copia
    exacta de la primera: es lo que hace que al llegar al final empiece otra vez
    sin un salto visible. Va en aria-hidden porque no añade nada que leer.

    Cada foto se abre en grande al tocarla (PhotoSwipe, en story.js). Es un
    enlace de verdad, así que sin JS abre la imagen en el navegador.

    El movimiento se detiene al pasar el ratón o al llegar con el teclado, para
    poder tocar una foto, y no arranca si el sistema pide menos animación.
--}}
@if ($tarjetas)
    <section class="td-story" id="historia">
        <div class="td-container">
            <p class="td-story__title">{{ $section->get('title') }}</p>
        </div>

        <div class="td-story__viewport" data-td-story style="--td-story-cards: {{ count($tarjetas) }}">
            <div class="td-story__marquee">
                @foreach ([false, true] as $esCopia)
                    <ul class="td-story__track"
                        @if ($esCopia) aria-hidden="true" data-td-story-copia @else data-td-story-original @endif>

                        @foreach ($tarjetas as $tarjeta)
                            <li class="td-story__card"
                                style="--td-card-giro: {{ $tarjeta['giro'] }}deg; --td-card-caida: {{ $tarjeta['caida'] }}rem">
                                <span class="td-story__year">{{ $tarjeta['year'] }}</span>

                                @if ($tarjeta['image'])
                                    <a class="td-story__link" href="{{ $tarjeta['image'] }}"
                                        data-indice="{{ $tarjeta['indice'] }}"
                                        target="_blank" rel="noopener"
                                        @if ($esCopia) tabindex="-1" @endif
                                        aria-label="Ver la foto de {{ $tarjeta['year'] }} en grande">
                                        <img class="td-story__photo" src="{{ $tarjeta['image'] }}"
                                            alt="{{ $tarjeta['year'] }}" loading="lazy">
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
    </section>
@endif
