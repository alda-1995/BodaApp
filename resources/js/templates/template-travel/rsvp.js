/**
 * Confirmación de asistencia.
 *
 * Manda el formulario por fetch a la ruta de la invitación y, según lo que
 * conteste el servidor, deja visible una de las capas: gracias, lamentamos o
 * error. Los errores de validación se listan dentro del propio formulario.
 */
export function initRsvp() {
    const section = document.querySelector('[data-rsvp]');

    if (!section) {
        return;
    }

    const form = section.querySelector('[data-rsvp-form]');
    const statuses = section.querySelectorAll('[data-rsvp-status]');
    const loading = section.querySelector('[data-rsvp-loading]');
    const errors = section.querySelector('[data-rsvp-errors]');
    const attendance = section.querySelectorAll('input[name="attendance"]');
    const whenAttending = section.querySelectorAll('[data-rsvp-when-attending]');

    // Quien ya había confirmado entra directo al agradecimiento, sin formulario.
    if (!form) {
        return;
    }

    const showStatus = (name) => {
        statuses.forEach((status) => {
            status.classList.toggle('is-visible', status.dataset.rsvpStatus === name);
        });
    };

    const hideStatuses = () => statuses.forEach((status) => status.classList.remove('is-visible'));

    const toggleLoading = (isLoading) => loading?.classList.toggle('is-visible', isLoading);

    const attendanceValue = () =>
        Array.from(attendance).find((radio) => radio.checked)?.value ?? 'confirmed';

    /** Quien no asiste no elige lugares ni responde las demás preguntas. */
    const toggleAttendingFields = () => {
        const attending = attendanceValue() === 'confirmed';

        whenAttending.forEach((field) => {
            field.style.display = attending ? '' : 'none';

            if (!attending) {
                field.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.value = '';
                });
            }
        });
    };

    const showErrors = (messages) => {
        errors.innerHTML = '';

        const list = Object.values(messages).flat();

        if (!list.length) {
            return;
        }

        const html = list.map((message) => `<li>${message}</li>`).join('');
        errors.innerHTML = `<ul>${html}</ul>`;
    };

    attendance.forEach((radio) => radio.addEventListener('change', toggleAttendingFields));
    toggleAttendingFields();

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errors.innerHTML = '';
        hideStatuses();

        const attending = attendanceValue();

        // En la vista previa no hay a dónde mandar nada: sólo se muestra el final.
        if (section.dataset.demo) {
            showStatus(attending);
            return;
        }

        toggleLoading(true);

        try {
            const response = await fetch(section.dataset.url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: new FormData(form),
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok) {
                showStatus(data.attendance ?? attending);
                form.reset();
                toggleAttendingFields();
                return;
            }

            if (response.status === 422 && data.errors) {
                showErrors(data.errors);
                return;
            }

            showError(section, data.message);
        } catch (error) {
            showError(section, 'No pudimos enviar tu confirmación. Revisa tu conexión e inténtalo de nuevo.');
        } finally {
            toggleLoading(false);
        }
    });

    function showError(root, message) {
        const text = root.querySelector('[data-rsvp-error-text]');

        if (text) {
            text.textContent = message || 'Ocurrió un error inesperado. Inténtalo de nuevo.';
        }

        showStatus('error');
    }
}
