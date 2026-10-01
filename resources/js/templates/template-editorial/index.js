/**
 * JS de la plantilla "Boda Editorial".
 *
 * Es sobria a propósito: no carga animaciones ni librerías pesadas. Sólo lo que
 * sus bloques necesitan para funcionar, y nada tomado del JS del panel.
 */
import { initClipboard } from './clipboard.js';
import { initGallery } from './gallery.js';
import { initRsvp } from './rsvp.js';
import { initStory } from './story.js';
import { initTransport } from './transport.js';

document.addEventListener('DOMContentLoaded', () => {
    initClipboard();
    initGallery();
    initRsvp();
    initStory();
    initTransport();
});
