@props([
    'steps' => [],
    'currentStepKey' => null,
    'event',
    'progress' => [],
])

@php
    $stepStates = $progress['steps'] ?? [];
    $stepKeys = array_keys($steps);
@endphp

<nav
    aria-label="Pasos de configuración del evento"
    class="relative"
    x-data="{
        canLeft: false,
        canRight: false,
        dragging: false,
        moved: false,
        startX: 0,
        startScroll: 0,
        userScrolled: false,

        init() {
            this.center();

            // Si estilos o fuentes terminan de cargar después, se vuelve a centrar,
            // salvo que el usuario ya haya movido el stepper.
            window.addEventListener('load', () => {
                if (!this.userScrolled) this.center();
            }, { once: true });

            new ResizeObserver(() => this.update()).observe(this.$refs.scroller);
        },

        // Fija el paso actual al centro. Se mueve sólo el stepper, no la página
        // (scrollIntoView también desplazaría la ventana en vertical).
        center() {
            const el = this.$refs.scroller;
            const current = el.querySelector('[aria-current=step]');
            if (current) {
                el.scrollLeft = current.offsetLeft - (el.clientWidth - current.offsetWidth) / 2;
            }
            this.update();
        },

        update() {
            const el = this.$refs.scroller;
            this.canLeft = el.scrollLeft > 4;
            this.canRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 4;
        },

        scrollByPage(direction) {
            this.userScrolled = true;
            const el = this.$refs.scroller;
            el.scrollBy({ left: direction * el.clientWidth * 0.6, behavior: 'smooth' });
        },

        // Arrastre con mouse en escritorio; en táctil el scroll nativo ya funciona.
        onDown(event) {
            this.userScrolled = true;
            if (event.pointerType !== 'mouse') return;
            this.dragging = true;
            this.moved = false;
            this.startX = event.clientX;
            this.startScroll = this.$refs.scroller.scrollLeft;
        },

        onMove(event) {
            if (!this.dragging) return;
            const dx = event.clientX - this.startX;
            if (Math.abs(dx) > 5) this.moved = true;
            this.$refs.scroller.scrollLeft = this.startScroll - dx;
        },

        onUp() {
            this.dragging = false;
        },

        // Tras arrastrar, soltar sobre un paso no debe navegar a él.
        onLinkClick(event) {
            if (this.moved) {
                event.preventDefault();
                this.moved = false;
            }
        },

        // Desvanece sólo el borde del lado donde queda contenido. Con máscara y no con
        // un degradado de color, para no depender del fondo de la página.
        get maskStyle() {
            const left = this.canLeft ? 'transparent 0, #000 40px' : '#000 0';
            const right = this.canRight ? '#000 calc(100% - 40px), transparent 100%' : '#000 100%';
            const gradient = `linear-gradient(to right, ${left}, ${right})`;
            return `-webkit-mask-image: ${gradient}; mask-image: ${gradient};`;
        },
    }"
    @pointermove.window="onMove($event)"
    @pointerup.window="onUp()"
