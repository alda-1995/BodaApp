@props(['section', 'context'])

@php
    $guest = $context->guest;
    $calendar = $section->get('calendar', []);
    $maxPasses = (int) ($guest->max_passes ?? $section->get('open_link_max_passes', 1));
    $deadline = $section->get('deadline');

    /*
    | Con la liga abierta apagada sólo confirma quien llegó por su invitación
    | personal. A los demás se les dice aquí, en vez de dejarlos llenar todo
    | el formulario para que el servidor los rechace al enviarlo.
    */
    $soloPersonal = !$context->hasPersonalInvitation() && !$section->get('open_link_enabled', true);
@endphp

{{--
    Confirmación de asistencia.

    El formulario manda a invitation.rsvp por fetch y, según la respuesta, se
    muestra una de las tres capas: gracias, lamentamos o error. Lo maneja
    rsvp.js de esta plantilla.
--}}
<section class="tv-confirm" id="confirm" data-rsvp
    data-url="{{ $context->rsvpUrl }}"
    data-demo="{{ $context->isDemo ? '1' : '' }}">

    <div class="tv-confirm__bg" style="background-image: url('{{ asset('images/assets-travel/pareja-fondo.jpg') }}');"></div>

    {{-- Capa: ya confirmó (se ve de entrada si este invitado ya había respondido). --}}
    <div class="tv-confirm__status {{ ($guest->has_confirmed ?? false) ? 'is-visible' : '' }}" data-rsvp-status="confirmed">
        <div class="tv-confirm__calendar"
            style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');
                   -webkit-mask-image: url('{{ asset('images/assets-travel/maskcalendar.png') }}');
                   mask-image: url('{{ asset('images/assets-travel/maskcalendar.png') }}');">
            <h2 class="tv-confirm__status-title">Gracias por confirmar tu asistencia</h2>
            <h3 class="tv-confirm__status-text">
                Para que no se te pase ningún detalle, agrega este día a tu calendario para tenerlo presente:
            </h3>

            @if (filled($calendar['starts_at'] ?? null))
                <div class="tv-confirm__calendar-buttons"
                    data-calendar
                    data-title="{{ $calendar['title'] ?? '' }}"
                    data-description="{{ $calendar['description'] ?? '' }}"
                    data-location="{{ $calendar['location'] ?? '' }}"
                    data-start="{{ $calendar['starts_at']->toIso8601String() }}">
                    <button type="button" class="tv-confirm__calendar-button" data-calendar-type="google">
                        <x-icons.calendar />
                        <span>Google Calendar</span>
                    </button>
                    <button type="button" class="tv-confirm__calendar-button tv-confirm__calendar-button--desktop"
                        data-calendar-type="outlook">
                        <x-icons.outlook />
                        <span>Outlook Calendar</span>
                    </button>
                    <button type="button" class="tv-confirm__calendar-button" data-calendar-type="ics">
                        <x-icons.download />
                        <span>Descargar .ics</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Capa: no podrá acompañarnos. --}}
    <div class="tv-confirm__status" data-rsvp-status="declined">
        <div class="tv-confirm__note">
            <h2 class="tv-confirm__note-title">Gracias por avisarnos.</h2>
            <h3 class="tv-confirm__note-text">
                Lamentamos que no puedas acompañarnos, pero agradecemos tu mensaje.
            </h3>
        </div>
    </div>

    {{-- Capa: algo salió mal (el texto lo pone el servidor). --}}
    <div class="tv-confirm__status" data-rsvp-status="error">
        <div class="tv-confirm__note tv-confirm__note--error">
            <h3 class="tv-confirm__note-text" data-rsvp-error-text></h3>
        </div>
    </div>

    <div class="tv-container tv-confirm__content">
        <div class="tv-confirm__card">
            <div class="tv-confirm__flower">
                <img class="img-parallax-flower" src="{{ asset('images/assets-travel/flor-float.png') }}" alt="">
            </div>

            <div class="tv-confirm__header">
                <div class="tv-confirm__header-mask"
                    style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');
                           -webkit-mask-image: url('{{ asset('images/assets-travel/masktitle.png') }}');
                           mask-image: url('{{ asset('images/assets-travel/masktitle.png') }}');"></div>

                <div class="tv-confirm__header-content">
                    <h2 class="tv-confirm__title">¡Queremos reservarte un asiento con nosotros!</h2>
                </div>
            </div>

            <div class="tv-confirm__body" style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');">
                @if ($section->get('closed'))
                    <p class="tv-confirm__intro">
                        La fecha límite para confirmar ya pasó. Escríbele a los novios para avisarles.
                    </p>
                @elseif ($soloPersonal)
                    <p class="tv-confirm__intro">
                        Lo sentimos, esta es una celebración privada. Sólo se puede confirmar
                        desde la invitación personal que envían los novios.
                    </p>
                @else
                    @if ($section->filled('welcome_message'))
                        <p class="tv-confirm__intro">
                            {{ $section->get('welcome_message') }}
                            @if ($deadline)
                                Agradecemos tu confirmación antes del
                                {{ $deadline->locale('es')->isoFormat('D \d\e MMMM \d\e YYYY') }}.
                            @endif
                        </p>
                    @endif

                    <div class="tv-confirm__form-wrap">
                        <div class="tv-confirm__loading" data-rsvp-loading>
                            <span class="tv-confirm__spinner"></span>
                            <p>Enviando confirmación...</p>
                        </div>

                        <form data-rsvp-form novalidate>
                            @if ($guest?->uuid)
                                <input type="hidden" name="uuid" value="{{ $guest->uuid }}">
                                <label class="tv-field">
                                    <span class="tv-field__label">Nombre completo</span>
                                    <input type="text" value="{{ $guest->name }}" disabled>
                                </label>
                            @else
                                <label class="tv-field">
                                    <span class="tv-field__label">Nombre completo</span>
                                    <input type="text" name="name" placeholder="Escribe tu nombre" required>
                                </label>
                                <label class="tv-field">
                                    <span class="tv-field__label">Número de celular</span>
                                    <input type="tel" name="phone" placeholder="Escribe tu celular">
                                </label>
                                <label class="tv-field">
                                    <span class="tv-field__label">Correo electrónico (opcional)</span>
                                    <input type="email" name="email" placeholder="Escribe tu correo electrónico">
                                </label>
                            @endif

                            <div class="tv-field">
                                <span class="tv-field__label">¿Asistirás a la boda?</span>
                                <div class="tv-field__options">
                                    <label class="tv-radio">
                                        <input type="radio" name="attendance" value="confirmed" checked>
                                        <span>Sí, ahí estaré</span>
                                    </label>
                                    <label class="tv-radio">
                                        <input type="radio" name="attendance" value="declined">
                                        <span>No podré asistir</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Los campos de abajo sólo tienen sentido si sí asiste. --}}
                            <label class="tv-field" data-rsvp-when-attending>
                                <span class="tv-field__label">Número total de asistentes (intransferibles)</span>
                                <select name="passes">
                                    @for ($passes = $maxPasses; $passes >= 1; $passes--)
                                        <option value="{{ $passes }}">{{ $passes }}</option>
                                    @endfor
                                </select>
                            </label>

                            @if ($section->get('ask_dietary_requirements'))
                                <label class="tv-field" data-rsvp-when-attending>
                                    <span class="tv-field__label">¿Tienes alguna restricción alimentaria o alergias?</span>
                                    <textarea name="dietary_restrictions" rows="2"
                                        placeholder="Ejemplo: sin gluten, sin lácteos, alergia al marisco..."></textarea>
                                </label>
                            @endif

                            @foreach ($section->get('custom_questions', []) as $pregunta)
                                <label class="tv-field" data-rsvp-when-attending>
                                    <span class="tv-field__label">{{ $pregunta['question'] }}</span>

                                    @if ($pregunta['description'])
                                        <small class="tv-field__hint">{{ $pregunta['description'] }}</small>
                                    @endif

                                    <textarea name="answers[{{ $pregunta['question'] }}]" rows="2"></textarea>
                                </label>
                            @endforeach

                            <div class="tv-confirm__errors" data-rsvp-errors></div>

                            <div class="tv-confirm__actions">
                                <button type="submit" class="tv-link">Confirmar asistencia</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            <div class="tv-confirm__footer"
                style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');
                       -webkit-mask-image: url('{{ asset('images/assets-travel/footermask.png') }}');
                       mask-image: url('{{ asset('images/assets-travel/footermask.png') }}');"></div>
        </div>
    </div>
</section>
