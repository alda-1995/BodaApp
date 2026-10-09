@props(['section', 'context'])

@php
    $preguntas = $section->get('items', []);
@endphp

{{--
    Preguntas frecuentes (FaqSection).

    Una lista numerada: cada fila es su número, la pregunta y un "+" que la
    abre. La respuesta se despliega debajo de su propia pregunta, no en un
    panel aparte.

    El plegado lo lleva Alpine con x-collapse, igual que en las otras
    plantillas: sin JS, todas quedan abiertas y se leen igual.
--}}
<section class="tc-faq" id="preguntas">
    <h2 class="tc-faq__titulo">Preguntas frecuentes</h2>

    <ol class="tc-faq__lista">
        @foreach ($preguntas as $i => $pregunta)
            <li class="tc-faq__fila" x-data="{ abierta: false }">
                <button type="button" class="tc-faq__cabecera" @click="abierta = !abierta"
                    :aria-expanded="abierta ? 'true' : 'false'">
                    <span class="tc-faq__numero">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="tc-faq__pregunta">{{ $pregunta['question'] }}</span>
                    <span class="tc-faq__signo" aria-hidden="true" x-text="abierta ? '−' : '+'">+</span>
                </button>

                <div class="tc-faq__respuesta" x-show="abierta" x-collapse x-cloak>
                    <p>{{ $pregunta['content'] }}</p>
                </div>
            </li>
        @endforeach
    </ol>
</section>
