/**
 * Bloque "Transporte": el carrusel de corridas.
 *
 * Swiper viene del CDN que carga el layout. Si no está —CDN caído, sin JS— no
 * se hace nada: el CSS deja la fila con scroll horizontal y las corridas se
 * siguen leyendo. Por eso aquí no se construye ningún HTML: ya está en el
 * Blade, flechas incluidas.
 */
export function initTransport() {
    if (typeof Swiper === 'undefined') {
        return;
    }

    document.querySelectorAll('[data-te-transport]').forEach((carousel) => {
        const viewport = carousel.querySelector('.te-transport__viewport');
        const track = carousel.querySelector('.te-transport__track');

        if (!viewport || carousel.querySelectorAll('.te-transport__ride').length === 0) {
            return;
        }

        /*
         * La separación entre tarjetas la define el CSS, no este archivo: se
         * lee de su 'gap' antes de que Swiper tome el control —al inicializar
         * lo apaga— y se le pasa como spaceBetween, que es lo que él sí mete
         * en su cuenta al desplazar. Así el hueco se ajusta desde una sola
         * perilla, --te-transport-gap.
         */
        const separacion = parseFloat(getComputedStyle(track).columnGap) || 0;

        new Swiper(viewport, {
            spaceBetween: separacion,
            // Una corrida a la vez: cada tarjeta es su propio slide.
            slidesPerView: 1,
            // Da la vuelta: de la última se pasa a la primera y las flechas
            // nunca se apagan. Son pocas corridas y se recorren de ida y vuelta.
            loop: true,
            speed: 400,
            navigation: {
                prevEl: carousel.querySelector('.te-transport__nav--prev'),
                nextEl: carousel.querySelector('.te-transport__nav--next'),
            },
            keyboard: { enabled: true },
            a11y: {
                prevSlideMessage: 'Corrida anterior',
                nextSlideMessage: 'Corrida siguiente',
            },
        });
    });
}
