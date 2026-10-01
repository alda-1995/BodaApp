<section class="pt-16 pb-32 bg-[#F3EFEE]">
    <div class="container">
        <div class="max-w-lg mx-auto mb-8 md:mb-12 flex-col flex items-center text-center">
            <img src="{{ asset('images/assets-travel/monograma-footer.png') }}" class="max-w-[120px] mb-6 md:mb-8" alt="img logo2">
            <h2 class="text-size-title font-main text-main text-animated-opacity-split">Todo lo que necesitas saber para acompañarnos en este gran
                viaje</h2>
        </div>
        <div class="max-w-lg mx-auto">
            <div class="overflow-hidden rounded-[5px]">
                <div x-data="{ openIndex: 0 }" class="overflow-hidden">
                    <div class="w-full">
                        <button type="button"
                            class="relative bg-center bg-cover bg-no-repeat w-full px-6 py-5 flex justify-between items-center text-left"
                            style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');"
                            @click="openIndex === 1 ? openIndex = null : openIndex = 1">
                            <span class="parrafo text-parrafo font-main text-main">¿Hay una tarifa especial de
                                hospedaje para los invitados de la boda?</span>
                            <svg :class="{ 'rotate-180': openIndex === 1 }"
                                class="w-4 h-4 text-current transition-transform duration-300" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            <div class="absolute bottom-0 left-0 w-full">
                                <img src="{{ asset('images/assets-travel/line.png') }}" class="w-full"
                                    alt="line accordeon">
                            </div>
                        </button>

                        <div x-show="openIndex === 1" x-collapse>
                            <div
                                class="px-6 py-6 parrafo text-parrafo font-main bg-cover bg-center bg-no-repeat text-accent leading-snug" style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');">
                                <ul>
                                    <li>Código de reservación en Hotel Barceló Santa Fé: Boda Alfonso y Elena</li>
                                    <li>Reservaciones vía e-mail: <a href="mailto:mexicosantafe.res@barcelo.com"
                                            class="underline">mexicosantafe.res@barcelo.com</a>
                                    </li>
                                    <li>Reservaciones vía telefónica: <a href="tel:5550041616" class="underline">55 5004
                                            1616</a> Ext. 2812</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <button type="button"
                            class="relative bg-center bg-cover bg-no-repeat w-full px-6 py-5 flex justify-between items-center text-left"
                            style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');"
                            @click="openIndex === 2 ? openIndex = null : openIndex = 2">
                            <span class="parrafo text-parrafo font-main text-main">¿Hay otras sugerencias de
                                hospedaje?</span>
                            <svg :class="{ 'rotate-180': openIndex === 2 }"
                                class="w-4 h-4 text-current transition-transform duration-300" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            <div class="absolute bottom-0 left-0 w-full">
                                <img src="{{ asset('images/assets-travel/line.png') }}" class="w-full"
                                    alt="line accordeon">
                            </div>
                        </button>

                        <div x-show="openIndex === 2" x-collapse>
                            <div
                                class="px-6 py-6 parrafo text-parrafo font-main bg-cover bg-center bg-no-repeat text-accent leading-snug" style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');">
                                <ul>
                                    <li>Holiday Inn Santa Fé</li>
                                    <li>Holiday Inn Express Santa Fé</li>
                                    <li>Hotel Novotel Santa Fé</li>
                                    <li>Hilton Santa Fé</li>
                                    <li>JW Marriot Santa Fé</li>
                                    <li>Double Tree Santa Fé</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <button type="button"
                            class="relative bg-center bg-cover bg-no-repeat w-full px-6 py-5 flex justify-between items-center text-left"
                            style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');"
                            @click="openIndex === 3 ? openIndex = null : openIndex = 3">
                            <span class="parrafo text-parrafo font-main text-main">¿Puedo llevar niños?</span>
                            <svg :class="{ 'rotate-180': openIndex === 3 }"
                                class="w-4 h-4 text-current transition-transform duration-300" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            <div class="absolute bottom-0 left-0 w-full">
                                <img src="{{ asset('images/assets-travel/line.png') }}" class="w-full"
                                    alt="line accordeon">
                            </div>
                        </button>

                        <div x-show="openIndex === 3" x-collapse>
                            <div
                                class="px-6 py-6 parrafo text-parrafo font-main bg-cover bg-center bg-no-repeat text-accent leading-snug" style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');">
                                <p>Este día especial lo hemos diseñado como una celebración solo para adultos. Gracias
                                    por tu comprensión y por ser parte de este momento único.</p>
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <button type="button"
                            class="relative bg-center bg-cover bg-no-repeat w-full px-6 py-5 flex justify-between items-center text-left"
                            style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');"
                            @click="openIndex === 4 ? openIndex = null : openIndex = 4">
                            <span class="parrafo text-parrafo font-main text-main">¿Cuánto tiempo tengo para confirmar
                                asistencia?</span>
                            <svg :class="{ 'rotate-180': openIndex === 4 }"
                                class="w-4 h-4 text-current transition-transform duration-300" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            <div class="absolute bottom-0 left-0 w-full">
                                <img src="{{ asset('images/assets-travel/line.png') }}" class="w-full"
                                    alt="line accordeon">
                            </div>
                        </button>

                        <div x-show="openIndex === 4" x-collapse>
                            <div
                                class="px-6 py-6 parrafo text-parrafo font-main bg-cover bg-center bg-no-repeat text-accent leading-snug" style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');">
                                <p>Fecha límite 28 de Enero de 2026.</p>
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <button type="button"
                            class="relative bg-center bg-cover bg-no-repeat w-full px-6 py-5 flex justify-between items-center text-left"
                            style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');"
                            @click="openIndex === 5 ? openIndex = null : openIndex = 5">
                            <span class="parrafo text-parrafo font-main text-main">¿Hay opciones de transporte y
                                estacionamiento para los invitados?</span>
                            <svg :class="{ 'rotate-180': openIndex === 5 }"
                                class="w-4 h-4 text-current transition-transform duration-300" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            <div class="absolute bottom-0 left-0 w-full">
                                <img src="{{ asset('images/assets-travel/line.png') }}" class="w-full"
                                    alt="line accordeon">
                            </div>
                        </button>

                        <div x-show="openIndex === 5" x-collapse>
                            <div
                                class="px-6 py-6 parrafo text-parrafo font-main bg-cover bg-center bg-no-repeat text-accent leading-snug" style="background-image: url('{{ asset('images/assets-travel/fondomask.png') }}');">
                                <ul>
                                    <li>Iglesia: Parquímetro en las calles aledañas o estacionamiento público en calle Cordillera de los Andes 531. </li>
                                    <li>Hotel Barceló Santa Fé: Cuenta con estacionamiento propio con tarifa especial por día.</li>
                                    <li>En caso de no utilizar vehículo propio, sugerimos utilizar Uber.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>