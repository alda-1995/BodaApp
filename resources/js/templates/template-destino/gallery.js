/**
 * Galería: el visor para ver las fotos en grande.
 *
 * Aquí no se arma ningún carrusel. La tira ya se recorre con scroll desde el
 * CSS, que funciona sin JS y es el gesto natural en móvil, así que lo único
 * que añade el JS es abrir la foto completa.
 *
 * Cada foto es un enlace de verdad: sin JS abre la imagen en el navegador.
 */
import PhotoSwipeLightbox from 'photoswipe/lightbox';

export function initGallery() {
    document.querySelectorAll('[data-td-gallery]').forEach(armarVisor);
}

function armarVisor(tira) {
    const visor = new PhotoSwipeLightbox({
        gallery: tira,
        children: '.td-gallery__link',
        // El grueso de la librería sólo se descarga cuando alguien abre una foto.
        pswpModule: () => import('photoswipe'),
        bgOpacity: 0.95,
        closeTitle: 'Cerrar',
        zoomTitle: 'Acercar',
        arrowPrevTitle: 'Foto anterior',
        arrowNextTitle: 'Foto siguiente',
        errorMsg: 'No se pudo cargar la foto.',
    });

    /*
     * Las medidas salen de la <img> que ya está en la página. Así no se piden
     * las fotos otra vez sólo para medirlas. Si todavía no cargó —'lazy' y
     * nunca estuvo a la vista— se abre sin medidas y PhotoSwipe la ajusta.
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
