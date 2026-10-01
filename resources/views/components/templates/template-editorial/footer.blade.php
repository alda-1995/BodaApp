{{--
    Cierre de la invitación: monograma, nombres, fecha y ciudad.

    No es un bloque del wizard sino la firma de la plantilla, así que no tiene
    paso propio: repite los datos de la sección general, que es donde el
    organizador ya los capturó. Por eso recibe esa sección además del contexto.

    El monograma es el mismo que subió el organizador para la portada: son las
    iniciales de la pareja, y no habría por qué firmar el cierre con otras. Si
    no subió ninguno, el pie va sin él.
--}}
@props(['context', 'section' => null])

<footer class="te-footer">
    <div class="te-container te-center">
        @if ($section?->filled('monogram'))
            <img class="te-footer__monogram" src="{{ $section->get('monogram') }}" alt="">
        @endif

        <p class="te-footer__names">{{ $context->coupleNames(' & ') }}</p>

        @if ($context->formattedDateLong())
            <p class="te-footer__date">{{ $context->formattedDateLong() }}</p>
        @endif

        @if ($section?->filled('event_city'))
            <p class="te-footer__place">{{ $section->get('event_city') }}</p>
        @endif
    </div>
</footer>
