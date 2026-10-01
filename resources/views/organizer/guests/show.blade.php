<x-layouts.dashboard-layout>
    <x-modals.modal-confirm-delete
        id="deleteGuestModal"
        cancelId="cancelDeleteGuest"
        title="¿Eliminar invitado?"
        message="Se eliminará a {{ $guest->name }} y su enlace de invitación dejará de funcionar. Esta acción no se puede deshacer."
        :action="route('organizer.guests.destroy', $guest->id)" />

    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-2xl">
                <nav class="flex flex-wrap gap-2 mb-4 text-parrafo text-[#737373]" aria-label="Ruta">
                    <a href="{{ route('organizer.guests.index') }}" class="hover:text-black">Invitados</a>
                    <span>/</span>
                    <span class="text-black">{{ $guest->name }}</span>
                </nav>

                <div class="mb-6">
                    <h2 class="text-size-title text-black mb-2">{{ $guest->name }}</h2>
                    <p class="text-parrafo text-[#737373]">Datos de contacto e invitación.</p>
                </div>

                <dl class="rounded-xl border border-[#F2F2F2] bg-white divide-y divide-[#F2F2F2]">
                    @foreach ([
                        'Nombre' => $guest->name,
                        'Correo' => $guest->email ?: '—',
                        'Teléfono' => $guest->phone ?: '—',
                        'Tope acompañantes' => $invitation?->max_passes ?? '—',
                    ] as $label => $value)
                        <div class="grid grid-cols-1 sm:grid-cols-[200px_1fr] gap-1 px-6 py-4">
                            <dt class="text-size-small-heading text-[#808080]">{{ $label }}</dt>
                            <dd class="text-parrafo text-[#1A1A1A]">{{ $value }}</dd>
                        </div>
                    @endforeach

                    <div class="grid grid-cols-1 sm:grid-cols-[200px_1fr] gap-1 px-6 py-4">
                        <dt class="text-size-small-heading text-[#808080]">Enlace de invitación</dt>
                        <dd class="text-parrafo text-[#1A1A1A] min-w-0">
                            @if ($invitation)
                                <div x-data="{ copied: false }" class="flex flex-wrap items-center gap-3">
                                    <span class="truncate text-[#737373]">{{ $invitation->invitationUrl() }}</span>
                                    <button type="button"
                                        @click="navigator.clipboard.writeText(@js($invitation->invitationUrl())).then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
                                        x-text="copied ? '¡Copiado!' : 'Copiar URL'"
                                        class="text-[#3952F6] cursor-pointer">Copiar URL</button>
                                </div>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>

                <div class="mt-8 flex flex-wrap gap-4">
                    <x-controls.button href="{{ route('organizer.guests.edit', $guest->id) }}">Editar invitado</x-controls.button>
                    <x-controls.button variant="remove" onclick="toggleModalDelete('deleteGuestModal')">
                        Eliminar invitado
                    </x-controls.button>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            document.getElementById('cancelDeleteGuest')?.addEventListener('click', (event) => {
                event.preventDefault();
                document.getElementById('deleteGuestModal')?.classList.add('hidden');
            });
        </script>
    @endpush
</x-layouts.dashboard-layout>
