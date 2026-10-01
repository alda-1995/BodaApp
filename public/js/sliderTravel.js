const swiperTravel = new Swiper('.travelSwiper', {
  slidesPerView: 1,
  spaceBetween: 16,
  speed: 600,
  breakpoints: {
    1024: {
      slidesPerView: 2,
      spaceBetween: 20,
    },
    1280: {
      slidesPerView: 2,
      spaceBetween: 20,
    }
  }
});

const gallerySlider = new Swiper('.gallerySlider', {
  slidesPerView: 1,
  spaceBetween: 16,
  breakpoints: {
    1024: {
      slidesPerView: 2,
      spaceBetween: 20,
    },
    1280: {
      slidesPerView: 3,
      spaceBetween: 20,
    }
  },
  navigation: {
    nextEl: ".next-gallery",
    prevEl: ".prev-gallery",
  },
});