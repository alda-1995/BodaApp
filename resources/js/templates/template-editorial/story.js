/**
 * Bloque "Nuestra historia": la foto que aparece al pasar por un año.
 *
 * Sólo en escritorio. En móvil las fotos se ven siempre —las pinta el CSS en el
 * flujo normal, debajo de cada año—, así que ahí no hay nada que activar: ni
 * foco, ni un botón que anunciar, ni estado que mantener.
 *
 * El HTML no trae nada interactivo: un año es texto y este archivo lo vuelve
 * pulsable. De la animación se encarga el CSS con te-story__year--active; aquí
 * sólo se decide cuál año la lleva.
 */

/** A partir de este ancho hay animación. Es el mismo corte que usa su CSS. */
const DESKTOP = '(min-width: 768px)';

/** Sólo un año a la vez: la foto anterior se va cuando entra la siguiente. */
function activate(years, year) {
    years.forEach((item) => {
        item.classList.toggle('te-story__year--active', item === year);
    });
}

function clear(years) {
    years.forEach((item) => item.classList.remove('te-story__year--active'));
}

/**
 * Enciende una fila de años: la deja pulsable y escuchando.
 *
 * Devuelve cómo apagarla, para cuando la ventana pasa a tamaño móvil.
 */
function enable(list) {
    // Un año sin foto no revela nada: se queda como texto.
    const years = Array.from(list.querySelectorAll('.te-story__year'))
        .filter((year) => year.querySelector('.te-story__year-photo'));

    if (years.length === 0) {
        return () => {};
    }

    const controller = new AbortController();
    const { signal } = controller;

    years.forEach((year) => {
        year.setAttribute('tabindex', '0');

        year.addEventListener('pointerenter', () => activate(years, year), { signal });
        // El teclado recorre los años igual que el ratón.
        year.addEventListener('focus', () => activate(years, year), { signal });
        year.addEventListener('blur', () => clear(years), { signal });
    });

    // Al salir de la fila entera, no al cambiar de año dentro de ella.
    list.addEventListener('pointerleave', () => clear(years), { signal });

    return () => {
        controller.abort();
        clear(years);
        years.forEach((year) => year.removeAttribute('tabindex'));
    };
}

export function initStory() {
    const desktop = window.matchMedia(DESKTOP);

    document.querySelectorAll('[data-te-story-years]').forEach((list) => {
        let disable = null;

        const sync = () => {
            if (desktop.matches && !disable) {
                disable = enable(list);
            } else if (!desktop.matches && disable) {
                disable();
                disable = null;
            }
        };

        sync();

        // Girar el teléfono o achicar la ventana cruza el corte: se reajusta.
        desktop.addEventListener('change', sync);
    });
}