>
    {{-- Flechas: sólo escritorio y sólo cuando hay pasos ocultos de ese lado. --}}
    <button type="button" x-cloak x-show="canLeft" x-transition.opacity @click="scrollByPage(-1)"
        aria-label="Ver pasos anteriores"
        class="hidden md:flex absolute left-0 top-[18px] -translate-y-1/2 z-20 w-8 h-8 items-center justify-center rounded-full bg-white border border-[#E5E5E5] shadow-sm text-[#29241C] hover:bg-[#F5F5F5] cursor-pointer">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
    </button>
    <button type="button" x-cloak x-show="canRight" x-transition.opacity @click="scrollByPage(1)"
        aria-label="Ver más pasos"
        class="hidden md:flex absolute right-0 top-[18px] -translate-y-1/2 z-20 w-8 h-8 items-center justify-center rounded-full bg-white border border-[#E5E5E5] shadow-sm text-[#29241C] hover:bg-[#F5F5F5] cursor-pointer">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
    </button>

    {{-- Scroller: sin barra visible, con snap suave y arrastre. --}}
    <div
        x-ref="scroller"
        @scroll.passive="update()"
        @wheel.passive="userScrolled = true"
        @pointerdown="onDown($event)"
        :style="maskStyle"
        :class="dragging ? 'cursor-grabbing' : 'md:cursor-grab'"
        class="relative overflow-x-auto overscroll-x-contain snap-x snap-proximity select-none pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
    >
        {{-- Los pasos arrancan alineados al inicio. --}}
        <ol class="flex w-max">
            @foreach ($steps as $key => $stepData)
                @php
                    $isCurrent = $key === $currentStepKey;
                    $isCompleted = (bool) ($stepStates[$key]['completed'] ?? false);
                    $statusLabel = $isCurrent ? 'paso actual' : ($isCompleted ? 'completado' : 'pendiente');
                @endphp

                {{-- Ancho fijo: garantiza que el conector (w-full) mida exactamente la distancia
                     entre centros, y que los títulos largos pasen a dos renglones. --}}
                <li class="relative shrink-0 w-[5.5rem] md:w-[7.5rem] snap-center"
                    @if ($isCurrent) aria-current="step" @endif>

                    {{-- Conector: línea corta entre este círculo y el siguiente, con aire a
                         ambos lados. Anclada al centro del círculo (top = mitad de 36px), así
                         queda alineada aunque el título ocupe uno o dos renglones.
                         Se pinta oscura sólo si los DOS pasos que une están completos. --}}
                    @unless ($loop->last)
                        @php
                            $nextCompleted = (bool) ($stepStates[$stepKeys[$loop->index + 1]]['completed'] ?? false);
                        @endphp
                        <span aria-hidden="true" @class([
                            'absolute top-[17.5px] h-px left-[calc(50%+30px)] w-[calc(100%-60px)]',
                            'bg-[#29241C]' => $isCompleted && $nextCompleted,
                            'bg-[#E0E0E0]' => !($isCompleted && $nextCompleted),
                        ])></span>
                    @endunless

                    <a href="{{ route('events.wizard.edit', ['event' => $event->slug, 'step' => $key]) }}"
                        draggable="false"
                        @click="onLinkClick($event)"
                        class="relative z-10 flex flex-col items-center gap-2 px-1 text-center group focus:outline-none">

                        @if ($isCurrent)
                            {{-- ACTIVO: borde oscuro; muestra check si ya está completo --}}
                            <span class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full border-[1.5px] border-[#29241C] bg-white text-[#29241C] font-semibold group-focus-visible:ring-2 group-focus-visible:ring-[#29241C]/30">
                                @if ($isCompleted)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                @else
                                    {{ $stepData['step'] }}
                                @endif
                            </span>
                        @elseif ($isCompleted)
                            {{-- COMPLETADO: círculo oscuro con check --}}
                            <span class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full border-[1.5px] border-[#29241C] bg-[#29241C] text-white group-hover:opacity-90 group-focus-visible:ring-2 group-focus-visible:ring-[#29241C]/30">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                        @else
                            {{-- PENDIENTE: borde fino gris --}}
                            <span class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full border border-[#D4D4D4] bg-white text-[#999999] group-hover:border-[#A3A3A3] group-focus-visible:ring-2 group-focus-visible:ring-[#29241C]/20">
                                {{ $stepData['step'] }}
                            </span>
                        @endif

                        <span @class([
                            'text-size-small-heading font-inter',
                            'text-[#29241C]' => $isCurrent || $isCompleted,
                            'text-[#999999]' => !$isCurrent && !$isCompleted,
                        ])>
                            {{ $stepData['title'] }}
                        </span>
                        <span class="sr-only">({{ $statusLabel }})</span>
                    </a>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Pista en móvil mientras queden pasos a la derecha. --}}
    <p x-cloak x-show="canRight" x-transition.opacity class="md:hidden mt-2 text-[11px] text-[#999999] text-right">
        Desliza para ver más pasos →
    </p>
</nav>
