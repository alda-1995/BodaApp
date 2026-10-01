<x-layouts.guest-layout>
    <div class="min-h-screen pt-32 pb-20 md:py-16 bg-[#FAFAFA] md:flex md:items-center">
        <div class="container">
            <div class="rounded-xl bg-white p-6 md:p-8 lg:p-10 flex flex-col max-w-xl mx-auto">
                <p class="font-inter text-size-small-heading text-[#8C8C8C] mb-4">Paso 2 de 3 · Resumen → Pago →
                    Confirmación</p>
                <h2 class="font-inter text-size-subtitle mb-4">Método de pago</h2>
                <form action="{{ route('checkout.process-identity') }}" novalidate method="POST">
                    @csrf
                    <input type="hidden" name="template_id" value="{{ $template->id }}">
                    <x-controls.inputs.input placeholder="tu@correo.com" label="Correo Electrónico" name="email" value="{{ old('email') }}" required autofocus />
                    
                    <div class="mt-4">
                        <x-controls.button type="submit" class="w-full">Pagar Stripe ${{ number_format($template->price, 2) }} MXN</x-controls.button>
                        <x-controls.button variant="cancel" class="mt-6 md:mt-8" href="{{ route('checkout.checkout-preview', $template->slug) }}">Regresar al resumen</x-controls.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.guest-layout>