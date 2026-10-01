/**
 * JS de la plantilla "Boda Barco".
 *
 * Es el único archivo que carga el layout para esta plantilla, y trae todo lo
 * que necesita: sus librerías, sus animaciones y el comportamiento de sus
 * bloques. No toma nada del JS del panel.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';

import { initHomeAnimations } from './banner.js';
import { initTemplateAnimations } from './animations.js';
import { initCountdown } from './countdown.js';
import { initClipboard } from './clipboard.js';
import { initSliders } from './sliders.js';
import { initRsvp } from './rsvp.js';
import { initCalendarButtons } from './calendar.js';

gsap.registerPlugin(ScrollTrigger);

// banner.js y animations.js trabajan con estos globales.
window.gsap = gsap;
window.ScrollTrigger = ScrollTrigger;
window.PhotoSwipeLightbox = PhotoSwipeLightbox;
window.photoswipe = import('photoswipe');
window.isLg = window.matchMedia('only screen and (min-width: 1024px)').matches;

document.addEventListener('DOMContentLoaded', () => {
    initCountdown();
    initClipboard();
    initSliders();
    initRsvp();
    initCalendarButtons();

    // Los canvas del sobre y del itinerario devuelven promesas; el resto de
    // animaciones espera a que terminen de precargar.
    initTemplateAnimations(initHomeAnimations());
});
