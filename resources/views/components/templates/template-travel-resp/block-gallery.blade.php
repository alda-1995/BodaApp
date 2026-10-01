<section class="py-16 bg-[#F3EFEE]" id="galeria">
    <div class="container">
        <div class="max-w-lg mx-auto flex flex-col items-center text-center">
            <h2 class="font-main text-main text-size-main mb-4 text-animated-opacity-split">Nuestros recuerdos en fotos
            </h2>
            <h3 class="text-size-subtitle text-gray font-main text-animated-opacity-split">Pequeños instantes que
                guardamos con cariño, risas que
                aún se escuchan y miradas que lo dijeron todo.</h3>
        </div>
        <div class="mt-8 md:mt-10 lg:mt-12">
            <div class="bg-cover bg-no-repeat bg-center py-2 px-4 md:px-6 rounded-[5px] overflow-hidden"
                style="background-image: url('{{ asset('images/assets-travel/horizontal-background.png') }}');">
                <div class="swiper gallerySlider">
                    <div class="swiper-wrapper" id="my-gallery">
                        <div class="swiper-slide">
                            <a href="{{ asset('images/assets-travel/gallery/1.jpeg') }}" target="_blank">
                                <img src="{{ asset('images/assets-travel/gallery/1.jpeg') }}"
                                    class="max-w-full h-[250px] md:h-[300px] object-cover w-full object-center"
                                    alt="img gallery">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="{{ asset('images/assets-travel/gallery/2.jpg') }}" target="_blank">
                                <img src="{{ asset('images/assets-travel/gallery/2.jpg') }}"
                                    class="max-w-full h-[250px] md:h-[300px] object-cover w-full object-center"
                                    alt="img gallery">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="{{ asset('images/assets-travel/gallery/3.jpg') }}" target="_blank">
                                <img src="{{ asset('images/assets-travel/gallery/3.jpg') }}"
                                    class="max-w-full h-[250px] md:h-[300px] object-cover w-full object-center"
                                    alt="img gallery">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="{{ asset('images/assets-travel/gallery/4.jpg') }}" target="_blank">
                                <img src="{{ asset('images/assets-travel/gallery/4.jpg') }}"
                                    class="max-w-full h-[250px] md:h-[300px] object-cover w-full object-center"
                                    alt="img gallery">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="{{ asset('images/assets-travel/gallery/5.jpg') }}" target="_blank">
                                <img src="{{ asset('images/assets-travel/gallery/5.jpg') }}"
                                    class="max-w-full h-[250px] md:h-[300px] object-cover w-full object-center"
                                    alt="img gallery">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="{{ asset('images/assets-travel/gallery/6.jpg') }}" target="_blank">
                                <img src="{{ asset('images/assets-travel/gallery/6.jpg') }}"
                                    class="max-w-full h-[250px] md:h-[300px] object-cover w-full object-center"
                                    alt="img gallery">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="{{ asset('images/assets-travel/gallery/7.jpg') }}" target="_blank">
                                <img src="{{ asset('images/assets-travel/gallery/7.jpg') }}"
                                    class="max-w-full h-[250px] md:h-[300px] object-cover w-full object-center"
                                    alt="img gallery">
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="{{ asset('images/assets-travel/gallery/8.jpeg') }}" target="_blank">
                                <img src="{{ asset('images/assets-travel/gallery/8.jpeg') }}"
                                    class="max-w-full h-[250px] md:h-[300px] object-cover w-full object-center"
                                    alt="img gallery">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-6 md:mt-8">
            <div class="flex gap-4 md:gap-6 items-center justify-center">
                <div class="swiper-button-prev prev-gallery">
                    <img src="{{ asset('images/assets-travel/arrow-left.png') }}" class="max-w-full"
                        alt="img flecha izquierda">
                </div>
                <div class="swiper-button-next next-gallery">
                    <img src="{{ asset('images/assets-travel/arrow-rigth.png') }}" class="max-w-full"
                        alt="img flecha derecha">
                </div>
            </div>
        </div>
    </div>
</section>