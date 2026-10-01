/**
 * Bloque "Galería": el carrusel de fotos y el visor para verlas grandes.
 *
 * Swiper viene del CDN que carga el layout. Si no está —CDN caído, sin JS— no
 * se arma el carrusel: el CSS deja la fila con scroll horizontal y las fotos se
 * ven igual. Por eso aquí no se construye ningún HTML: ya está todo en el Blade.
 *
 * La foto del centro va entera y las de los lados achicadas y atenuadas; de
 * eso se encarga el CSS con .swiper-slide-active.
 */
import PhotoSwipeLightbox from 'photoswipe/lightbox';

/** Cuántas fotos hacen falta para que dar la vuelta tenga sentido. */
const MIN_PARA_CICLAR = 4;

export function initGallery() {
    document.querySelectorAll('[data-te-gallery]').forEach((carousel) => {
        armarVisor(carousel);
        armarCarrusel(carousel);
    });
}

/**
 * El visor de la foto en grande.
 *
 * Va antes que Swiper y por su cuenta: el carrusel es un adorno y el visor es
 * lo que el invitado quiere —ver la foto completa—, así que si Swiper no carga
 * el visor tiene que seguir abriendo igual.
 *
 * No hace falta esquivar diapositivas clonadas: Swiper 11 ya no clona para dar
 * la vuelta, reordena las que hay. Si algún día se baja de versión, habría que
 * volver a dejar los clones fuera o el visor enseñaría fotos repetidas.
 */
function armarVisor(carousel) {
    const visor = new PhotoSwipeLightbox({
        gallery: carousel,
        children: '.te-gallery__link',
        // El grueso de la librería sólo se descarga cuando alguien abre una foto.
        pswpModule: () => import('photoswipe'),
        bgOpacity: 0.92,
        closeTitle: 'Cerrar',
        zoomTitle: 'Acercar',
        arrowPrevTitle: 'Foto anterior',
        arrowNextTitle: 'Foto siguiente',
        errorMsg: 'No se pudo cargar la foto.',
    });

    /*
     * Las medidas salen de la <img> que ya está en la página, que para cuando
     * alguien hace clic lleva rato cargada. Así no se piden las fotos otra vez
     * sólo para medirlas. Si todavía no cargó —'lazy' y nunca estuvo a la
     * vista— se abre sin medidas y PhotoSwipe la ajusta al cargarla.
     */
    visor.addFilter('domItemData', (itemData, element) => {
        const img = element.querySelector('img');

        if (img?.naturalWidth) {
            itemData.width = img.naturalWidth;
            itemData.height = img.naturalHeight;
        }

        return itemData;
    });

    visor.init();
}

function armarCarrusel(carousel) {
    if (typeof Swiper === 'undefined') {
        return;
    }

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
}
