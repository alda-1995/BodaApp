@props(['section', 'context'])

<section class="inv-block inv-section">
    <div class="inv-container inv-narrow">
        <h2 class="inv-title inv-center">{{ $section->get('title') }}</h2>

        @if ($section->filled('message'))
            <p class="inv-text inv-center">{!! nl2br(e($section->get('message'))) !!}</p>
        @endif

        <ul class="inv-list">
            @foreach ($section->get('options', []) as $option)
                <li class="inv-card">
                    @if ($option['type'])
                        <p class="inv-card__value">{{ $option['type'] }}</p>
                    @endif

                    @foreach ($option['details'] as $detail)
                        <p class="inv-card__label">{{ $detail['label'] }}</p>
                        <p class="inv-card__value">{{ $detail['value'] }}</p>
                    @endforeach
                </li>
            @endforeach
        </ul>
    </div>
</section>
