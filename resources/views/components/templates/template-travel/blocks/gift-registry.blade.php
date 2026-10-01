@props(['section', 'context'])

{{-- #gift-section y .falling-flower-static los usa animations.js para las flores. --}}
<section class="tv-section tv-gifts" id="gift-section">
    @foreach ([['flower-top-1.png', 'top:0; left:5%;'], ['flower-top-2.png', 'top:0; right:5%;']] as [$flower, $position])
        <div class="tv-flower" style="{{ $position }} z-index: 20; transform: translateY(-10%);">
            <img src="{{ asset('images/assets-travel/flowers/' . $flower) }}" alt="">
        </div>
    @endforeach

    @foreach (['flower1' => '5%', 'flower2' => '0%', 'flower3' => '4%', 'flower5' => '12%', 'flower6' => '22%', 'flowerstep1' => '25%', 'flower8' => '36%', 'flowerstep2' => '40%', 'flower9' => '44%', 'flower10' => '48%', 'flower11' => '54%', 'flower13' => '60%', 'flower14' => '68%', 'flowerstep3' => '70%', 'flower15' => '78%', 'flower16' => '86%', 'flower17' => '92%'] as $flower => $left)
        <div class="tv-flower falling-flower-static" data-target-y="1" style="left: {{ $left }};">
            <img src="{{ asset('images/assets-travel/flowers/' . $flower . '.png') }}" alt="">
        </div>
    @endforeach

    <div class="tv-container">
        <div class="tv-gifts__content">
            <h2 class="tv-heading text-animated-opacity-split">{{ $section->get('title') }}</h2>

            @if ($section->filled('message'))
                <h3 class="tv-subheading text-animated-opacity-split">{!! nl2br(e($section->get('message'))) !!}</h3>
            @endif

            {{-- Cada opción con sus datos; las etiquetas vienen de la configuración del sistema. --}}
            @foreach ($section->get('options', []) as $option)
                <div class="tv-gifts__option">
                    @if ($option['type'])
                        <h3 class="tv-gifts__type text-animated-opacity-split">{{ $option['type'] }}</h3>
                    @endif

                    @foreach ($option['details'] as $detail)
                        <p class="tv-gifts__label">{{ $detail['label'] }}:</p>

                        @unless ($detail['copy'] ?? true)
                            {{-- Un nombre se lee, no se copia. --}}
                            <p class="tv-gifts__value">{{ $detail['value'] }}</p>
                            @continue
                        @endunless

                        {{-- Los data-* los conecta clipboard.js de esta plantilla. --}}
                        <div class="tv-copy" data-copy-group>
                            <div class="tv-copy__value" data-copy-value>{{ $detail['value'] }}</div>

                            <button type="button" class="tv-copy__button" data-copy-button
                                aria-label="Copiar {{ $detail['label'] }}">
                                <svg data-copy-icon xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none">
                                    <path fill="currentColor"
                                        d="M9 18C8.45 18 7.97917 17.8042 7.5875 17.4125C7.19583 17.0208 7 16.55 7 16V4C7 3.45 7.19583 2.97917 7.5875 2.5875C7.97917 2.19583 8.45 2 9 2H18C18.55 2 19.0208 2.19583 19.4125 2.5875C19.8042 2.97917 20 3.45 20 4V16C20 16.55 19.8042 17.0208 19.4125 17.4125C19.0208 17.8042 18.55 18 18 18H9ZM9 16H18V4H9V16ZM5 22C4.45 22 3.97917 21.8042 3.5875 21.4125C3.19583 21.0208 3 20.55 3 20V6H5V20H16V22H5Z" />
                                </svg>
                                <svg data-copy-done xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </button>

                            <div class="tv-copy__done" data-copy-message>¡Copiado!</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>
