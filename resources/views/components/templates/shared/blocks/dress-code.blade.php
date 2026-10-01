@props(['section', 'context'])

<section class="inv-block inv-section">
    <div class="inv-container inv-narrow">
        <h2 class="inv-title inv-center">Código de vestimenta</h2>
        <p class="inv-subtitle inv-center">{{ $section->get('type') }}</p>

        <ul class="inv-list">
            @if ($section->filled('men_attire'))
                <li class="inv-card">
                    <p class="inv-card__value">Hombres</p>
                    <p class="inv-text">{!! nl2br(e($section->get('men_attire'))) !!}</p>
                </li>
            @endif

            @if ($section->filled('women_attire'))
                <li class="inv-card">
                    <p class="inv-card__value">Mujeres</p>
                    <p class="inv-text">{!! nl2br(e($section->get('women_attire'))) !!}</p>
                </li>
            @endif
        </ul>

        @if ($section->filled('color_or_theme'))
            <p class="inv-text inv-center">Colores y tema: {{ $section->get('color_or_theme') }}</p>
        @endif

        @if ($section->filled('reference_image'))
            <img src="{{ $section->get('reference_image') }}" alt="Referencia de vestimenta">
        @endif
    </div>
</section>
