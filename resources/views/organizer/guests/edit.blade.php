<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-lg">
                <nav class="flex flex-wrap gap-2 mb-4 text-parrafo text-[#737373]" aria-label="Ruta">
                    <a href="{{ route('organizer.guests.index') }}" class="hover:text-black">Invitados</a>
                    <span>/</span>
                    <a href="{{ route('organizer.guests.show', $guest->id) }}" class="hover:text-black">{{ $guest->name }}</a>
                    <span>/</span>
                    <span class="text-black">Editar</span>
                </nav>

                <h2 class="text-size-title text-black mb-6">Editar invitado</h2>

                <form method="POST" action="{{ route('organizer.guests.update', $guest->id) }}" novalidate>
                    @csrf
                    @method('PUT')

                    @include('organizer.guests.partials.form', ['guest' => $guest, 'invitation' => $invitation])

                    <div class="mt-8 flex gap-4">
                        <x-controls.button type="submit">Guardar cambios</x-controls.button>
                        <x-controls.button href="{{ route('organizer.guests.show', $guest->id) }}" variant="cancel">
                            Cancelar
                        </x-controls.button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>
