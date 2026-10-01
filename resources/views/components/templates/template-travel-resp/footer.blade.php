<footer class="bg-bgfooter py-14 relative">
    <div class="absolute left-[10%] md:left-[30%] top-0 -translate-y-1/2 w-[180px] md:w-[220px]">
        <img src="{{ asset('images/assets-travel/barco.png') }}" class="boat-animation-move max-w-full" alt="img barco">
    </div>
    <div class="container">
        <div class="flex flex-col md:flex-row md:items-center">
            <div class="mb-6 md:mb-0 md:w-1/2 md:pr-10 lg:pr-[10%]">
                <img src="{{ asset('images/assets-travel/logo-footer.png') }}" class="max-w-full" alt="img logo footer">
            </div>
            <div class="w-1/2">
                <nav class="flex flex-col md:flex-row gap-4">
                    <a href="#historia" data-block-go="#historia" class="link-footer">Historia</a>
                    <a href="#time-canvas-weeding" data-block-go="#time-canvas-weeding" class="link-footer">Itinerario</a>
                    <a href="#confirm" data-block-go="#confirm" class="link-footer">Confirmación asistencia</a>
                    <a href="#galeria" data-block-go="#galeria" class="link-footer">Galería</a>
                </nav>
            </div>
        </div>
        <div class="max-w-lg mt-8 lg:mt-10">
            <h2 class="text-size-title font-main text-white">Cada historia de amor es única, y la nuestra brilla más con
                personas como tú.</h2>
        </div>
        <div class="mt-6 md:mt-8 lg:mt-12 flex justify-end">
            <button id="btn-scroll-top"
                class="cursor-pointer w-20 h-20 bg-white rounded-full flex items-center justify-center text-gray transition-all duration-300">
                <x-icons.arrow-up />
            </button>
        </div>
    </div>
</footer>