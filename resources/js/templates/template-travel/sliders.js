/**
 * Carruseles de la plantilla (Swiper viene del CDN que carga el layout).
 */
export function initSliders() {
    if (typeof Swiper === 'undefined') {
        return;
    }

    if (document.querySelector('.travelSwiper')) {
        new Swiper('.travelSwiper', {
            slidesPerView: 1,
            spaceBetween: 16,
            speed: 600,
            breakpoints: {
                1024: { slidesPerView: 2, spaceBetween: 20 },
                1280: { slidesPerView: 2, spaceBetween: 20 },
            },
        });
    }

    if (document.querySelector('.gallerySlider')) {
        new Swiper('.gallerySlider', {
            slidesPerView: 1,
            spaceBetween: 16,
            breakpoints: {
                1024: { slidesPerView: 2, spaceBetween: 20 },
                1280: { slidesPerView: 3, spaceBetween: 20 },
            },
            navigation: {
                nextEl: '.next-gallery',
                prevEl: '.prev-gallery',
            },
        });
    }
}
