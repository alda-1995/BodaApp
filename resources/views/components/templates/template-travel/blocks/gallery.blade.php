@props(['section', 'context'])

{{-- .gallerySlider y #my-gallery los inicializan sliders.js y PhotoSwipe. --}}
<section class="tv-gallery" id="galeria">
    <div class="tv-container">
        <div class="tv-gallery__intro">
            <h2 class="tv-heading tv-gallery__title text-animated-opacity-split">Nuestra galería</h2>

            @if ($section->filled('message'))
                <h3 class="tv-gallery__subtitle text-animated-opacity-split">{{ $section->get('message') }}</h3>
            @endif
        </div>

        {{-- El marco de papel es una imagen de fondo, no un color plano. --}}
        <div class="tv-gallery__frame"
            style="background-image: url('{{ asset('images/assets-travel/horizontal-background.png') }}');">
            <div class="swiper gallerySlider">
                <div class="swiper-wrapper" id="my-gallery">
                    @foreach ($section->get('images', []) as $image)
                        <div class="swiper-slide">
                            <a href="{{ $image }}" target="_blank" rel="noopener">
                                <img src="{{ $image }}" alt="Foto de la pareja">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Flechas propias: las de Swiper quedan ocultas por CSS. --}}
        <div class="tv-gallery__nav">
            <div class="swiper-button-prev prev-gallery tv-gallery__arrow">
                <img src="{{ asset('images/assets-travel/arrow-left.png') }}" alt="Anterior">
            </div>
            <div class="swiper-button-next next-gallery tv-gallery__arrow">
                <img src="{{ asset('images/assets-travel/arrow-rigth.png') }}" alt="Siguiente">
            </div>
        </div>
    </div>
</section>
