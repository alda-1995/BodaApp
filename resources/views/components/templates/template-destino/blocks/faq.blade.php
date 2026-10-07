@props(['section', 'context'])

@php
    $preguntas = $section->get('items', []);
    $foto = $section->get('image');
    $intro = $section->get('intro');
@endphp

{{--
    Preguntas frecuentes (FaqSection).

    Abre con una banda de foto y debajo, dos columnas que no se mezclan:

      - Izquierda: las preguntas. Al tocar una, su respuesta se despliega
        debajo de ella misma.
      - Derecha: el texto de la sección, siempre. Es un recado del destino, no
        la respuesta de nadie, así que no cambia al elegir una pregunta.

    Lo mueve Alpine, que ya carga el layout de la invitación junto con su plugin
    de colapso. Sin JS las respuestas siguen en el DOM, desplegadas, así que la
    sección nunca queda muda.

    Las respuestas vienen con sus correos, teléfonos y ligas ya convertidos en
    enlaces (Autolink), por eso se imprimen como HTML.
--}}
@if ($preguntas)
    <section class="td-faq" id="preguntas" x-data="{ abierta: null }">
        @if ($foto)
            <img class="td-faq__photo" src="{{ $foto }}" alt="" loading="lazy">
        @endif

        <div class="td-faq__columns">
            <div class="td-faq__aside">
                <h2 class="td-faq__title">Preguntas frecuentes</h2>

                <ul class="td-faq__list">
                    @foreach ($preguntas as $faq)
                        <li class="td-faq__item">
                            <h3>
                                <button class="td-faq__question" type="button"
                                    :class="{ 'is-active': abierta === {{ $loop->index }} }"
                                    :aria-expanded="abierta === {{ $loop->index }}"
                                    @click="abierta = abierta === {{ $loop->index }} ? null : {{ $loop->index }}">
                                    {{ $faq['question'] }}
                                </button>
                            </h3>

                            <div class="td-faq__answer" x-show="abierta === {{ $loop->index }}" x-collapse x-cloak>
                                <div class="td-faq__answer-inner">
                                    {!! $faq['content_html'] ?? nl2br(e($faq['content'])) !!}
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="td-faq__panel">
                @if ($intro)
                    <p class="td-faq__intro">{{ $intro }}</p>
                @endif
            </div>
        </div>
    </section>
@endif
