/**
 * El visor de una tira que lleva su lista dos veces.
 *
 * Se arma SÓLO sobre la primera lista: si se armara sobre las dos, cada foto
 * saldría repetida al pasar de una a otra dentro de la modal. Las fotos de la
 * copia abren el visor en la posición de su original, que es la misma foto.
 *
 * Lo comparten "Nuestra historia" y la galería, que tienen el mismo problema.
 */
export function armarVisorConCopia(tira, PhotoSwipeLightbox, { original, copia, enlace }) {
    const lista = tira.querySelector(original);

    if (!lista) {
        return null;
    }

    const visor = new PhotoSwipeLightbox({
        gallery: lista,
        children: enlace,
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
     * Las medidas salen de la <img> que ya está en la página, así no se piden
     * las fotos otra vez sólo para medirlas. Si todavía no cargó, se abre sin
     * medidas y PhotoSwipe la ajusta.
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

    tira.querySelector(copia)?.addEventListener('click', (evento) => {
        const tocado = evento.target.closest(enlace);

        if (!tocado) {
            return;
        }

        evento.preventDefault();
        visor.loadAndOpen(Number(tocado.dataset.indice) || 0);
    });

    return visor;
}
