/**
 * Nuestra historia: una tira de polaroids que gira describiendo un arco.
 *
 * El arco no se puede hacer con una animación de CSS porque la altura y el giro
 * de cada foto no dependen de su sitio en la lista, sino de DÓNDE ESTÁ en ese
 * momento: la del centro va arriba y derecha, y las de los lados caen y se
 * inclinan hacia afuera. Eso cambia en cada fotograma, así que lo calcula el JS.
 *
 * La tira lleva la lista dos veces y el desplazamiento se envuelve a la mitad,
 * que es justo donde la copia repite a la primera: así da vueltas sin fin.
 *
 * Se puede arrastrar para adelantar o retroceder, y al soltar sigue sola.
 *
 * Sin JS no queda rota: el CSS deja las fotos con un giro fijo y la tira se
 * recorre a mano.
 */
import PhotoSwipeLightbox from 'photoswipe/lightbox';

/** Forma del arco: cuánto caen las de las orillas y cuánto se inclinan. */
const CAIDA_MAXIMA = 70; // px
const GIRO_MAXIMO = 14; // grados
/** Lo que avanza sola, en píxeles por segundo. */
const VELOCIDAD = 38;
/** A partir de cuánto se considera arrastre y no un clic. */
const UMBRAL_ARRASTRE = 6; // px

export function initStory() {
    document.querySelectorAll('[data-td-story]').forEach(armar);
}

function armar(tira) {
    const marquesina = tira.querySelector('.td-story__marquee');
    const original = tira.querySelector('[data-td-story-original]');

    if (!marquesina || !original) {
        return;
    }

    armarVisor(tira, original);
    armarMovimiento(tira, marquesina);
}

/* -------------------------------------------------------------------------
 | El movimiento
 * ---------------------------------------------------------------------- */

