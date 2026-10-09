@props(['section', 'context'])

@php
    $fotos = $section->get('images', []);
@endphp

{{--
    Cuenta regresiva (CountdownSection).

    Una foto dentro del marco dorado y, debajo, cuánto falta para la boda.

    La cuenta llega calculada desde el servidor para que se lea aunque el JS no
    corra, y countdown.js la pone a andar como un reloj. Si el organizador subió
    varias fotos, marco.js las va cruzando en bucle; con una sola se queda fija.
--}}
<section class="tc-countdown" id="cuenta-regresiva">
    @if ($fotos)
        <div class="tc-countdown__marco"
            style="--tc-marco: url('{{ asset('images/assets-clasica/marco.png') }}')">

            <div class="tc-countdown__fotos" @if (count($fotos) > 1) data-carrusel @endif>
                @foreach ($fotos as $i => $foto)
                    {{-- La primera llega encendida: sin JS se ve esa y ya. --}}
                    <img @class(['tc-countdown__foto', 'is-visible' => $i === 0])
                        src="{{ $foto }}" alt="" loading="lazy">
                @endforeach
            </div>
        </div>
    @endif

    @if ($context->eventDate)
        @php
            // Lo que falta ahora mismo; el navegador lo sigue contando.
            $restante = max(0, now()->diffInSeconds($context->eventDate, false));
            $cuenta = [
                ['days', intdiv($restante, 86400), 'Días'],
                ['hours', intdiv($restante % 86400, 3600), 'Horas'],
                ['minutes', intdiv($restante % 3600, 60), 'Min'],
                ['seconds', $restante % 60, 'Seg'],
            ];
        @endphp

        <div class="tc-countdown__cuenta" data-countdown data-fecha="{{ $context->eventDate->toIso8601String() }}">
            <h2 class="tc-countdown__titulo">El gran día llega en</h2>

            <ol class="tc-countdown__cifras">
                @foreach ($cuenta as [$clave, $valor, $etiqueta])
                    <li class="tc-countdown__cifra">
                        <span class="tc-countdown__numero" data-countdown-{{ $clave }}>{{ str_pad($valor, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="tc-countdown__unidad">{{ $etiqueta }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
</section>
