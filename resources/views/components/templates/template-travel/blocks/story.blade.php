@props(['section', 'context'])

<section class="tv-section tv-story" id="historia">
    <div class="tv-container">
        <div class="tv-narrow tv-center">
            <h2 class="tv-heading text-animated-opacity-split">{{ $section->get('title') }}</h2>
            <h3 class="tv-story__subtitle text-animated-opacity-split">{{ $section->get('subtitle') }}</h3>
        </div>

        <div class="tv-story__box">
            {{-- .card-travel abre el modal (modalPosts en animations.js). --}}
            <div class="card-travel" data-slide="0">
                <img src="{{ asset('images/assets-travel/caja-fondo.png') }}" alt="Caja de recuerdos">
                <div class="box-top">
                    <img src="{{ asset('images/assets-travel/caja-tapa.png') }}" alt="Tapa de la caja">
                </div>
            </div>
        </div>

        <h3 class="tv-story__hint">{{ $section->get('action_text') }}</h3>
    </div>
</section>

{{--
    Recuerdos de la pareja: se abre al tocar la caja.

    Cada postal se escribe imagen por imagen (sin ciclos) para poder decidir
    después cuáles serán administrables desde el panel. Por ahora todas son
    las imágenes de ejemplo de la plantilla.
--}}
<div class="tv-modal modal-posts-slides" id="modal-slides-travel">
    <div class="tv-modal__bg" style="background-image: url('{{ asset('images/assets-travel/fondo-modal.jpg') }}');"></div>

    <div class="swiper sliderModalTravels tv-modal__slider">
        <div class="swiper-wrapper">

            {{-- ---------------------------------------------------------
             | Postal 1: Ciudad de México
             * -------------------------------------------------------- --}}
            <div class="swiper-slide">
                <div class="tv-postal tv-postal--cdmx">
                    <button type="button" class="close-modal-content tv-modal__close">
                        <span>Cerrar</span>
                        <x-icons.close />
                    </button>

                    <div class="tv-container">
                        <div class="tv-cdmx__top">
                            {{-- disco (sólo móvil) --}}
                            <img class="tv-cdmx__disco-mobile" src="{{ asset('images/assets-travel/modalone/discocdmx.png') }}" alt="">
                            {{-- postal principal --}}
                            <img class="tv-cdmx__postal1" src="{{ asset('images/assets-travel/modalone/postal1.png') }}" alt="Postal de la Ciudad de México">
                            <div class="tv-cdmx__sticker">
                                {{-- calcomanía --}}
                                <img class="postal-scale" src="{{ asset('images/assets-travel/modalone/sticker.png') }}" alt="">
                            </div>
                        </div>

                        <div class="tv-cdmx__mid">
                            <div class="tv-cdmx__mid-inner">
                                {{-- segunda postal --}}
                                <img class="tv-cdmx__postal2" src="{{ asset('images/assets-travel/modalone/postal2.png') }}" alt="Segunda postal">
                                {{-- disco (escritorio) --}}
                                <img class="tv-cdmx__disco" src="{{ asset('images/assets-travel/modalone/discocdmx.png') }}" alt="">

                                <div class="tv-cdmx__tickets">
                                    {{-- boleto 1 --}}
                                    <img class="bolet1" src="{{ asset('images/assets-travel/modalone/boleto1.png') }}" alt="">
                                    <div class="tv-cdmx__boleto2">
                                        {{-- boleto 2 --}}
                                        <img class="bolet2" src="{{ asset('images/assets-travel/modalone/boleto2.png') }}" alt="">
                                    </div>
                                </div>

                                <div class="tv-cdmx__pareja">
                                    {{-- foto de la pareja --}}
                                    <img class="bolet1" src="{{ asset('images/assets-travel/modalone/parejacdmx.png') }}" alt="La pareja en la Ciudad de México">
                                </div>
                            </div>

                            <div class="tv-cdmx__envoltura">
                                {{-- envoltura --}}
                                <img class="envolve" src="{{ asset('images/assets-travel/modalone/envoltura.png') }}" alt="">
                            </div>
                            <div class="tv-cdmx__festival">
                                {{-- boleto del festival --}}
                                <img class="bolet-green" src="{{ asset('images/assets-travel/modalone/boletofestival.png') }}" alt="">
                            </div>
                        </div>

                        <div class="tv-cdmx__bottom">
                            <div class="tv-cdmx__anastasia">
                                {{-- boleto Anastasia --}}
                                <img src="{{ asset('images/assets-travel/modalone/anastasia.png') }}" alt="">
                            </div>
                            {{-- boleto Da Vinci --}}
                            <img src="{{ asset('images/assets-travel/modalone/boletodavinci.png') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ---------------------------------------------------------
             | Postal 2: Tampa
             * -------------------------------------------------------- --}}
            <div class="swiper-slide">
                <div class="tv-postal tv-postal--tampa">
                    <button type="button" class="close-modal-content tv-modal__close">
                        <span>Cerrar</span>
                        <x-icons.close />
                    </button>

                    <div class="tv-container tv-tampa__wrap">
                        <div class="tv-tampa__postal">
                            {{-- postal principal --}}
                            <img src="{{ asset('images/assets-travel/modaltwo/postal.png') }}" alt="Postal de Tampa">
                        </div>

                        <div class="tv-tampa__spacer"></div>

                        <div class="tv-tampa__group">
                            <div class="tv-tampa__stack">
                                {{-- postal con el texto --}}
                                <img class="tv-tampa__postalinfo" src="{{ asset('images/assets-travel/modaltwo/postalinfo.png') }}" alt="Recuerdo del viaje">

                                <div class="tv-tampa__cassette">
                                    {{-- cassette --}}
                                    <img class="postal-scale" src="{{ asset('images/assets-travel/modaltwo/cassette.png') }}" alt="">
                                </div>
                                <div class="tv-tampa__pareja1">
                                    {{-- foto de la pareja 1 --}}
                                    <img src="{{ asset('images/assets-travel/modaltwo/pareja1.png') }}" alt="La pareja en Tampa">
                                </div>
                                <div class="tv-tampa__boleto1">
                                    {{-- boleto 1 --}}
                                    <img class="bolet1" src="{{ asset('images/assets-travel/modaltwo/boleto1.png') }}" alt="">
                                </div>
                                <div class="tv-tampa__boleto2">
                                    {{-- boleto 2 --}}
                                    <img class="bolet2" src="{{ asset('images/assets-travel/modaltwo/boleto2.png') }}" alt="">
                                </div>
                                <div class="tv-tampa__pareja2">
                                    {{-- foto de la pareja 2 --}}
                                    <img class="bolet1" src="{{ asset('images/assets-travel/modaltwo/pareja2.png') }}" alt="La pareja en Tampa">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ---------------------------------------------------------
             | Postal 3: París
             * -------------------------------------------------------- --}}
            <div class="swiper-slide">
                <div class="tv-postal tv-postal--paris">
                    <button type="button" class="close-modal-content tv-modal__close">
                        <span>Cerrar</span>
                        <x-icons.close />
                    </button>

                    <div class="tv-container tv-paris__wrap">
                        <div class="tv-paris__top">
                            <div class="tv-paris__postal-wrap">
                                <div class="tv-paris__pareja1">
                                    {{-- foto de la pareja 1 --}}
                                    <img src="{{ asset('images/assets-travel/modalthree/pareja1.png') }}" alt="La pareja en París">
                                </div>
                                {{-- postal principal --}}
                                <img class="tv-paris__postal1" src="{{ asset('images/assets-travel/modalthree/postal1.png') }}" alt="Postal de París">
                                <div class="tv-paris__pase">
                                    {{-- pase de abordar --}}
                                    <img class="bolet1" src="{{ asset('images/assets-travel/modalthree/pase.png') }}" alt="">
                                </div>
                            </div>

                            <div class="tv-paris__pareja5">
                                {{-- foto de la pareja 5 --}}
                                <img src="{{ asset('images/assets-travel/modalthree/pareja5.png') }}" alt="La pareja en París">
                                <div class="tv-paris__pareja6">
                                    {{-- foto de la pareja 6 --}}
                                    <img class="postal-scale" src="{{ asset('images/assets-travel/modalthree/pareja6.png') }}" alt="La pareja en París">
                                </div>
                            </div>
                        </div>

                        <div class="tv-paris__spacer"></div>

                        <div class="tv-paris__bottom">
                            <div class="tv-paris__postal2-wrap">
                                <div class="tv-paris__stack">
                                    {{-- segunda postal --}}
                                    <img class="tv-paris__postal2" src="{{ asset('images/assets-travel/modalthree/postal2.png') }}" alt="Segunda postal de París">
                                    <div class="tv-paris__candado">
                                        {{-- candado del puente --}}
                                        <img src="{{ asset('images/assets-travel/candado.png') }}" alt="">
                                    </div>
                                </div>
                            </div>

                            <div class="tv-paris__pareja2">
                                {{-- foto de la pareja 2 --}}
                                <img src="{{ asset('images/assets-travel/modalthree/pareja2.png') }}" alt="La pareja en París">
                            </div>
                            <div class="tv-paris__pareja3">
                                {{-- foto de la pareja 3 --}}
                                <img src="{{ asset('images/assets-travel/modalthree/pareja3.png') }}" alt="La pareja en París">
                                <div class="tv-paris__pareja4">
                                    {{-- foto de la pareja 4 --}}
                                    <img src="{{ asset('images/assets-travel/modalthree/pareja4.png') }}" alt="La pareja en París">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ---------------------------------------------------------
             | Postal 4: Ámsterdam
             * -------------------------------------------------------- --}}
            <div class="swiper-slide">
                <div class="tv-postal tv-postal--amsterdam">
                    <button type="button" class="close-modal-content tv-modal__close">
                        <span>Cerrar</span>
                        <x-icons.close />
                    </button>

                    <div class="tv-container tv-ams__wrap">
                        <div class="tv-ams__stage">
                            <div class="tv-ams__column">
                                {{-- foto de la pareja 1 --}}
                                <img class="tv-ams__pareja1" src="{{ asset('images/assets-travel/modalfour/pareja1.png') }}" alt="La pareja en Ámsterdam">
                                {{-- foto de la pareja 2 --}}
                                <img class="tv-ams__pareja2" src="{{ asset('images/assets-travel/modalfour/pareja2.png') }}" alt="La pareja en Ámsterdam">

                                <div class="tv-ams__pareja3">
                                    {{-- foto de la pareja 3 --}}
                                    <img class="bolet1" src="{{ asset('images/assets-travel/modalfour/pareja3.png') }}" alt="La pareja en Ámsterdam">
                                </div>
                                <div class="tv-ams__pareja4">
                                    {{-- foto de la pareja 4 --}}
                                    <img class="postal-scale" src="{{ asset('images/assets-travel/modalfour/pareja4.png') }}" alt="La pareja en Ámsterdam">
                                </div>
                            </div>

                            <div class="tv-ams__pareja9">
                                {{-- foto de la pareja 9 --}}
                                <img class="bolet2" src="{{ asset('images/assets-travel/modalfour/pareja9.png') }}" alt="La pareja en Ámsterdam">
                            </div>
                            <div class="tv-ams__boleto">
                                {{-- boleto --}}
                                <img src="{{ asset('images/assets-travel/modalfour/boleto.png') }}" alt="">
                            </div>
                            <div class="tv-ams__pareja10">
                                {{-- foto de la pareja 10 --}}
                                <img src="{{ asset('images/assets-travel/modalfour/pareja10.png') }}" alt="La pareja en Ámsterdam">
                            </div>

                            <div class="tv-ams__postal">
                                {{-- postal principal --}}
                                <img src="{{ asset('images/assets-travel/modalfour/postal.png') }}" alt="Postal de Ámsterdam">
                            </div>

                            <div class="tv-ams__spacer"></div>

                            <div class="tv-ams__bottom">
                                <div class="tv-ams__postalinfo-wrap">
                                    {{-- postal con el texto --}}
                                    <img class="tv-ams__postalinfo" src="{{ asset('images/assets-travel/modalfour/postalinfo.png') }}" alt="Recuerdo del viaje">
                                </div>

                                <div class="tv-ams__cluster">
                                    {{-- foto de la pareja 5 --}}
                                    <img class="tv-ams__pareja5 postal-scale" src="{{ asset('images/assets-travel/modalfour/pareja5.png') }}" alt="La pareja en Ámsterdam">

                                    <div class="tv-ams__pareja6">
                                        {{-- foto de la pareja 6 --}}
                                        <img src="{{ asset('images/assets-travel/modalfour/pareja6.png') }}" alt="La pareja en Ámsterdam">
                                        <div class="tv-ams__pareja7">
                                            {{-- foto de la pareja 7 --}}
                                            <img src="{{ asset('images/assets-travel/modalfour/pareja7.png') }}" alt="La pareja en Ámsterdam">
                                        </div>
                                        <div class="tv-ams__pareja8">
                                            {{-- foto de la pareja 8 --}}
                                            <img class="postal-scale" src="{{ asset('images/assets-travel/modalfour/pareja8.png') }}" alt="La pareja en Ámsterdam">
                                        </div>
                                    </div>
                                </div>

                                <div class="tv-ams__pareja11">
                                    {{-- foto de la pareja 11 --}}
                                    <img src="{{ asset('images/assets-travel/modalfour/pareja11.png') }}" alt="La pareja en Ámsterdam">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="tv-modal__hint" id="indicadorMobileSlide">
        <img src="{{ asset('images/assets-travel/indicador-mobile.gif') }}" alt="Desliza para ver más">
    </div>

    <div class="swiper-button-prev prev-int-travel tv-modal__nav tv-modal__nav--prev">
        <img src="{{ asset('images/assets-travel/arrow-left-big.png') }}" alt="Anterior">
    </div>
    <div class="swiper-button-next next-int-travel tv-modal__nav tv-modal__nav--next">
        <img src="{{ asset('images/assets-travel/arrow-rigth-big.png') }}" alt="Siguiente">
    </div>
</div>
