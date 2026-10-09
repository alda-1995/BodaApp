/**
 * Visor de fotos de la galería.
 *
 * El collage es una lista plana de enlaces, sin la copia que necesitan las
 * tiras que dan la vuelta, así que el visor se arma directo sobre ella.
 *
 * El grueso de PhotoSwipe sólo se descarga cuando alguien abre una foto.
 */
export function initVisor(PhotoSwipeLightbox) {
    document.querySelectorAll('[data-visor]').forEach((collage) => {
        const visor = new PhotoSwipeLightbox({
            gallery: collage,
            children: 'a',
            pswpModule: () => import('photoswipe'),
            bgOpacity: 0.95,
            closeTitle: 'Cerrar',
            zoomTitle: 'Acercar',
            arrowPrevTitle: 'Foto anterior',
            arrowNextTitle: 'Foto siguiente',
            errorMsg: 'No se pudo cargar la foto.',
        });

        /*
         * Las medidas salen de la <img> que ya está en la página, así no se
         * piden las fotos otra vez sólo para medirlas. Si todavía no cargó, se
         * abre sin medidas y PhotoSwipe la ajusta.
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
    });
}
