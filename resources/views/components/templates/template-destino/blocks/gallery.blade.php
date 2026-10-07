@props(['section', 'context'])

@php
    $fotos = collect($section->get('images', []))->values();
@endphp

{{--
    Galería (GallerySection).

    Una tira de fotos en escalera que sube hacia la derecha y gira en bucle. La
    segunda lista es una copia exacta de la primera: es lo que hace que al
    llegar al final empiece otra vez sin un salto visible. Va en aria-hidden
    porque no añade nada que leer.

    El escalón lo calcula gallery.js según dónde esté cada foto, no según su
    sitio en la lista: una escalera que creciera sin fin no podría dar la vuelta.

    Se puede arrastrar, y cada foto se abre en grande al tocarla (PhotoSwipe).
    Es un enlace de verdad, así que sin JS abre la imagen en el navegador.
--}}
@if ($fotos->isNotEmpty())
    <section class="td-gallery" id="galeria">
        <div class="td-gallery__viewport" data-td-gallery>
            <div class="td-gallery__marquee" data-marquesina>
                @foreach ([false, true] as $esCopia)
                    <ul class="td-gallery__strip"
                        @if ($esCopia) aria-hidden="true" data-td-gallery-copia @else data-td-gallery-original @endif>

                        @foreach ($fotos as $indice => $foto)
                            <li class="td-gallery__item">
                                <a class="td-gallery__link" href="{{ $foto }}"
                                    data-indice="{{ $indice }}"
                                    target="_blank" rel="noopener"
                                    @if ($esCopia) tabindex="-1" @endif
                                    aria-label="Ver la foto {{ $indice + 1 }} en grande">
                                    <img src="{{ $foto }}" alt="Foto de la pareja" loading="lazy">
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
    </section>
@endif
