@props(['section', 'context'])

@php
    $guest = $context->guest;
    $maxPasses = (int) ($guest->max_passes ?? $section->get('open_link_max_passes', 1));
    $deadline = $section->get('deadline');
    $foto = $section->get('photo');

    /*
    | Con la liga abierta apagada sólo confirma quien llegó por su invitación
    | personal. A los demás se les dice aquí, en vez de dejarlos llenar todo
    | el formulario para que el servidor los rechace al enviarlo.
    */
    $soloPersonal = !$context->hasPersonalInvitation() && !$section->get('open_link_enabled', true);

    /*
    | Quien ya respondió desde su invitación personal no vuelve a ver el
    | formulario: se le da las gracias y ya.
    */
    $yaConfirmo = (bool) ($guest->has_confirmed ?? false);
@endphp

{{--
    Confirmación de asistencia (RsvpSection).

    La cuenta regresiva vive en su propio bloque, justo encima: aquí sólo va el
    formulario.

    Comparte el contrato con las otras plantillas: manda a
    invitation.rsvp por fetch y rsvp.js deja visible la capa que toca. El nombre
    va partido en dos campos y rsvp.js los junta al enviar.
--}}
{{-- El papel es el fondo de toda la sección, de borde a borde. --}}
<section class="tc-rsvp" id="confirmacion" data-rsvp
    data-url="{{ $context->rsvpUrl }}"
    data-demo="{{ $context->isDemo ? '1' : '' }}"
    @if ($foto) style="--tc-papel-rsvp: url('{{ $foto }}')" @endif>

    <div class="tc-rsvp__panel">
        <h2 class="tc-rsvp__titulo">RSVP</h2>

        @if ($yaConfirmo)
        @elseif ($section->get('closed'))
            <p class="tc-rsvp__aviso">
                La fecha límite para confirmar ya pasó. Escríbele a los novios para avisarles.
            </p>
        @elseif ($soloPersonal)
            <p class="tc-rsvp__aviso">
                Lo sentimos, esta es una celebración privada. Sólo se puede confirmar desde la
                invitación personal que envían los novios.
            </p>
        @else
            {{-- Los novios ven el formulario para revisar el diseño, pero no confirman. --}}
            @if ($context->isOrganizerPreview)
                <p class="tc-rsvp__aviso">
                    Estás viendo tu propia invitación: desde aquí no se confirma.
                    Comparte el enlace con tus invitados.
                </p>
            @elseif ($deadline)
                <p class="tc-rsvp__limite">
                    Confirma antes del {{ $deadline->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                </p>
            @endif

            <div class="tc-rsvp__espera" data-rsvp-loading>
                <span class="tc-rsvp__spinner"></span>
                <p>Enviando confirmación...</p>
            </div>

            <form data-rsvp-form novalidate>
                @if ($guest?->uuid)
                    <input type="hidden" name="uuid" value="{{ $guest->uuid }}">

                    <label class="tc-campo">
                        <span>Nombre</span>
                        <input type="text" value="{{ $guest->name }}" disabled>
                    </label>
                @else
                    <label class="tc-campo">
                        <span>Tu nombre</span>
                        <input type="text" name="first_name" placeholder="Tu nombre" required>
                    </label>

                    <label class="tc-campo">
                        <span>Tu apellido</span>
                        <input type="text" name="last_name" placeholder="Tu apellido" required>
                    </label>

                    <label class="tc-campo">
                        <span>Correo electrónico</span>
                        <input type="email" name="email" placeholder="tu@correo.com" required>
                    </label>

                    <label class="tc-campo">
                        <span>Teléfono</span>
                        <input type="tel" name="phone" placeholder="+52 415 000 0000">
                    </label>
                @endif

                <fieldset class="tc-campo tc-campo--opciones">
                    <legend>¿Asistirás? *</legend>

                    <label class="tc-check">
                        <input type="radio" name="attendance" value="confirmed" checked>
                        <span>Con gusto asistiré</span>
                    </label>
                    <label class="tc-check">
                        <input type="radio" name="attendance" value="declined">
                        <span>No podré asistir</span>
                    </label>
                </fieldset>

                @if ($maxPasses > 1)
                    <label class="tc-campo" data-rsvp-when-attending>
                        <span>Número de asistentes</span>
                        <select name="passes">
                            @for ($passes = $maxPasses; $passes >= 1; $passes--)
                                <option value="{{ $passes }}">{{ $passes }}</option>
                            @endfor
                        </select>
                    </label>
                @else
                    {{-- Con un solo lugar no hay nada que elegir, pero el
                         servidor lo pide al confirmar. --}}
                    <input type="hidden" name="passes" value="1">
                @endif

                @foreach ($section->get('custom_questions', []) as $pregunta)
                    <label class="tc-campo" data-rsvp-when-attending>
                        <span>{{ $pregunta['question'] }}</span>

                        @if ($pregunta['description'])
                            <small class="tc-campo__nota">{{ $pregunta['description'] }}</small>
                        @endif

                        <textarea name="answers[{{ $pregunta['question'] }}]" rows="2"></textarea>
                    </label>
                @endforeach

                {{--
                    El recado va fuera de "when-attending": quien no puede
                    venir es justo quien más quiere dejar unas palabras.
                --}}
                <label class="tc-campo tc-campo--caja">
                    <span>Mensaje para los novios</span>
                    <textarea name="answers[Mensaje para los novios]" rows="2"
                        placeholder="Escríbeles unas palabras (opcional)"></textarea>
                </label>

                <div class="tc-rsvp__errores" data-rsvp-errors></div>

                <button type="submit" class="tc-rsvp__enviar" @disabled($context->isOrganizerPreview)>
                    Confirmar asistencia
                </button>
            </form>
        @endif
    </div>

    {{-- Encendida de entrada si este invitado ya había respondido. --}}
    <div @class(['tc-rsvp__estado', 'is-visible' => $yaConfirmo]) data-rsvp-status="confirmed">
        <div class="tc-rsvp__nota">
            <h3>Gracias por confirmar</h3>
            <p>{{ $section->get('thank_you_message') }}</p>
        </div>
    </div>

    <div class="tc-rsvp__estado" data-rsvp-status="declined">
        <div class="tc-rsvp__nota">
            <h3>Gracias por avisarnos</h3>
            <p>Lamentamos que no puedas acompañarnos, pero agradecemos tu mensaje.</p>
        </div>
    </div>

    <div class="tc-rsvp__estado" data-rsvp-status="error">
        <div class="tc-rsvp__nota tc-rsvp__nota--error">
            <p data-rsvp-error-text></p>
        </div>
    </div>
</section>
