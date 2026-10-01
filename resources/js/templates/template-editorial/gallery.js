/**
 * Bloque "Galería": el carrusel de fotos.
 *
 * Swiper viene del CDN que carga el layout. Si no está —CDN caído, sin JS— no
 * se hace nada: el CSS deja la fila con scroll horizontal y las fotos se ven
 * igual. Por eso aquí no se construye ningún HTML: ya está todo en el Blade.
 *
 * La foto del centro va entera y las de los lados achicadas y atenuadas; de
 * eso se encarga el CSS con .swiper-slide-active.
 */

/** Cuántas fotos hacen falta para que dar la vuelta tenga sentido. */
const MIN_PARA_CICLAR = 4;

export function initGallery() {
    if (typeof Swiper === 'undefined') {
        return;
    }

    document.querySelectorAll('[data-te-gallery]').forEach((carousel) => {
        const viewport = carousel.querySelector('.te-gallery__viewport');
        const total = carousel.querySelectorAll('.te-gallery__slide').length;

        if (!viewport || total === 0) {
            return;
        }

        new Swiper(viewport, {
            // El ancho lo pone el CSS, para que sea una perilla más del bloque.
            slidesPerView: 'auto',
            centeredSlides: true,
            // Con dos o tres fotos, dar la vuelta se siente un salto.
            loop: total >= MIN_PARA_CICLAR,
            speed: 400,
            grabCursor: true,
            pagination: {
                el: carousel.querySelector('.te-gallery__dots'),
                clickable: true,
            },
            keyboard: { enabled: true },
            a11y: {
                prevSlideMessage: 'Foto anterior',
                nextSlideMessage: 'Foto siguiente',
                paginationBulletMessage: 'Ir a la foto {{index}}',
            },
        });
    });
}
