<footer class="bg-violet py-10">
    <div class="container">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 items-start">
            <div class="flex flex-col items-center md:items-start md:min-h-[300px] md:justify-between">
                <div class="w-[5.625rem] h-[5.625rem] bg-primary100 rounded-full"></div>
                <div class="hidden md:block">
                    <x-reusable.btn-scroll />
                </div>
            </div>

            <!-- Enlaces -->
            <div class="text-center md:text-left">
                <div class="relative md:max-w-sm md:mx-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 md:mb-6 md:gap-x-8">
                        <h4 class="text-size-small-heading font-secondary text-gray mb-1 md:mb-0">Información</h4>
                        <ul class="space-y-1 mb-8 parrafo font-secondary">
                            <li><a href="#">Ubicación</a></li>
                            <li><a href="#">Itinerario</a></li>
                            <li><a href="#">Mesa de regalos</a></li>
                            <li><a href="#">Galería</a></li>
                            <li><a href="#">FAQS</a></li>
                        </ul>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 md:gap-x-8">
                        <h4 class="text-size-small-heading font-secondary text-gray mb-1 md:mb-0">Contacto</h4>
                        <ul class="space-y-1 parrafo font-secondary">
                            <li>321 989-11-91</li>
                            <li><a href="#">Whatsapp</a></li>
                        </ul>
                    </div>
                    <hr class="hidden md:block absolute top-0 left-1/2 -translate-x-1/2 border border-black/10 h-[140%]">
                </div>
            </div>

            <!-- Botón -->
            <div class="flex justify-center md:justify-end">
                <x-controls.button variant="main">Confirmar asistencia</x-controls.button>
            </div>
            <div class="flex justify-center md:hidden">
                <x-reusable.btn-scroll />
            </div>
        </div>
    </div>
</footer>