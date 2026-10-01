@props(['section', 'context'])

{{-- Un momento por diapositiva; las anima banner.js con #time-canvas-weeding. --}}
<section class="tv-timeline" id="time-canvas-weeding"
    data-frames="{{ $section->get('frames_time') }}"
    data-frames-mobile="{{ $section->get('frames_time_mobile') }}">
    <div class="tv-timeline__canvas">
        <canvas id="animation-canvas-time"></canvas>
    </div>

    @foreach ($section->get('moments', []) as $moment)
        <div class="slides-time">
            <div class="tv-container">
                <div class="tv-narrow">
                    @if ($moment['time'])
                        <h3 class="tv-timeline__time">{{ $moment['time'] }}</h3>
                    @endif

                    <h2 class="tv-timeline__name">{{ $moment['name'] }}</h2>
                    <p class="tv-timeline__place">{{ $moment['place'] }}</p>

                    @if ($moment['location'])
                        <p class="tv-timeline__location">{{ $moment['location'] }}</p>
                    @endif

                    @if ($moment['maps'])
                        <a class="tv-link" href="{{ $moment['maps'] }}" target="_blank" rel="noopener">
                            ver ubicación
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</section>
