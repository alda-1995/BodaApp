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

    /*
    | Quien ya respondió desde su invitación personal no vuelve a ver el
    | formulario: se le da las gracias y ya. Sólo lo sabemos de quien llegó por
    | su liga —la liga abierta no identifica a nadie—, así que para los demás
    | esto siempre es falso y el formulario se pinta igual que antes.
    */
    $yaConfirmo = (bool) ($guest->has_confirmed ?? false);
@endphp

{{--
    Confirmación de asistencia (RsvpSection).

    Comparte el contrato con las otras plantillas: el formulario manda a
    invitation.rsvp por fetch y rsvp.js deja visible la capa que toca.

    El diseño parte el nombre en dos campos. El servidor sigue esperando uno
    solo, así que rsvp.js los junta al enviar: la maqueta cambia sin tocar el
    backend.
--}}
<section class="td-rsvp" id="confirmacion" data-rsvp
    data-url="{{ $context->rsvpUrl }}"
    data-demo="{{ $context->isDemo ? '1' : '' }}">

    <div class="td-container td-rsvp__columns">
        <div class="td-rsvp__aside">
            <h2 class="td-rsvp__title">RSVP</h2>
            <p class="td-rsvp__kicker">Confirmación de asistencia</p>
        </div>

        <div class="td-rsvp__main">
            {{--
                El agradecimiento va primero: a quien ya confirmó no se le dice
                que la fecha límite pasó, porque respondió a tiempo. Lo pinta la
                capa de abajo, que el servidor deja ya encendida.
            --}}
            @if ($yaConfirmo)
            @elseif ($section->get('closed'))
                <p class="td-rsvp__notice">
                    La fecha límite para confirmar ya pasó. Escríbele a los novios para avisarles.
                </p>
            @elseif ($soloPersonal)
                <p class="td-rsvp__notice">
                    Lo sentimos, esta es una celebración privada. Sólo se puede confirmar desde la
                    invitación personal que envían los novios.
                </p>
            @else
                @if ($deadline)
                    <p class="td-rsvp__deadline">
                        Confirma antes del {{ $deadline->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                    </p>
                @endif

                <div class="td-rsvp__form-wrap">
                    <div class="td-rsvp__loading" data-rsvp-loading>
                        <span class="td-rsvp__spinner"></span>
                        <p>Enviando confirmación...</p>
                    </div>

                    <form data-rsvp-form novalidate>
                        @if ($guest?->uuid)
                            <input type="hidden" name="uuid" value="{{ $guest->uuid }}">

                            <label class="td-field">
                                <span>Nombre</span>
                                <input type="text" value="{{ $guest->name }}" disabled>
                            </label>
                        @else
                            <div class="td-rsvp__row">
                                <label class="td-field">
                                    <span>Nombre *</span>
                                    <input type="text" name="first_name" placeholder="Tu nombre" required>
                                </label>
                                <label class="td-field">
                                    <span>Apellido *</span>
                                    <input type="text" name="last_name" placeholder="Tu apellido" required>
                                </label>
                            </div>

                            <label class="td-field">
                                <span>Correo electrónico *</span>
                                <input type="email" name="email" placeholder="tu@correo.com" required>
                            </label>

                            <label class="td-field">
                                <span>Teléfono</span>
                                <input type="tel" name="phone" placeholder="+52 415 000 0000">
                            </label>
                        @endif

                        <fieldset class="td-field td-field--options">
                            <legend>¿Asistirás? *</legend>

                            <label class="td-check">
                                <input type="radio" name="attendance" value="confirmed" checked>
                                <span>Con gusto asistiré</span>
                            </label>
                            <label class="td-check">
                                <input type="radio" name="attendance" value="declined">
                                <span>No podré asistir</span>
                            </label>
                        </fieldset>

                        @if ($maxPasses > 1)
                            <label class="td-field" data-rsvp-when-attending>
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
                            <label class="td-field" data-rsvp-when-attending>
                                <span>{{ $pregunta['question'] }}</span>

                                @if ($pregunta['description'])
                                    <small class="td-field__hint">{{ $pregunta['description'] }}</small>
                                @endif

                                <textarea name="answers[{{ $pregunta['question'] }}]" rows="2"></textarea>
                            </label>
                        @endforeach

                        {{--
                            El recado va fuera de "when-attending": quien no puede
                            venir es justo quien más quiere dejar unas palabras.
                        --}}
                        <label class="td-field td-field--boxed">
                            <span>Mensaje para los novios</span>
                            <textarea name="answers[Mensaje para los novios]" rows="2"
                                placeholder="Escríbeles unas palabras (opcional)"></textarea>
                        </label>

                        <div class="td-rsvp__errors" data-rsvp-errors></div>

                        <button type="submit" class="td-rsvp__submit">Confirmar asistencia</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    @if ($fondo)
        <img class="td-rsvp__photo" src="{{ $fondo }}" alt="" loading="lazy">
    @endif

    {{-- Encendida de entrada si este invitado ya había respondido. --}}
    <div @class(['td-rsvp__status', 'is-visible' => $yaConfirmo]) data-rsvp-status="confirmed">
        <div class="td-rsvp__note">
            <h3>Gracias por confirmar</h3>
            <p>{{ $section->get('thank_you_message') }}</p>
        </div>
    </div>

    <div class="td-rsvp__status" data-rsvp-status="declined">
        <div class="td-rsvp__note">
            <h3>Gracias por avisarnos</h3>
            <p>Lamentamos que no puedas acompañarnos, pero agradecemos tu mensaje.</p>
        </div>
    </div>

    <div class="td-rsvp__status" data-rsvp-status="error">
        <div class="td-rsvp__note td-rsvp__note--error">
            <p data-rsvp-error-text></p>
        </div>
    </div>
</section>
