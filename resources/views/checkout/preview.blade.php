<x-layouts.guest-layout>
    <div class="min-h-screen pt-32 pb-20 md:py-16 bg-[#FAFAFA] md:flex md:items-center">
        <div class="container">
            <div class="rounded-xl bg-white p-6 md:p-8 lg:p-10 flex flex-col max-w-2xl mx-auto">
                <p class="font-inter text-size-small-heading text-[#8C8C8C] mb-4">Paso 1 de 3 · Resumen → Pago → Confirmación</p>
                <h2 class="font-inter text-size-subtitle mb-4">Resumen de tu compra</h2>

                {{--
                    Lo que se lleva, con los datos de SU plantilla: el nombre y
                    el precio salen de la base, no escritos aquí. Antes esta
                    tarjeta decía siempre lo mismo —"Alfonso y Elena", "Categoría:
                    Elegante"— comprara quien comprara lo que comprara.
                --}}
                <div class="bg-[#FAFAFA] rounded-lg overflow-hidden">
                    @if ($template->previewImageUrl())
                        <img src="{{ $template->previewImageUrl() }}" alt="Plantilla {{ $template->name }}"
                            class="aspect-[16/9] w-full object-cover" />
                    @endif

                    <div class="p-4">
                        <h3 class="font-inter text-size-heading">{{ $template->name }}</h3>
                        <p class="font-inter text-size-small-heading text-[#8C8C8C] mt-1">
                            {{ $template->description
                                ?: 'Invitación digital con confirmación de asistencia, lista de invitados,
                                    itinerario, mesa de regalos y galería.' }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-between gap-x-8 md:gap-x-12 pb-4 border-b border-b-[#EBEBEB]">
                    <p class="font-inter text-[#737373] text-parrafo">{{ $template->name }}</p>
                    <p class="font-inter text-parrafo">${{ number_format($template->price, 2) }} MXN</p>
                </div>

                {{-- Un pago único: conviene decirlo antes de pedir la tarjeta. --}}
                <div class="mt-4 flex justify-between gap-x-8 md:gap-x-12">
                    <p class="font-inter text-parrafo">Total</p>
                    <p class="font-inter text-size-heading">${{ number_format($template->price, 2) }} MXN</p>
                </div>
                <p class="font-inter text-size-small-heading text-[#8C8C8C] mt-1">Pago único, sin mensualidades.</p>
                <div class="mt-4">
                    <x-controls.button class="w-full" href="{{ route('checkout.detail-payment', $template->slug) }}">Continuar al pago</x-controls.button>
                    {{-- Demo con datos de ejemplo, antes de decidir la compra. --}}
                    <x-controls.button variant="secondary" class="w-full mt-4"
                        href="{{ route('templates.preview', $template->slug) }}" target="_blank" rel="noopener">
                        Ver demo de la plantilla
                    </x-controls.button>
                    <x-controls.button variant="cancel" class="mt-6 md:mt-8" href="{{ url('/') }}">Elegir otro template</x-controls.button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest-layout>