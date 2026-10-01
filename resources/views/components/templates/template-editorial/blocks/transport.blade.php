@props(['section', 'context'])

@php
    $title = $section->get('title');
    $mapa = $section->get('map');
    $corridas = $section->get('rides', []);
@endphp

{{--
    Transporte (TransportSection).

    El mapa ilustrado de la plantilla con el título encima, y debajo las
    corridas de una en una: sólo se lee la que toca, y se pasa con las flechas.

    El carrusel lo arma transport.js con Swiper, que viene del CDN que carga el
    layout. Si no carga, las corridas quedan en una fila que se recorre de lado:
    el CSS le da scroll horizontal, así que nunca se pierde ninguna.
--}}
@if ($corridas)
    <section class="te-transport" id="transporte">
        <div class="te-container">
            <div class="te-transport__map">
                @if ($mapa)
                    <img src="{{ $mapa }}" alt="" loading="lazy">
                @endif

                @if ($title)
                    <h2 class="te-transport__title">{{ mb_strtoupper($title) }}</h2>
                @endif
            </div>

            <div class="te-transport__rides" data-te-transport>
                <button class="te-transport__nav te-transport__nav--prev" type="button" aria-label="Corrida anterior">
                    <span aria-hidden="true">←</span>
                </button>

                <div class="te-transport__viewport swiper">
                    <div class="te-transport__track swiper-wrapper">
                        @foreach ($corridas as $corrida)
                            <article class="te-transport__ride swiper-slide">
                                @if ($corrida['time'])
                                    <p class="te-transport__time">{{ $corrida['time'] }}</p>
                                @endif

                                <p class="te-transport__route">{{ $corrida['route'] }}</p>

                                @if ($corrida['detail'])
                                    <p class="te-transport__detail">{{ $corrida['detail'] }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>

                <button class="te-transport__nav te-transport__nav--next" type="button" aria-label="Corrida siguiente">
                    <span aria-hidden="true">→</span>
                </button>
            </div>
        </div>
    </section>
@endif
