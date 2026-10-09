/**
 * Cuenta regresiva del bloque de confirmación.
 *
 * El HTML ya trae los números calculados en el servidor, así que esto no
 * "rellena" nada: sólo los sigue moviendo, un tic por segundo. Si el JS no
 * corre, la cuenta se queda en el valor con el que se pintó la página, que es
 * correcto aunque no avance; lo que nunca se ve es un hueco ni tres guiones.
 *
 * Va con requestAnimationFrame y no con setInterval: cuando la pestaña queda
 * en segundo plano el navegador lo pausa, y al volver se pinta la hora real
 * de ese momento en vez de arrastrar los tics que se perdieron.
 */
export function initCountdown() {
    document.querySelectorAll('[data-countdown]').forEach(armar);
}

function armar(bloque) {
    const objetivo = new Date(bloque.dataset.fecha);

    if (Number.isNaN(objetivo.getTime())) {
        return;
    }

    const casillas = {
        dias: bloque.querySelector('[data-countdown-days]'),
        horas: bloque.querySelector('[data-countdown-hours]'),
        minutos: bloque.querySelector('[data-countdown-minutes]'),
        segundos: bloque.querySelector('[data-countdown-seconds]'),
    };

    if (!casillas.dias || !casillas.horas || !casillas.minutos) {
        return;
    }

    const dosCifras = (valor) => String(valor).padStart(2, '0');

    // Lo último que se escribió, para no tocar el DOM cuando no cambió nada.
    const ultimo = {};

    const escribir = (clave, valor) => {
        const texto = dosCifras(valor);

        if (casillas[clave] && ultimo[clave] !== texto) {
            casillas[clave].textContent = texto;
            ultimo[clave] = texto;
        }
    };

    const pintar = () => {
        // Ya pasó la boda: se queda en cero en vez de contar hacia atrás.
        const faltan = Math.max(0, objetivo.getTime() - Date.now());
        const total = Math.floor(faltan / 1000);

        escribir('dias', Math.floor(total / 86400));
        escribir('horas', Math.floor((total % 86400) / 3600));
        escribir('minutos', Math.floor((total % 3600) / 60));
        escribir('segundos', total % 60);

        return total > 0;
    };

    /*
     * Se pinta en cada cuadro pero sólo se toca el DOM cuando el segundo
     * cambia, que es lo que de verdad cuesta. Así el reloj no se atrasa ni
     * salta, aunque el navegador no entregue los cuadros con precisión.
     */
    const seguir = () => {
        if (pintar()) {
            requestAnimationFrame(seguir);
        }
    };

    seguir();
}
