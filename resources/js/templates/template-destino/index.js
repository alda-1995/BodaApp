/**
 * JS de la plantilla "Boda Destino".
 *
 * Sólo lo que sus bloques necesitan para funcionar, y nada tomado del JS del
 * panel. Del visor de fotos entran aquí sus estilos y el arranque, que son
 * livianos; el grueso se descarga cuando alguien abre una foto y no antes.
 */
import 'photoswipe/style.css';

import { initCalendar } from './calendar.js';
import { initClipboard } from './clipboard.js';
import { initCountdown } from './countdown.js';
import { initGallery } from './gallery.js';
import { initRsvp } from './rsvp.js';
import { initStory } from './story.js';

document.addEventListener('DOMContentLoaded', () => {
    initCalendar();
    initClipboard();
    initCountdown();
    initGallery();
    initRsvp();
    initStory();
});
