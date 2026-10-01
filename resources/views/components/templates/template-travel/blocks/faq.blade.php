@props(['section', 'context'])

<section class="tv-section tv-section--paper">
    <div class="tv-container">
        <div class="tv-narrow tv-center" style="margin-bottom: 2rem;">
            <img src="{{ asset('images/assets-travel/monograma-footer.png') }}" alt="Monograma"
                style="max-width: 120px; margin: 0 auto 1.5rem;">
            <h2 class="tv-title text-animated-opacity-split">{{ $section->get('title') }}</h2>
        </div>

        <div class="tv-narrow" x-data="{ openIndex: 0 }">
            @foreach ($section->get('items', []) as $faq)
                <div class="tv-faq__item">
                    <button type="button" class="tv-faq__question"
                        style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');"
                        @click="openIndex === {{ $loop->iteration }} ? openIndex = null : openIndex = {{ $loop->iteration }}">
                        <span>{{ $faq['question'] }}</span>

                        <svg class="tv-faq__icon" :class="{ 'tv-faq__icon--open': openIndex === {{ $loop->iteration }} }"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>

                        <img class="tv-faq__line" src="{{ asset('images/assets-travel/line.png') }}" alt="">
                    </button>

                    <div x-show="openIndex === {{ $loop->iteration }}" x-collapse>
                        <div class="tv-faq__answer"
                            style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');">
                            {{-- Ya viene escapado y con los enlaces armados (Autolink). --}}
                            {!! $faq['content_html'] ?? nl2br(e($faq['content'])) !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
