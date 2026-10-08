@props(['section', 'context'])

{{--
    Confirmación de asistencia. Por ahora sólo muestra la información; el envío
    de respuestas se conecta en el siguiente paso.
--}}
<section class="inv-block inv-section" id="confirm">
    <div class="inv-container inv-narrow inv-center">
        <h2 class="inv-title">Confirma tu asistencia</h2>

        @if ($section->get('closed'))
            <p class="inv-text">La fecha límite para confirmar ya pasó. Comunícate con los novios.</p>
        @elseif (!$context->hasPersonalInvitation() && !$section->get('open_link_enabled', true))
            {{-- Sin liga abierta, sólo confirma quien tiene su invitación personal. --}}
            <p class="inv-text">
                Lo sentimos, esta es una celebración privada. Sólo se puede confirmar desde la
                invitación personal que envían los novios.
            </p>
        @else
            {{-- Los novios ven el bloque para revisar el diseño, pero no confirman. --}}
            @if ($context->isOrganizerPreview)
                <p class="inv-text">
                    Estás viendo tu propia invitación: desde aquí no se confirma.
                    Comparte el enlace con tus invitados.
                </p>
            @endif

            @if ($section->filled('welcome_message'))
                <p class="inv-text">{{ $section->get('welcome_message') }}</p>
            @endif

            @if ($section->get('deadline'))
                <p class="inv-text">
                    Puedes confirmar hasta el {{ $section->get('deadline')->locale('es')->isoFormat('D \d\e MMMM') }}.
                </p>
            @endif

            @if ($context->guest)
                <p class="inv-text">
                    {{ $context->guest->name }}, tienes {{ $context->guest->max_passes }}
                    {{ $context->guest->max_passes === 1 ? 'lugar' : 'lugares' }} apartados.
                </p>
            @endif
        @endif
    </div>
</section>
