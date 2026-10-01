@props(['section', 'context'])

{{-- Bloque de respaldo: lo usa cualquier plantilla que no traiga su propio banner. --}}
<section class="inv-block inv-banner inv-section">
    <div class="inv-container inv-narrow">
        @if ($context->guest)
            <p class="inv-guest">Para: {{ $context->guest->name }}</p>
        @endif

        <h1 class="inv-banner__names">{{ $context->coupleNames() }}</h1>

        @if ($context->parents)
            <p class="inv-text">{!! nl2br(e($context->parents)) !!}</p>
        @endif

        @if ($context->eventDate)
            <p class="inv-banner__date">{{ $context->formattedDate() }}</p>
        @endif

        @foreach (array_filter([$section->get('ceremony'), $section->get('party')]) as $moment)
            <div class="inv-card" style="margin-top: 1.5rem;">
                <p class="inv-card__label">{{ $moment['time'] }} · {{ $moment['name'] }}</p>
                <p class="inv-card__value">{{ $moment['place'] }}</p>
                <p class="inv-text">{{ $moment['location'] }}</p>

                @if ($moment['maps'])
                    <a class="inv-btn" href="{{ $moment['maps'] }}" target="_blank" rel="noopener">Ver ubicación</a>
                @endif
            </div>
        @endforeach
    </div>
</section>
