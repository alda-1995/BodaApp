/**
 * Una tira que gira en bucle y se puede arrastrar.
 *
 * La usan dos bloques de esta plantilla —"Nuestra historia" y la galería— y es
 * la misma mecánica en los dos: la lista va dos veces en el DOM y el
 * desplazamiento se envuelve a la mitad, que es justo donde la copia repite a
 * la primera, así que da vueltas sin fin y sin salto.
 *
 * Lo que cambia entre un bloque y otro es cómo se coloca cada pieza según
 * dónde esté, y eso lo decide quien llama (`transformar`).
 *
 * ¿Por qué no Swiper, que ya viene cargado? Porque en modo bucle reordena las
 * diapositivas, y aquí la posición de cada foto —el arco, la escalera— depende
 * de su sitio en la fila: al reordenarlas, el dibujo saltaría.
 */

/** A partir de cuánto se considera arrastre y no un clic. */
export const UMBRAL_ARRASTRE = 6; // px

/**
 * @param {HTMLElement} tira       la ventana que recorta y escucha el arrastre
 * @param {object}      opciones
 * @param {string}      opciones.pieza      selector de cada pieza de la tira
 * @param {number}      opciones.velocidad  px por segundo que avanza sola
 * @param {Function}    [opciones.transformar] (pieza, t) => string; t va de -1 a 1
 * @returns {{ arrastrado: () => number }} cuánto se arrastró en el último gesto
 */
export function armarMarquesina(tira, { pieza, velocidad, transformar }) {
    const carril = tira.querySelector('[data-marquesina]');
    const piezas = carril ? [...carril.querySelectorAll(pieza)] : [];

    if (!carril || piezas.length === 0) {
        return { arrastrado: () => 0 };
    }

    const menosMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)');

    let desplazamiento = 0;
    let mitad = 0;
    let centros = [];
    let arrastrando = false;
    let arrastrado = 0;
    let capturado = false;
    let ultimoFotograma = 0;

    /*
     * Las posiciones de reposo se miden una vez y se guardan: leerlas en cada
     * fotograma obligaría al navegador a recalcular la maqueta 60 veces por
     * segundo sólo para saber algo que no cambia.
     */
    const medir = () => {
        mitad = carril.getBoundingClientRect().width / 2;
        centros = piezas.map((p) => p.offsetLeft + p.offsetWidth / 2);
    };

    const colocar = () => {
        const centroPantalla = tira.getBoundingClientRect().width / 2;

        carril.style.transform = `translateX(${-desplazamiento}px)`;

        if (!transformar) {
            return;
        }

        piezas.forEach((p, i) => {
            // Dónde cae su centro dentro de la ventana, de -1 (izquierda) a 1.
            const x = centros[i] - desplazamiento;
            const t = Math.max(-1.6, Math.min(1.6, (x - centroPantalla) / centroPantalla));

            p.style.transform = transformar(p, t);
        });
    };

    const avanzar = (ahora) => {
        const delta = ultimoFotograma ? (ahora - ultimoFotograma) / 1000 : 0;
        ultimoFotograma = ahora;

        if (!arrastrando && !menosMovimiento.matches && !tira.matches(':hover')) {
            desplazamiento += velocidad * delta;
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
     * Al terminar un arrastre el navegador manda un 'click' sobre la pieza que
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
    carril.querySelectorAll('img').forEach((img) => {
        if (!img.complete) {
            img.addEventListener('load', medir, { once: true });
        }
    });

    return { arrastrado: () => arrastrado };
}
