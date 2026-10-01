// import './modal.js';
import flatpickr from "flatpickr";
import { Spanish } from "flatpickr/dist/l10n/es.js";
import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';

gsap.registerPlugin(ScrollTrigger);
const isLg = window.matchMedia("only screen and (min-width: 1024px)").matches;

window.isLg = isLg;
window.gsap = gsap;
window.ScrollTrigger = ScrollTrigger;
window.PhotoSwipeLightbox = PhotoSwipeLightbox;
window.photoswipe = import('photoswipe');

flatpickr(".only-date input", {
    dateFormat: "Y-m-d",
    enableTime: false,
    noCalendar: false,
    locale: Spanish
});

flatpickr(".only-time input", {
    dateFormat: "H:i",
    enableTime: true,
    noCalendar: true,
    locale: Spanish
});

document.addEventListener('DOMContentLoaded', () => {
    const AUTO_CLOSE_TIME = 1 * 60 * 1000;
    const FADE_OUT_DURATION = 500;

    const toastNotifications = document.querySelectorAll('.toast-notification');

    toastNotifications.forEach(toast => {
        const closeButton = toast.querySelector('.close-toast');
        closeButton.addEventListener('click', () => {
            closeToast(toast);
        });
        setTimeout(() => {
            closeToast(toast);
        }, AUTO_CLOSE_TIME);
    });

    const closeToast = (toastElement) => {
        toastElement.classList.remove('opacity-100');
        toastElement.classList.add('opacity-0', 'transition', `duration-${FADE_OUT_DURATION}`);
        setTimeout(() => {
            if (toastElement.parentNode) {
                toastElement.parentNode.removeChild(toastElement);
            }
        }, FADE_OUT_DURATION);
    }

    flatpickr(".only-date input", {
        dateFormat: "Y-m-d",
        enableTime: false,
        noCalendar: false,
        locale: "es"
    });
});

const toggleModalDelete = (id, url = null) => {
    const modal = document.getElementById(id);
    if (!modal) return;

    const form = modal.querySelector('form');
    
    if (url && form) {
        form.setAttribute('action', url);
    }
    modal.classList.toggle('hidden');
}

window.toggleModalDelete = toggleModalDelete;