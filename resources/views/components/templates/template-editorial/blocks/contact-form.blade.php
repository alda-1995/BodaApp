@props(['section', 'context'])

@php
    $guest = $context->guest;
    $maxPasses = (int) ($guest->max_passes ?? $section->get('open_link_max_passes', 1));
    $deadline = $section->get('deadline');
    $fondo = $section->get('background_image');

    /*
    | Con la liga abierta apagada sólo confirma quien llegó por su invitación
    | personal. A los demás se les dice aquí, en vez de dejarlos llenar todo
    | el formulario para que el servidor los rechace al enviarlo.
    */
    $soloPersonal = !$context->hasPersonalInvitation() && !$section->get('open_link_enabled', true);
@endphp

{{--
    Confirmación de asistencia (RsvpSection).

    Comparte el contrato con la otra plantilla: el formulario manda a
    invitation.rsvp por fetch y rsvp.js deja visible la capa que toca.

    El diseño parte el nombre en dos campos. El servidor sigue esperando uno
    solo, así que rsvp.js los junta al enviar: la maqueta cambia sin tocar el
    backend.
--}}
<section class="te-rsvp" id="confirmacion" data-rsvp
    data-url="{{ $context->rsvpUrl }}"
    data-demo="{{ $context->isDemo ? '1' : '' }}"
    @if ($fondo) style="--te-rsvp-bg-image: url('{{ $fondo }}')" @endif>

    <div class="te-container te-rsvp__inner">
        <p class="te-rsvp__kicker">Confirmación de asistencia</p>
        <h2 class="te-rsvp__title">RSVP</h2>

        @if ($section->get('closed'))
            <p class="te-rsvp__intro te-rsvp__intro--notice">
                La fecha límite para confirmar ya pasó. Escríbele a los novios para avisarles.
            </p>
        @elseif ($soloPersonal)
            <p class="te-rsvp__intro te-rsvp__intro--notice">
                Lo sentimos, esta es una celebración privada. Sólo se puede confirmar desde la
                invitación personal que envían los novios.
            </p>
        @else
            @if ($deadline)
                <p class="te-rsvp__intro">
                    Confirma antes del {{ $deadline->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                </p>
            @endif

            <div class="te-rsvp__form-wrap">
                <div class="te-rsvp__loading" data-rsvp-loading>
                    <span class="te-rsvp__spinner"></span>
                    <p>Enviando confirmación...</p>
                </div>

                <form data-rsvp-form novalidate>
                    @if ($guest?->uuid)
                        <input type="hidden" name="uuid" value="{{ $guest->uuid }}">

                        <label class="te-field">
                            <span>Nombre</span>
                            <input type="text" value="{{ $guest->name }}" disabled>
                        </label>
                    @else
                        <div class="te-rsvp__row">
                            <label class="te-field">
                                <span>Nombre *</span>
                                <input type="text" name="first_name" placeholder="Tu nombre" required>
                            </label>
                            <label class="te-field">
                                <span>Apellido *</span>
                                <input type="text" name="last_name" placeholder="Tu apellido" required>
                            </label>
                        </div>

                        <label class="te-field">
                            <span>Correo electrónico *</span>
                            <input type="email" name="email" placeholder="tu@correo.com" required>
                        </label>

                        <label class="te-field">
                            <span>Teléfono</span>
                            <input type="tel" name="phone" placeholder="+52 415 000 0000">
                        </label>
                    @endif

                    <fieldset class="te-field te-field--options">
                        <legend>¿Asistirás? *</legend>

                        <label class="te-radio">
                            <input type="radio" name="attendance" value="confirmed" checked>
                            <span>Con gusto asistiré</span>
                        </label>
                        <label class="te-radio">
                            <input type="radio" name="attendance" value="declined">
                            <span>No podré asistir</span>
                        </label>
                    </fieldset>

                    @if ($maxPasses > 1)
                        <label class="te-field" data-rsvp-when-attending>
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

                    {{--
                        Las preguntas las escribe el organizador, incluida la de
                        alimentos: antes iba aquí fija y no todas las bodas la
                        quieren ni la preguntan igual. Si quiere preguntarla, la
                        agrega como una más en su wizard.
                    --}}
                    @foreach ($section->get('custom_questions', []) as $pregunta)
                        <label class="te-field" data-rsvp-when-attending>
                            <span>{{ $pregunta['question'] }}</span>

                            @if ($pregunta['description'])
                                <small class="te-field__hint">{{ $pregunta['description'] }}</small>
                            @endif

                            <textarea name="answers[{{ $pregunta['question'] }}]" rows="2"></textarea>
                        </label>
                    @endforeach

                    {{--
                        El recado va fuera de "when-attending": quien no puede
                        venir es justo quien más quiere dejar unas palabras.
                        Viaja como una respuesta más, que es lo que el servidor
                        ya sabe guardar.
                    --}}
                    <label class="te-field te-field--boxed">
                        <span>Mensaje para los novios</span>
                        <textarea name="answers[Mensaje para los novios]" rows="2"
                            placeholder="Escríbeles unas palabras (opcional)"></textarea>
                    </label>

                    <div class="te-rsvp__errors" data-rsvp-errors></div>

                    <button type="submit" class="te-rsvp__submit">Confirmar asistencia</button>
                </form>
            </div>
        @endif
    </div>

    <div class="te-rsvp__status" data-rsvp-status="confirmed">
        <div class="te-rsvp__note">
            <h3>Gracias por confirmar</h3>
            <p>{{ $section->get('thank_you_message') }}</p>
        </div>
    </div>

    <div class="te-rsvp__status" data-rsvp-status="declined">
        <div class="te-rsvp__note">
            <h3>Gracias por avisarnos</h3>
            <p>Lamentamos que no puedas acompañarnos, pero agradecemos tu mensaje.</p>
        </div>
    </div>

    <div class="te-rsvp__status" data-rsvp-status="error">
        <div class="te-rsvp__note te-rsvp__note--error">
            <p data-rsvp-error-text></p>
        </div>
    </div>
</section>
