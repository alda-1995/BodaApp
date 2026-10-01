<section class="min-h-[500px] py-16 overflow-hidden relative" id="confirm">
    <div class="absolute top-0 left-0 h-full w-full z-[1] bg-center bg-cover bg-no-repeat"
        style="background-image: url('{{ asset('images/assets-travel/pareja-fondo.jpg') }}');">
    </div>
    <div @class([
        'absolute bg-main/50 top-0 left-0 h-full w-full z-50 justify-center items-center',
        'flex' => $guestData?->hasConfirmed,
        'hidden' => !$guestData?->hasConfirmed,
    ]) id="status-message">
        <div style="
            background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');
            -webkit-mask-image: url('{{ asset('images/assets-travel/maskcalendar.png') }}'); 
            mask-image: url('{{ asset('images/assets-travel/maskcalendar.png') }}');"
            class="mask-container-calendar px-[30px] pt-14 pb-14 min-h-[393px] flex flex-col items-center text-center w-full max-w-[400px] md:max-w-[560px]">
            <h2 class="text-size-title font-main mb-2 text-main">Gracias por confirmar tu asistencia</h2>
            <h3 class="text-size-heading font-main text-accent">Para que no se te pase ningún detalle, agrega este día
                a tu calendario para tenerlo presente:</h3>
            <div class="flex flex-wrap gap-2 mt-6 md:mt-8">
                @if(!empty($event))
                    @php
                        $dateOnly = Carbon\Carbon::parse($event->start_date_only)->format('Y-m-d');
                        $dateTimeStr = trim($dateOnly . ' ' . $event->start_time_only);
                        $carbonDate = Carbon\Carbon::createFromFormat(
                            'Y-m-d H:i:s',
                            $dateTimeStr,
                            'America/Mexico_City'
                        )->toISOString();
                    @endphp
                    <x-controls.calendar-button icon="calendar" :location="$event->location_ceremony"
                        label="Google Calendar" :title="$event->name" :description="$event->description"
                        :start-date="$carbonDate" typeCalendar="google" />
                    <x-controls.calendar-button class="hidden lg:flex" icon="outlook" label="Outlook Calendar"
                        :title="$event->name" :description="$event->description" :location="$event->location_ceremony"
                        :start-date="$carbonDate" typeCalendar="outlook" />

                    <x-controls.calendar-button icon="download" label="Descargar .ics" :title="$event->name"
                        :description="$event->description" :location="$event->location_ceremony" :start-date="$carbonDate"
                        typeCalendar="ics" />
                @endif
            </div>
        </div>
    </div>
    <div class="absolute bg-main/50 top-0 left-0 h-full w-full z-50 hidden justify-center items-center"
        id="status-message-noconfirmed">
        <div class="bg-error-message rounded-[19px] p-[30px] flex flex-col w-[380px] max-w-full">
            <h2 class="text-size-title font-main mb-2 text-white">Gracias por avisarnos.</h2>
            <h3 class="text-size-subtitle font-main text-white">Lamentamos que no puedas acompañarnos, pero agradecemos
                tu mensaje.</h3>
        </div>
    </div>
    <div class="absolute bg-main/50 top-0 left-0 h-full w-full z-50 hidden justify-center items-center"
        id="status-message-error">
        <div class="bg-red-500 rounded-[19px] p-[30px] flex flex-col w-[380px] max-w-full" id="content-error">

        </div>
    </div>
    <div class="container relative z-[5]">
        <div class="max-w-[502px] mx-auto relative">
            <div class="absolute top-[20%] right-0 w-[150px] translate-x-[60%] z-[5]">
                <img class="max-w-full img-parallax-flower" src="{{ asset('images/assets-travel/flor-float.png') }}"
                    alt="flor flotando img">
            </div>
            <div class="relative">
                <div class="absolute left-0 top-0 h-full w-full mask-container-title bg-center bg-cover bg-no-repeat"
                    style="
                background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');
        -webkit-mask-image: url('{{ asset('images/assets-travel/masktitle.png') }}'); 
        mask-image: url('{{ asset('images/assets-travel/masktitle.png') }}');">
                </div>
                <div class="min-h-[394px] lg:min-h-[570px] relative z-[2] flex justify-center items-center">
                    <div class="w-[300px] md:w-[380px]">
                        <h2 class="font-antura text-size-main text-white text-center">¡Queremos reservarte un asiento
                            con
                            nosotros!
                        </h2>
                    </div>
                </div>
            </div>
            <div class="p-8 md:p-10 lg:p-12 !pb-0 !pt-0 bg-center bg-cover bg-no-repeat"
                style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');">
                <p class="text-parrafo font-main text-main mb-4">Esperamos contar con tu asistencia, agradecemos tu
                    confirmación antes del 28 de enero de 2026</p>
                <div class="relative">
                    <div id="loading-overlay"
                        class="hidden absolute inset-0 bg-white/50 z-50 items-center justify-center flex-col space-y-4">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary200"></div>
                        <p id="loading-text" class="text-complement300 text-size-subtitle font-main font-medium">
                            Enviando
                            confirmación...</p>
                    </div>
                    <form name="confirmGuest" method="POST" novalidate>
                        @csrf
                        @if ($guestData)
                            <input type="hidden" name="slug" value="{{ $guestData->slug }}">
                            <x-controls.input disabled variant="secondary" id="name" type="text" name="name_disabled"
                                placeholder="Escribe tu nombre" label="Nombre completo" value="{{ $guestData->name }}"
                                required />
                            <input type="hidden" name="name" value="{{ $guestData->name }}">
                        @else
                            <x-controls.input variant="secondary" id="name" type="text" name="name"
                                placeholder="Escribe tu nombre" label="Nombre completo" value="" required />
                            <x-controls.input variant="secondary" id="phone" type="tel" name="phone"
                                placeholder="Escribe tu celular" label="Número de celular" value="" required />
                            <x-controls.input variant="secondary" id="email" type="email" name="email"
                                placeholder="Escribe tu correo electrónico" label="Correo electrónico (Opcional)" value=""
                                required />
                        @endif
                        <x-controls.rsvp-radio label="Asistirás a la boda" name="asistencia"
                            :selected="old('asistencia')" />
                        @if ($guestData)
                            @php
                                $maxGuests = $guestData->max_guests;
                            @endphp
                        @else
                            @php
                                $maxGuests = 5;
                            @endphp
                        @endif
                        <x-controls.select idParent="guest_count_parent" label="Número total de asistentes (intransferibles)"
                            name="guest_count" :options="collect(range($maxGuests, 1))->mapWithKeys(fn($num) => [$num => $num])->toArray()" placeholder="Selecciona el número de asistentes" />
                        <x-controls.textarea id="dietary_restrictions_parent" variant="secondary"
                            label="¿Tienes alguna restricción alimentaria o alergias?" name="dietary_restrictions"
                            placeholder="Ejemplo: sin gluten, sin lácteos, alergia al marisco... "
                            :value="old('dietary_restrictions')" />
                        <x-controls.textarea id="comments_parent" variant="secondary"
                            label="¿Con qué canción te pararías a bailar?" name="comments"
                            placeholder="Escribe el nombre de tu canción" :value="old('comments')" />
                        <p class="text-parrafo parrafo text-main font-main">Si durante el evento tomas fotos, nos
                            encantará recibirlas.Puedes compartirlas por el medio que prefieras para guardarlas con
                            cariño.</p>
                        <div id="errors-form-validation"></div>
                        <div class="mt-4 flex justify-center">
                            <x-controls.button variant="main" type="submit">Confirmar asistencia</x-controls.button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="h-[104px] mask-container-footer bg-center bg-cover bg-no-repeat" style="
            background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');
            -webkit-mask-image: url('{{ asset('images/assets-travel/footermask.png') }}'); 
            mask-image: url('{{ asset('images/assets-travel/footermask.png') }}');">
            </div>
        </div>
    </div>
