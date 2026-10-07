/**
 * Nuestra historia: una tira de polaroids que gira describiendo un arco.
 *
 * El arco no se puede hacer con una animación de CSS porque la altura y el giro
 * de cada foto no dependen de su sitio en la lista, sino de DÓNDE ESTÁ en ese
 * momento: la del centro va arriba y derecha, y las de los lados caen y se
 * inclinan hacia afuera. Eso cambia en cada fotograma, así que lo calcula el JS.
 *
 * El girar, el arrastrar y la vuelta sin salto los pone marquesina.js, que es
 * la misma mecánica que usa la galería. Aquí sólo se dice cómo se coloca cada
 * foto según dónde esté.
 *
 * Sin JS no queda rota: el CSS deja las fotos con un giro fijo y la tira se
 * recorre a mano.
 */
import PhotoSwipeLightbox from 'photoswipe/lightbox';

import { armarMarquesina } from './marquesina.js';
import { armarVisorConCopia } from './visor.js';

/** Forma del arco: cuánto caen las de las orillas y cuánto se inclinan. */
const CAIDA_MAXIMA = 70; // px
const GIRO_MAXIMO = 14; // grados
/** Lo que avanza sola, en píxeles por segundo. */
const VELOCIDAD = 38;

export function initStory() {
    document.querySelectorAll('[data-td-story]').forEach((tira) => {
        armarVisorConCopia(tira, PhotoSwipeLightbox, {
            original: '[data-td-story-original]',
            copia: '[data-td-story-copia]',
            enlace: '.td-story__link',
        });

        armarMarquesina(tira, {
            pieza: '.td-story__card',
            velocidad: VELOCIDAD,
            // Parábola: 0 en el centro, máximo en las orillas. Es el arco.
            transformar: (_pieza, t) =>
                `translateY(${(t * t * CAIDA_MAXIMA).toFixed(2)}px) rotate(${(t * GIRO_MAXIMO).toFixed(2)}deg)`,
        });
    });
}
