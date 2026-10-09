@props(['context', 'section' => null])

{{--
    Cierre de la invitación: la ilustración a la izquierda, la fecha abajo a su
    lado y los nombres grandes abajo a la derecha.

    No es un bloque del wizard sino la firma de la plantilla, así que no tiene
    paso propio ni pregunta nada: el dibujo es del diseño y los nombres y la
    fecha salen de lo que ya capturó el organizador.
--}}
<footer class="tc-footer">
    <img class="tc-footer__dibujo" src="{{ asset('images/assets-clasica/pie.png') }}" alt="" loading="lazy">

    @if ($context->formattedDateLong())
        <p class="tc-footer__fecha">{{ $context->formattedDateLong() }}</p>
    @endif

    <p class="tc-footer__nombres">{{ $context->coupleNames(' & ') }}</p>
</footer>
