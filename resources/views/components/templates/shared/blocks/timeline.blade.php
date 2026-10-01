@props(['section', 'context'])

<section class="inv-block inv-section">
    <div class="inv-container inv-narrow">
        <h2 class="inv-title inv-center">Itinerario</h2>

        <ul class="inv-list">
            @foreach ($section->get('moments', []) as $moment)
                <li class="inv-card">
                    <p class="inv-card__label">{{ $moment['time'] }}</p>
                    <p class="inv-card__value">{{ $moment['name'] }}</p>
                    <p class="inv-text">{{ $moment['place'] }}</p>

                    @if ($moment['location'])
                        <p class="inv-text">{{ $moment['location'] }}</p>
                    @endif

                    @if ($moment['maps'])
                        <a class="inv-btn" href="{{ $moment['maps'] }}" target="_blank" rel="noopener">Ver ubicación</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
