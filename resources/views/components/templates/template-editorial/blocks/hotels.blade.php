@props(['section', 'context'])

@php
    $title = $section->get('title');
    $hoteles = $section->get('hotels', []);
@endphp

{{--
    Hoteles recomendados (HotelsSection).

    Una tarjeta por hotel: su foto, su nombre y, en letra chica, la dirección y
    la tarifa con el código de reservación. La tarifa y el código van a la vista
    porque son lo que más pregunta quien viene de fuera.
--}}
@if ($hoteles)
    <section class="te-hotels" id="hoteles">
        <div class="te-container">
            @if ($title)
                <h2 class="te-hotels__title">{{ mb_strtoupper($title) }}</h2>
            @endif

            <div class="te-hotels__grid">
                @foreach ($hoteles as $hotel)
                    <article class="te-hotels__card">
                        @if ($hotel['image'])
                            <div class="te-hotels__photo">
                                <img src="{{ $hotel['image'] }}" alt="{{ $hotel['name'] }}" loading="lazy">
                            </div>
                        @endif

                        <div class="te-hotels__body">
                            <h3 class="te-hotels__name">{{ $hotel['name'] }}</h3>

                            @if ($hotel['address'])
                                <p class="te-hotels__line">{{ $hotel['address'] }}</p>
                            @endif

                            @php
                                // Tarifa y código van juntos en una línea, y
                                // cualquiera de los dos puede faltar.
                                $datos = array_filter([
                                    $hotel['rate'] ? 'Tarifa: ' . $hotel['rate'] : null,
                                    $hotel['code'] ? 'Código: ' . $hotel['code'] : null,
                                ]);
                            @endphp

                            @if ($datos)
                                <p class="te-hotels__line">{{ implode(' · ', $datos) }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