</section>
@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const input = document.querySelector("#phone");
            window.intlTelInput(input, {
                initialCountry: "mx",
                separateDialCode: true,
                strictMode: true,
                nationalMode: true,
                hiddenInput: () => ({ phone: "phone", country: "country_code" }),
                loadUtils: () => import("https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/js/utils.js"),
            });
        });
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.forms['confirmGuest'];
            // Referencias a los contenedores de mensajes de estado
            const statusMessageSuccess = document.getElementById('status-message'); // Asistencia: yes
            const statusMessageNotConfirmed = document.getElementById('status-message-noconfirmed'); // Asistencia: no
            const statusMessageError = document.getElementById('status-message-error'); // Popup de Error general
            const contentError = document.getElementById('content-error'); // Contenedor dentro del popup de error

            const loadingOverlay = document.getElementById('loading-overlay');
            const loadingText = document.getElementById('loading-text');

            // Referencia para mostrar los errores de validación (en el formulario, no en popup)
            const errorsFormValidation = document.getElementById('errors-form-validation');

            const asistenciaRadios = form.elements['asistencia'];
            // Campos dependientes
            const guestCountWrapper = form.querySelector('#guest_count_parent');
            const guestCountSelect = form.elements['guest_count'];
            const dietaryRestrictionsWrapper = form.querySelector('#dietary_restrictions_parent'); // Contenedor de Restricciones Dietéticas
            const dietaryRestrictionsSelect = form.elements['dietary_restrictions']; // Campo de Restricciones Dietéticas
            const commentsWrapper = form.querySelector('#comments_parent'); // Contenedor de Comentarios (Canción)
            const commentsTextarea = form.elements['comments']; // Campo de Comentarios (Canción)


            const getCsrfToken = () => "{{ csrf_token() }}";

            /**
             * Oculta todos los mensajes de estado (popups de pantalla completa).
             */
            const hideAllStatusMessages = () => {
                [statusMessageSuccess, statusMessageNotConfirmed, statusMessageError].forEach(el => {
                    el.classList.remove("flex");
                    el.classList.add("hidden");
                });
            };

            /**
             * Muestra el mensaje de estado específico (popup, excluyendo el de error).
             * @param {string} typeMessage - 'success' o 'notconfirmed'.
             */
            const showStatus = (typeMessage = '') => {
                hideAllStatusMessages();

                let targetMessage;
                switch (typeMessage) {
                    case 'success':
                        targetMessage = statusMessageSuccess;
                        break;
                    case 'notconfirmed':
                        targetMessage = statusMessageNotConfirmed;
                        break;
                    default:
                        return;
                }
                targetMessage.classList.remove("hidden");
                targetMessage.classList.add("flex");
            };

            /**
             * Muestra el popup de error, inyectando el mensaje dinámico del servidor en #content-error.
             * @param {string} message - Mensaje de error del servidor.
             */
            const showStatusErrorWithContent = (message) => {
                hideAllStatusMessages();

                const errorMessage = message || "Ocurrió un error inesperado. Por favor, inténtalo de nuevo.";

                // 1. Inyectar contenido en el div #content-error
                contentError.innerHTML = `
                                                <h3 class="text-size-subtitle font-main text-white">${errorMessage}</h3>
                                            `;

                // 2. Mostrar el popup de error
                statusMessageError.classList.remove("hidden");
                statusMessageError.classList.add("flex");
            };

            /**
             * Limpia y muestra los errores de validación en el contenedor designado (#errors-form-validation).
             * @param {Object} errors - Objeto de errores del servidor (e.g., {field: ["error message"]}).
             */
            const displayValidationErrors = (errors = {}) => {
                errorsFormValidation.innerHTML = ''; // Limpiar errores previos

                const errorKeys = Object.keys(errors);

                if (errorKeys.length > 0) {
                    let html = '<ul class="list-disc list-inside mt-2 font-main text-red-500 text-parrafo">';
                    errorKeys.forEach(field => {
                        errors[field].forEach(message => {
                            html += `<li>${message}</li>`;
                        });
                    });

                    html += '</ul>';

                    errorsFormValidation.innerHTML = html;
                }
            };

            const toggleLoading = (isLoading, text = 'Enviando confirmación...') => {
                if (isLoading) {
                    loadingOverlay.classList.remove('hidden');
                    loadingOverlay.classList.add('flex');
                    loadingText.textContent = text;
                } else {
                    loadingOverlay.classList.remove('flex');
                    loadingOverlay.classList.add('hidden');
                }
            };

            /**
             * Oculta/Muestra campos dependientes y resetea sus valores si no se asiste.
             */
            const toggleGuestCount = () => {
                const selectedValue = Array.from(asistenciaRadios).find(r => r.checked)?.value;

                if (selectedValue === 'no') {
                    // Ocultar y resetear Conteo de Invitados
                    guestCountWrapper.style.display = 'none';
                    guestCountSelect.value = '';

                    // Ocultar y resetear Restricciones Dietéticas
                    dietaryRestrictionsWrapper.style.display = 'none';
                    dietaryRestrictionsSelect.value = '';

                    // Ocultar y resetear Comentarios (Canción)
                    commentsWrapper.style.display = 'none';
                    commentsTextarea.value = '';
                } else {
                    // Mostrar campos
                    guestCountWrapper.style.display = 'block';
                    dietaryRestrictionsWrapper.style.display = 'block';
                    commentsWrapper.style.display = 'block';
                }
            };

            // Inicializar visibilidad al cargar
            toggleGuestCount();
            asistenciaRadios.forEach(radio => radio.addEventListener('change', toggleGuestCount));

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                toggleLoading(true);
                errorsFormValidation.innerHTML = ''; // Limpiar errores de validación del formulario
                hideAllStatusMessages(); // Asegurar que los popups estén ocultos

                const formData = new FormData(form);
                const asistenciaValue = formData.get('asistencia');

                try {
                    const response = await fetch("{{ route('rsvp.store') }}", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": getCsrfToken(),
                            "Accept": "application/json",
                        },
                        body: formData
                    });
                    const data = await response.json().catch(() => ({}));

                    if (response.ok) {
                        if (asistenciaValue === 'yes') {
                            showStatus('success');
                        } else if (asistenciaValue === 'no') {
                            showStatus('notconfirmed');
                        }
                        form.reset();
                        toggleGuestCount();
                    } else if (response.status === 422 && data.errors) {
                        // Errores de Validación: Mostrar en el formulario
                        displayValidationErrors(data.errors);
                    } else {
                        const errorMessage = data.message || `Error ${response.status}: Error desconocido.`;
                        showStatusErrorWithContent(errorMessage);
                    }

                } catch (error) {
                    showStatusErrorWithContent('Error de conexión. Verifica tu red y vuelve a intentarlo.');
                } finally {
                    toggleLoading(false);
                }
            });
        });
    </script>
@endpush