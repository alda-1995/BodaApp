@props(['section', 'context'])

<section class="inv-block inv-section">
    <div class="inv-container inv-narrow">
        <h2 class="inv-title inv-center">{{ $section->get('title') }}</h2>

        @foreach ($section->get('items', []) as $faq)
            <div class="inv-faq">
                <p class="inv-faq__question">{{ $faq['question'] }}</p>
                <p class="inv-text">{!! nl2br(e($faq['content'])) !!}</p>
            </div>
        @endforeach
    </div>
</section>
