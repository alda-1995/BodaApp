<div class="bg-white py-16 md:pb-20 md:pt-10 lg:pb-24 lg:pt-12">
    <div class="container">
        <div class="max-w-lg mx-auto mb-8 md:mb-12">
            <h2 class="text-complement400 font-secondary text-size-title text-center">Todo lo que necesitas saber para
                acompañarnos en este gran viaje</h2>
        </div>
        @php
            $questions = [
                ['q' => '¿Dónde será la boda?', 'a' => 'Ubicación por confirmar.'],
                ['q' => '¿Hay código de vestimenta?', 'a' => 'Formal elegante.'],
                ['q' => '¿Puedo llevar acompañante o niños?', 'a' => 'Sí, pero avísanos.'],
                ['q' => '¿Dónde nos hospedamos? ¿Recomiendan algún hotel?', 'a' => 'Sí, pronto compartiremos una lista con opciones cercanas. Escríbenos si necesitas ayuda para reservar.'],
                ['q' => '¿Hasta cuándo puedo confirmar asistencia?', 'a' => 'Por favor antes del 15 de agosto.'],
                ['q' => '¿Hay mesa de regalos?', 'a' => 'Sí, te compartimos los detalles aquí.'],
            ];
        @endphp
        <div class="max-w-lg mx-auto">
            <div class="overflow-hidden rounded-xl border border-complement400">
                <div x-data="{ openIndex: 0 }"
                    class="max-w-xl mx-auto rounded-xl border border-gray-300 overflow-hidden">
                    @foreach ($questions as $index => $item)
                        <div class="border-t border-dashed border-gray-300 first:border-t-0"
                            :class="{ 'bg-[#f6ede8]': openIndex === {{ $index }} }">
                            <button type="button" class="w-full px-6 py-5 flex justify-between items-center text-left"
                                @click="openIndex === {{ $index }} ? openIndex = null : openIndex = {{ $index }}">
                                <span class="parrafo font-secondary text-complement300">{{ $item['q'] }}</span>
                                <svg :class="{ 'rotate-180': openIndex === {{ $index }} }"
                                    class="w-4 h-4 text-[#302b27] transition-transform duration-300" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="openIndex === {{ $index }}" x-collapse>
                                <div class="px-6 pb-6 pt-1 text-[14px] text-[#302b27] leading-snug">
                                    {{ $item['a'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>