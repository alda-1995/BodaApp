/**
 * Las fotos del marco, cruzándose en bucle.
 *
 * El HTML llega con la primera encendida, así que sin JS se ve esa y ya: nunca
 * un hueco. Esto sólo va pasando a la siguiente, cruzando la opacidad con la
 * transición que pone el CSS.
 *
 * Se detiene mientras el bloque no está a la vista —no tiene sentido cruzar
 * fotos que nadie mira— y respeta a quien pidió menos movimiento.
 */
const ESPERA = 4000;

export function initMarco() {
    document.querySelectorAll('[data-carrusel]').forEach(armar);
}

function armar(contenedor) {
    const fotos = [...contenedor.querySelectorAll('img')];

    if (fotos.length < 2) {
        return;
    }

    let actual = fotos.findIndex((foto) => foto.classList.contains('is-visible'));
    actual = actual < 0 ? 0 : actual;

    let reloj = null;

    const pasar = () => {
        fotos[actual].classList.remove('is-visible');
        actual = (actual + 1) % fotos.length;
        fotos[actual].classList.add('is-visible');
    };

    const arrancar = () => {
        reloj ??= setInterval(pasar, ESPERA);
    };

    const parar = () => {
        clearInterval(reloj);
        reloj = null;
    };

    // Sólo mientras se ve. Sin IntersectionObserver, corre y ya.
    if (typeof IntersectionObserver === 'undefined') {
        arrancar();

        return;
    }

    new IntersectionObserver(
        ([entrada]) => (entrada.isIntersecting ? arrancar() : parar()),
        { threshold: 0.2 },
    ).observe(contenedor);
}
