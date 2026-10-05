@props(['section', 'context'])

@php
    $preguntas = $section->get('items', []);
@endphp

{{--
    Preguntas frecuentes (FaqSection).

    No es un acordeón sino una lista y un panel: a la izquierda las preguntas,
    a la derecha la respuesta de la que esté elegida. Arranca con la primera
    abierta para que el panel no nazca vacío.

    Lo mueve Alpine, que ya carga el layout de la invitación. Sin JS las
    respuestas siguen en el DOM —el panel las apila— así que la sección nunca
    queda muda.

    Las respuestas vienen con sus correos, teléfonos y ligas ya convertidos en
    enlaces (Autolink), por eso se imprimen como HTML.
--}}
@if ($preguntas)
    <section class="td-faq" id="preguntas" x-data="{ activa: 0 }">
        <div class="td-faq__columns">
            <div class="td-faq__aside">
                <h2 class="td-faq__title">Preguntas frecuentes</h2>

                <ul class="td-faq__list">
                    @foreach ($preguntas as $faq)
                        <li>
                            <button class="td-faq__question" type="button"
                                :class="{ 'is-active': activa === {{ $loop->index }} }"
                                :aria-expanded="activa === {{ $loop->index }}"
                                @click="activa = {{ $loop->index }}">{{ $faq['question'] }}</button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="td-faq__panel">
                @foreach ($preguntas as $faq)
                    <div class="td-faq__answer" x-show="activa === {{ $loop->index }}" x-cloak>
                        {!! $faq['content_html'] ?? nl2br(e($faq['content'])) !!}
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
