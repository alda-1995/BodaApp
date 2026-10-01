<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-lg">
                <div class="mb-6">
                    <h2 class="text-size-title text-black mb-2">Añadir invitado</h2>
                    <p class="text-parrafo text-[#737373]">
                        Registra a tu invitado y cuántos acompañantes puede llevar.
                    </p>
                </div>

                @unless ($event)
                    <p class="mb-6 rounded-md bg-[#FEF9C3] px-4 py-3 text-parrafo text-[#854D0E]">
                        Aún no tienes un evento: el invitado se guardará en tu lista, pero su enlace de
                        invitación se generará cuando tu evento exista.
                    </p>
                @endunless

                <form method="POST" action="{{ route('organizer.guests.store') }}" novalidate>
                    @csrf

                    @include('organizer.guests.partials.form')

                    <div class="mt-8 flex gap-4">
                        <x-controls.button type="submit">Guardar invitado</x-controls.button>
                        <x-controls.button href="{{ route('organizer.guests.index') }}" variant="cancel">
                            Cancelar
                        </x-controls.button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>
