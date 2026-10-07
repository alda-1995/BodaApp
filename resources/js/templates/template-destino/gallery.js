/**
 * Galería: una tira de fotos en escalera que gira en bucle.
 *
 * La escalera sube hacia la derecha y se repite, así que el escalón no puede
 * salir del sitio de la foto en la lista: si creciera sin fin, al dar la vuelta
 * habría un salto enorme. Sale de DÓNDE ESTÁ en la ventana, igual que el arco
 * de "Nuestra historia", y por eso lo calcula el JS en cada fotograma.
 *
 * El girar, el arrastrar y la vuelta sin salto los pone marquesina.js.
 *
 * Sin JS no queda rota: el CSS deja la escalera fija y la tira se recorre a
 * mano, y cada foto sigue siendo un enlace que abre la imagen en el navegador.
 */
import PhotoSwipeLightbox from 'photoswipe/lightbox';

import { armarMarquesina } from './marquesina.js';
import { armarVisorConCopia } from './visor.js';

/** Cuánto sube de un lado a otro de la ventana. */
const SUBIDA = 150; // px
/** Lo que avanza sola, en píxeles por segundo. */
const VELOCIDAD = 30;

export function initGallery() {
    document.querySelectorAll('[data-td-gallery]').forEach((tira) => {
        armarVisorConCopia(tira, PhotoSwipeLightbox, {
            original: '[data-td-gallery-original]',
            copia: '[data-td-gallery-copia]',
            enlace: '.td-gallery__link',
        });

        armarMarquesina(tira, {
            pieza: '.td-gallery__item',
            velocidad: VELOCIDAD,
            /*
             * Sube hacia la derecha: la de la izquierda es la más baja y la de
             * la derecha la más alta, como en el diseño. Es una recta, no una
             * curva, para que la escalera se lea como escalera.
             */
            transformar: (_pieza, t) => `translateY(${(-t * SUBIDA).toFixed(2)}px)`,
        });
    });
}
