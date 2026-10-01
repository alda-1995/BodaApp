@props(['section', 'context'])

<section class="inv-block inv-section">
    <div class="inv-container">
        <h2 class="inv-title inv-center">Nuestra galería</h2>

        @if ($section->filled('message'))
            <p class="inv-text inv-center">{{ $section->get('message') }}</p>
        @endif

        <div class="inv-gallery">
            @foreach ($section->get('images', []) as $image)
                <a href="{{ $image }}" target="_blank" rel="noopener">
                    <img src="{{ $image }}" alt="Foto de la pareja">
                </a>
            @endforeach
        </div>
    </div>
</section>
