@props(['section', 'context'])

@php
    $fotos = $section->get('images', []);
@endphp

{{--
    Galería (GallerySection).

    Las fotos bajan en escalera sobre negro, como una tira de contactos. El
    escalón lo calcula el CSS a partir del índice, así que la diagonal se
    mantiene con las fotos que haya.

    No es un carrusel: la tira se recorre de lado con scroll, que funciona sin
    JS. Cada foto es un enlace a sí misma, así que al hacer clic se abre grande
    en el visor (PhotoSwipe, en gallery.js) y, sin JS, en el navegador.
--}}
@if ($fotos)
    <section class="td-gallery" id="galeria">
        <ul class="td-gallery__strip" data-td-gallery>
            @foreach ($fotos as $foto)
                <li class="td-gallery__item" style="--td-card-index: {{ $loop->index }}">
                    <a class="td-gallery__link" href="{{ $foto }}" target="_blank" rel="noopener"
                        aria-label="Ver la foto {{ $loop->iteration }} en grande">
                        <img src="{{ $foto }}" alt="Foto de la pareja" loading="lazy">
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
