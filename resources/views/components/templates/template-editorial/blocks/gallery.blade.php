@props(['section', 'context'])

@php
    $images = $section->get('images', []);
    $background = $section->get('background_image');
@endphp

{{--
    Galería (EditorialGallerySection).

    Un carrusel: la foto del centro va limpia y las de los lados llevan encima
    la plasta del diseño. El fondo de la sección es opcional: si la pareja sube
    una imagen, va debajo de esa misma plasta; si no, queda el color de su
    paleta.

    El carrusel lo arma gallery.js con Swiper, que viene del CDN que carga el
    layout. Si no carga, las fotos siguen ahí y se recorren de lado: el CSS le
    da scroll horizontal, así que la sección nunca queda rota ni vacía.

    Cada foto va dentro de un enlace a sí misma: al hacer clic se abre grande en
    un visor (PhotoSwipe, en gallery.js). Es un enlace de verdad y no un div con
    onclick, así que sin JS sigue sirviendo —abre la imagen en el navegador— y
    se puede llegar a él con el teclado.
--}}
@if ($images)
    {{-- Sin imagen, el fondo se queda en negro: lo resuelve el CSS. --}}
    <section class="te-gallery" id="galeria"
        @if ($background) style="--te-gallery-bg-image: url('{{ $background }}')" @endif>

        <div class="te-gallery__carousel" data-te-gallery>
            <div class="te-gallery__viewport swiper">
                <div class="te-gallery__track swiper-wrapper">
                    @foreach ($images as $image)
                        <figure class="te-gallery__slide swiper-slide">
                            <a class="te-gallery__link" href="{{ $image }}" target="_blank" rel="noopener"
                                aria-label="Ver la foto {{ $loop->iteration }} en grande">
                                <img src="{{ $image }}" alt="Foto de la pareja" loading="lazy">
                            </a>
                        </figure>
                    @endforeach
                </div>
            </div>

            {{--
                Los puntos van FUERA del carrusel: dentro, Swiper los coloca en
                absoluto sobre las fotos y la del centro, que es más grande, los
                tapaba. Sin Swiper este div se queda vacío y no ocupa nada.
            --}}
            <div class="te-gallery__dots"></div>
        </div>
    </section>
@endif
