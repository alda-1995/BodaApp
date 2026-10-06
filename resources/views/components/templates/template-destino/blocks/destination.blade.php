@props(['section', 'context'])

@php
    $fecha = $section->get('event_date');
    $ruta = $section->get('route');
@endphp

{{--
    Destino y cuenta regresiva (DestinationSection).

    Tres franjas: la ciudad sobre papel, la cuenta regresiva sobre negro y, bajo
    ella, el "save the date" con el vuelo y el botón de calendario.

    La cuenta la rellena el navegador (destino/countdown.js) a partir de la
    fecha que viaja en data-fecha. El HTML ya trae los números calculados en el
    servidor, para que quien no tenga JS no vea tres guiones.
--}}
<section class="td-destination" id="destino">
    <div class="td-destination__head td-container">
        <p class="td-destination__eyebrow">{{ $section->get('eyebrow') }}</p>
        <h2 class="td-destination__city">{{ $section->get('city') }}</h2>
    </div>

    @if ($fecha)
        <div class="td-countdown" data-countdown data-fecha="{{ $fecha->toIso8601String() }}">
            @php
                // Lo que falta ahora mismo; el navegador lo sigue contando.
                $restante = max(0, now()->diffInSeconds($fecha, false));
                $dias = intdiv($restante, 86400);
                $horas = intdiv($restante % 86400, 3600);
                $minutos = intdiv($restante % 3600, 60);
                $segundos = $restante % 60;
            @endphp

            <div class="td-countdown__unit">
                {{--
                    Las etiquetas van en inglés porque así las escribió el
                    diseño, igual que "SAVE THE DATE" y "SCROLLDOWN": es parte
                    de su tono de viaje, no un descuido de traducción.
                --}}
                <p class="td-countdown__value" data-countdown-days>{{ str_pad($dias, 2, '0', STR_PAD_LEFT) }}</p>
                <p class="td-countdown__label">Days</p>
            </div>

            <div class="td-countdown__unit">
                <p class="td-countdown__value" data-countdown-hours>{{ str_pad($horas, 2, '0', STR_PAD_LEFT) }}</p>
                <p class="td-countdown__label">Hours</p>
            </div>

            <div class="td-countdown__unit">
                <p class="td-countdown__value" data-countdown-minutes>{{ str_pad($minutos, 2, '0', STR_PAD_LEFT) }}</p>
                <p class="td-countdown__label">Min</p>
            </div>

            {{--
                Los segundos no están en el diseño, que sólo pone tres cifras.
                Van porque son los que hacen que la cuenta se vea avanzar: sin
                ellos la pantalla queda quieta un minuto entero.
            --}}
            <div class="td-countdown__unit">
                <p class="td-countdown__value" data-countdown-seconds>{{ str_pad($segundos, 2, '0', STR_PAD_LEFT) }}</p>
                <p class="td-countdown__label">Sec</p>
            </div>
        </div>
    @endif

    <div class="td-destination__foot">
        <div class="td-container">
            <div class="td-destination__inner">
                <p class="td-destination__save">Save the date</p>
                <div class="td-destination__trip">
                    @if ($ruta)
                        <p class="td-destination__route">{{ $ruta }}</p>
                    @endif
        
                    @if ($fecha)
                        {{--
                            Al calendario se va con un .ics que arma el navegador: así no
                            hace falta una ruta en el servidor ni mandar a nadie fuera.
                        --}}
                        <button type="button" class="td-destination__calendar" data-calendar
                            data-inicio="{{ $fecha->toIso8601String() }}"
                            data-titulo="Boda de {{ $context->coupleNames(' y ') }}"
                            data-lugar="{{ $section->get('city') }}">Añadir al calendario</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
