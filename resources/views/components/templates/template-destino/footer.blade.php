{{--
    Cierre de la invitación: el monograma y la fecha, escrita como en la portada.

    No es un bloque del wizard sino la firma de la plantilla, así que no tiene
    paso propio: toma el monograma del pie que el organizador subió en
    información general. Si no subió uno aparte, se usa el de la portada —son
    las mismas iniciales— y si tampoco hay ése, el pie va sin firma.
--}}
@props(['context', 'section' => null])

@php
    // El del pie manda; el de la portada es el respaldo.
    $monograma = $section?->get('footer_monogram') ?: '';
@endphp

<footer class="td-footer">
    @if ($monograma)
        <img class="td-footer__monogram" src="{{ $monograma }}" alt="">
    @endif

    @if ($context->formattedDateNumeric(' - '))
        <p class="td-footer__date">{{ $context->formattedDateNumeric(' - ') }}</p>
    @endif
</footer>
