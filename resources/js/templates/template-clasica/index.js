/**
 * JS de la plantilla "Boda Clásica".
 *
 * Sólo lo que sus bloques necesitan, y nada tomado del JS del panel. El plegado
 * de las preguntas lo lleva Alpine, que ya viene en el layout de invitaciones;
 * aquí sólo entra la cuenta regresiva, el formulario y el visor de fotos.
 *
 * Del visor entran sus estilos y el arranque, que son livianos; el grueso se
 * descarga cuando alguien abre una foto y no antes.
 */
import 'photoswipe/style.css';
import PhotoSwipeLightbox from 'photoswipe/lightbox';

import { initCountdown } from './countdown.js';
import { initMarco } from './marco.js';
import { initRsvp } from './rsvp.js';
import { initVisor } from './visor.js';

document.addEventListener('DOMContentLoaded', () => {
    initCountdown();
    initMarco();
    initRsvp();
    initVisor(PhotoSwipeLightbox);
});
