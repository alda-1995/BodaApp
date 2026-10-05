@props(['section', 'context'])

@php
    $hoteles = $section->get('hotels', []);
    $fondo = $section->get('background_image');
@endphp

{{--
    Hospedaje (HotelsSection).

    Una foto ancha y, encima, la tarjeta de cada hotel: nombre, botón de reserva
    y la nota de la tarifa. Con varios hoteles las tarjetas se ponen en fila.
--}}
@if ($hoteles)
    <section class="td-hotels" id="hospedaje">
        <div class="td-container">
            <h2 class="td-hotels__title">{{ $section->get('title') }}</h2>

            <div class="td-hotels__stage">
                @if ($fondo)
                    <img class="td-hotels__photo" src="{{ $fondo }}" alt="{{ $section->get('title') }}" loading="lazy">
                @endif

                <ul class="td-hotels__cards">
                    @foreach ($hoteles as $hotel)
                        <li class="td-hotels__card">
                            <p class="td-hotels__name">{{ $hotel['name'] }}</p>

                            @if (!empty($hotel['url']))
                                <a class="td-hotels__link" href="{{ $hotel['url'] }}" target="_blank" rel="noopener">Reserva tu estancia</a>
                            @endif

                            @if (!empty($hotel['rate']))
                                <p class="td-hotels__rate">{{ $hotel['rate'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
@endif
