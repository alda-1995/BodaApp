{{--
    Cierre de la invitación: el monograma sobre su propia marca de agua y la
    fecha, escrita como en la portada.

    No es un bloque del wizard sino la firma de la plantilla, así que no tiene
    paso propio: repite el monograma de la portada, que es donde el organizador
    ya lo subió. Son las iniciales de la pareja y no habría por qué firmar el
    cierre con otras; si no subió ninguno, el pie va sin él.
--}}
@props(['context', 'section' => null])

<footer class="td-footer">
    @if ($section?->filled('monogram'))
        {{-- La marca de agua es el mismo monograma, enorme y casi borrado. --}}
        <img class="td-footer__watermark" src="{{ $section->get('monogram') }}" alt="" aria-hidden="true">
        <img class="td-footer__monogram" src="{{ $section->get('monogram') }}" alt="">
    @endif

    @if ($context->formattedDateNumeric(' - '))
        <p class="td-footer__date">{{ $context->formattedDateNumeric(' - ') }}</p>
    @endif
</footer>
