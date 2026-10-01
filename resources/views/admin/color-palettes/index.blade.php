<x-layouts.dashboard-layout>
    <section class="pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="mb-8 max-w-2xl">
                <h2 class="text-size-title mb-2">Paletas de color</h2>
                <p class="text-[#737373] text-parrafo mb-6">
                    Define las paletas predeterminadas que los usuarios pueden elegir en Configuración, además de
                    personalizar sus propios colores.
                </p>
            </div>

            {{-- 1. Listado para Editar/Eliminar Paletas Existentes --}}
            <div class="space-y-6">
                @foreach ($palettes as $palette)
                    <div x-data="{
                                            primary: '{{ old('primary_color', $palette->primary_color) }}', 
                                            secondary: '{{ old('secondary_color', $palette->secondary_color) }}', 
                                            accent: '{{ old('accent_color', $palette->accent_color) }}' 
                                        }" class="mt-6 bg-white mb-4 rounded-lg p-6 border border-[#EBEBEB]">
                        <form method="POST" action="{{ route('color-palettes.update', $palette->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                <div class="md:col-span-4">
                                    <x-controls.inputs.input label="Nombre de la paleta"
                                        value="{{ old('name', $palette->name) }}" name="name"
                                        placeholder="Ej. Paleta Elegante" required />
                                </div>

                                <div class="md:col-span-2" x-data="{ color: primary }"
                                    x-init="$watch('color', val => primary = val)">
                                    <x-controls.inputs.color-bar label="Color primario" name="primary_color"
                                        ::value="primary" />
                                </div>

                                <div class="md:col-span-2" x-data="{ color: secondary }"
                                    x-init="$watch('color', val => secondary = val)">
                                    <x-controls.inputs.color-bar label="Color secundario" name="secondary_color"
                                        ::value="secondary" />
                                </div>

                                <div class="md:col-span-2" x-data="{ color: accent }"
                                    x-init="$watch('color', val => accent = val)">
                                    <x-controls.inputs.color-bar label="Color de acento" name="accent_color"
                                        ::value="accent" />
                                </div>
                                <div class="md:col-span-2">
                                    <x-controls.button type="button"
                                        onclick="toggleModalDelete('deleteModalTableCrud', '{{ route('color-palettes.destroy', $palette->id) }}')"
                                        variant="remove-text">Eliminar</x-controls.button>
                                </div>
                            </div>

                            {{-- Barra de previsualización en vivo --}}
                            <div class="mt-4 h-10 w-full rounded-lg overflow-hidden border border-gray-100 flex">
                                <div class="w-1/3 h-full transition-all duration-150"
                                    :style="`background-color: ${primary}`"></div>
                                <div class="w-1/3 h-full transition-all duration-150"
                                    :style="`background-color: ${secondary}`"></div>
                                <div class="w-1/3 h-full transition-all duration-150"
                                    :style="`background-color: ${accent}`"></div>
                            </div>
                            <div class="mt-4">
                                <x-controls.button type="submit" variant="secondary">Actualizar</x-controls.button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>

            {{-- 2. Formulario Dinámico para Crear una Nueva Paleta --}}
            <div class="mt-4" x-data="{ open: false, primary: '#1E1E1E', secondary: '#F5E9DC', accent: '#FFFFFF' }">
                <div x-show="open" x-cloak x-collapse style="display: none;"
                    class="mt-6 bg-white mb-4 rounded-lg p-6 border border-[#EBEBEB]">
                    <h3 class="text-black text-size-heading mb-4">Nueva Paleta de Color</h3>

                    <form method="POST" novalidate action="{{ route('color-palettes.store') }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-6">
                                <x-controls.inputs.input label="Nombre de la paleta" value="{{ old('name') }}"
                                    name="name" placeholder="Ej. Paleta Elegante" required />
                            </div>

                            <div class="md:col-span-2" x-data="{ color: primary }"
                                x-init="$watch('color', val => primary = val)">
                                <x-controls.inputs.color-bar label="Color primario" name="primary_color" required />
                            </div>

                            <div class="md:col-span-2" x-data="{ color: secondary }"
                                x-init="$watch('color', val => secondary = val)">
                                <x-controls.inputs.color-bar label="Color secundario" name="secondary_color" required />
                            </div>

                            <div class="md:col-span-2" x-data="{ color: accent }"
                                x-init="$watch('color', val => accent = val)">
                                <x-controls.inputs.color-bar label="Color de acento" name="accent_color" required />
                            </div>
                        </div>

                        {{-- Vista previa de la nueva paleta --}}
                        <div class="mt-4 h-10 w-full rounded-lg overflow-hidden border border-gray-100 flex">
                            <div class="w-1/3 h-full transition-all duration-150"
                                :style="`background-color: ${primary}`"></div>
                            <div class="w-1/3 h-full transition-all duration-150"
                                :style="`background-color: ${secondary}`"></div>
                            <div class="w-1/3 h-full transition-all duration-150"
                                :style="`background-color: ${accent}`"></div>
                        </div>
                        <div class="mt-4">
                            <x-controls.button type="submit" variant="main">Crear</x-controls.button>
                            <x-controls.button type="button" @click="open = false"
                                variant="cancel">Cancelar</x-controls.button>
                        </div>
                    </form>
                </div>
                <x-controls.button x-show="!open" type="button" @click="open = !open">
                    + Agregar paleta
                </x-controls.button>
            </div>
        </div>
    </section>

    <x-modals.modal-confirm-delete id="deleteModalTableCrud" cancelId="btnCloseTableCrud" />
    @push('scripts')
        <script>
            const btnCloseModal = document.getElementById("btnCloseTableCrud");
            if (btnCloseModal) {
                btnCloseModal.addEventListener("click", function (e) {
                    e.preventDefault();
                    const modal = document.getElementById("deleteModalTableCrud");
                    if (modal) modal.classList.add('hidden');
                });
            }
        </script>
    @endpush
</x-layouts.dashboard-layout>