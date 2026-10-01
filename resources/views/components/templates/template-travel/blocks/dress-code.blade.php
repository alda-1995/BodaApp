@props(['section', 'context'])

<section class="tv-section tv-section--purple">
    <div class="tv-container">
        <div class="tv-dress__layout">
            <div class="tv-dress__image">
                <img src="{{ $section->get('reference_image') ?: asset('images/assets-travel/shoes.png') }}"
                    alt="Código de vestimenta">
            </div>

            <div class="tv-dress__content">
                <h3 class="tv-subheading text-animated-opacity-split">Código de vestimenta</h3>
                <h2 class="tv-heading text-animated-opacity-split" style="margin-bottom: 2rem;">
                    {{ $section->get('type') }}
                </h2>

                <div class="tv-dress__grid">
                    @if ($section->filled('men_attire'))
                        <div>
                            <h2 class="tv-dress__who text-animated-opacity-split">Hombres</h2>
                            <p class="tv-dress__detail">{!! nl2br(e($section->get('men_attire'))) !!}</p>
                        </div>
                    @endif

                    @if ($section->filled('women_attire'))
                        <div>
                            <h2 class="tv-dress__who text-animated-opacity-split">Mujeres</h2>
                            <p class="tv-dress__detail">{!! nl2br(e($section->get('women_attire'))) !!}</p>
                        </div>
                    @endif
                </div>

                @if ($section->filled('color_or_theme'))
                    <div class="tv-dress__theme">
                        <p class="tv-text">Colores y tema de la boda:</p>
                        <h3 class="tv-dress__who">{{ $section->get('color_or_theme') }}</h3>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
