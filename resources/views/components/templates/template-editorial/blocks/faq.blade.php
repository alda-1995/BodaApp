@props(['section', 'context'])

@php
    $preguntas = $section->get('items', []);
@endphp

{{--
    Preguntas frecuentes (FaqSection).

    Un acordeón: cada pregunta en su barra y la respuesta se despliega al
    tocarla. Lo abre Alpine, que ya carga el layout de la invitación junto con
    su plugin de colapso.

    Las respuestas vienen con sus correos, teléfonos y ligas ya convertidos en
    enlaces (Autolink), por eso se imprimen como HTML.
--}}
@if ($preguntas)
    <section class="te-faq" id="preguntas">
        <div class="te-container">
            <h2 class="te-faq__title">Preguntas frecuentes</h2>

            <div class="te-faq__list" x-data="{ abierta: null }">
                @foreach ($preguntas as $faq)
                    <div class="te-faq__item">
                        <button class="te-faq__question" type="button"
                            @click="abierta = abierta === {{ $loop->index }} ? null : {{ $loop->index }}"
                            :aria-expanded="abierta === {{ $loop->index }}">
                            <span>{{ $faq['question'] }}</span>

                            {{-- El mismo signo gira 45° y se vuelve una equis. --}}
                            <svg class="te-faq__sign" :class="{ 'is-open': abierta === {{ $loop->index }} }"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                        </button>

                        <div class="te-faq__answer" x-show="abierta === {{ $loop->index }}" x-collapse x-cloak>
                            <div class="te-faq__answer-inner">
                                {!! $faq['content_html'] ?? nl2br(e($faq['content'])) !!}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