function armarMovimiento(tira, marquesina) {
    const tarjetas = [...marquesina.querySelectorAll('.td-story__card')];

    if (tarjetas.length === 0) {
        return;
    }

    const menosMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)');

    let desplazamiento = 0;
    let mitad = 0;
    let centros = [];
    let arrastrando = false;
    let arrastrado = 0;
    let ultimoFotograma = 0;

    /*
     * Las posiciones de reposo se miden una vez y se guardan: leerlas en cada
     * fotograma obligaría al navegador a recalcular la maqueta 60 veces por
     * segundo sólo para saber algo que no cambia.
     */
    const medir = () => {
        mitad = marquesina.getBoundingClientRect().width / 2;
        centros = tarjetas.map((tarjeta) => tarjeta.offsetLeft + tarjeta.offsetWidth / 2);
    };

    const colocar = () => {
        const caja = tira.getBoundingClientRect();
        const centroPantalla = caja.width / 2;

        marquesina.style.transform = `translateX(${-desplazamiento}px)`;

        tarjetas.forEach((tarjeta, i) => {
            // Dónde cae su centro dentro de la ventana de la tira, de -1 a 1.
            const x = centros[i] - desplazamiento;
            const t = Math.max(-1.6, Math.min(1.6, (x - centroPantalla) / centroPantalla));

            // Parábola: 0 en el centro, máximo en las orillas. Es el arco.
            tarjeta.style.transform =
                `translateY(${(t * t * CAIDA_MAXIMA).toFixed(2)}px) rotate(${(t * GIRO_MAXIMO).toFixed(2)}deg)`;
        });
    };

    const avanzar = (ahora) => {
        const delta = ultimoFotograma ? (ahora - ultimoFotograma) / 1000 : 0;
        ultimoFotograma = ahora;

        if (!arrastrando && !menosMovimiento.matches && !tira.matches(':hover')) {
            desplazamiento += VELOCIDAD * delta;
        }

        // La vuelta: al pasar la mitad se vuelve al principio, que es idéntico.
        if (mitad > 0) {
            desplazamiento = ((desplazamiento % mitad) + mitad) % mitad;
        }

        colocar();
        requestAnimationFrame(avanzar);
    };

    /* --- Arrastrar --- */

    let inicioX = 0;
    let inicioDesplazamiento = 0;
    let capturado = false;

    tira.addEventListener('pointerdown', (evento) => {
        // Sólo el botón principal; el derecho abre el menú del navegador.
        if (evento.button !== 0) {
            return;
        }

        arrastrando = true;
        arrastrado = 0;
        capturado = false;
        inicioX = evento.clientX;
        inicioDesplazamiento = desplazamiento;
    });

    tira.addEventListener('pointermove', (evento) => {
        if (!arrastrando) {
            return;
        }

        const recorrido = evento.clientX - inicioX;
        arrastrado = Math.max(arrastrado, Math.abs(recorrido));

        /*
         * El puntero se captura al pasar el umbral, NO al pulsar.
         *
         * Capturándolo desde el principio, el navegador reasigna el clic a esta
         * caja: la foto deja de ser el destino del evento y no se abre nunca.
         * Capturando sólo cuando ya hay arrastre, un clic limpio conserva su
         * destino y el arrastre sigue funcionando aunque el puntero se salga.
         */
        if (!capturado && arrastrado > UMBRAL_ARRASTRE) {
            capturado = true;
            tira.setPointerCapture(evento.pointerId);
            tira.classList.add('is-arrastrando');
        }

        if (capturado) {
            desplazamiento = inicioDesplazamiento - recorrido;
        }
    });

    const soltar = (evento) => {
        if (!arrastrando) {
            return;
        }

        arrastrando = false;

        if (capturado) {
            tira.releasePointerCapture?.(evento.pointerId);
            capturado = false;
        }

        tira.classList.remove('is-arrastrando');
    };

    tira.addEventListener('pointerup', soltar);
    tira.addEventListener('pointercancel', soltar);

    /*
     * Al terminar un arrastre el navegador manda un 'click' sobre la foto que
     * quedó bajo el dedo. Sin esto, mover la tira abriría una foto al soltar.
     */
    tira.addEventListener('click', (evento) => {
        if (arrastrado > UMBRAL_ARRASTRE) {
            evento.preventDefault();
            evento.stopPropagation();
            arrastrado = 0;
        }
    }, true);

    /*
     * A partir de aquí el desplazamiento lo lleva el script: se apaga el scroll
     * que el CSS deja puesto para cuando el JS no corre.
     */
    tira.classList.add('is-vivo');

    medir();
    colocar();
    requestAnimationFrame(avanzar);

    // Al cambiar el ancho cambian los sitios de reposo.
    window.addEventListener('resize', medir);

    /*
     * Las fotos son 'lazy': al cargarse pueden cambiar el alto de la tira y,
     * con él, las posiciones medidas.
     */
    marquesina.querySelectorAll('img').forEach((img) => {
        if (!img.complete) {
            img.addEventListener('load', medir, { once: true });
        }
    });
}

/* -------------------------------------------------------------------------
 | La foto en grande
 * ---------------------------------------------------------------------- */

/**
 * El visor se arma SÓLO sobre la primera lista: si se armara sobre las dos,
 * cada foto saldría repetida al pasar de una a otra dentro de la modal. Las
 * fotos de la copia abren el visor en la posición de su original.
 */
function armarVisor(tira, original) {
    const visor = new PhotoSwipeLightbox({
        gallery: original,
        children: '.td-story__link',
        // El grueso de la librería sólo se descarga cuando alguien abre una foto.
        pswpModule: () => import('photoswipe'),
        bgOpacity: 0.95,
        closeTitle: 'Cerrar',
        zoomTitle: 'Acercar',
        arrowPrevTitle: 'Foto anterior',
        arrowNextTitle: 'Foto siguiente',
        errorMsg: 'No se pudo cargar la foto.',
    });

    visor.addFilter('domItemData', (itemData, element) => {
        const img = element.querySelector('img');

        if (img?.naturalWidth) {
            itemData.width = img.naturalWidth;
            itemData.height = img.naturalHeight;
        }

        return itemData;
    });

    visor.init();

    tira.querySelector('[data-td-story-copia]')?.addEventListener('click', (evento) => {
        const enlace = evento.target.closest('.td-story__link');

        if (!enlace) {
            return;
        }

        evento.preventDefault();
        visor.loadAndOpen(Number(enlace.dataset.indice) || 0);
    });
}
