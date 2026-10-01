<x-layouts.super-layout>
    <section class="pt-32 pb-20 md:py-16">
        <div class="container">
            <div class="max-w-lg">
                <h2 class="text-size-title font-inter text-black mb-6">Editar Plantilla</h2>

                <form method="POST" action="{{ route('templates.update', $template->id) }}" novalidate>
                    @csrf
                    @method('PUT')
                    
                    <x-controls.input label="Nombre de la Plantilla" type="text" name="name" placeholder="Ej: Boda Emerald Esmeralda"
                        value="{{ old('name', $template->name) }}" required autofocus />

                    <x-controls.input label="Precio ($MXN)" type="number" name="price" placeholder="0.00" step="0.01" min="0"
                        value="{{ old('price', $template->price) }}" required />
                    
                    <x-controls.input label="Días de vigencia después de la boda" type="number" name="duration_days"
                        min="1" max="365" placeholder="{{ config('events.default_duration_days') }}"
                        value="{{ old('duration_days', $template->duration_days) }}" />
                    <p class="-mt-2 mb-4 font-inter text-size-small-heading text-[#8C8C8C]">
                        Días que la invitación sigue activa después de la fecha del evento.
                        Si lo dejas vacío se usan {{ config('events.default_duration_days') }}.
                        Sólo aplica a las invitaciones que se compren desde ahora.
                    </p>

                    <!-- Campo View Path deshabilitado para evitar modificaciones accidentales en la edición -->
                    <div class="relative opacity-60">
                        <x-controls.select label="Ruta de la Vista (View Path) [No Modificable]" name="view_path" 
                            :options="$availableViews" 
                            :selected="old('view_path', $template->view_path)"
                            placeholder="-- Selecciona el archivo/vista de destino --"
                            disabled />
                    </div>
                    
                    <x-controls.check-label name="active" label="Template activo (visible en la landing)" 
                        :checked="(bool) old('active', $template->is_active)" />

                    <div class="mt-8 flex flex-wrap gap-4">
                        <x-controls.button type="submit">Actualizar Plantilla</x-controls.button>
                        <x-controls.button href="{{ route('templates.preview', $template->slug) }}" variant="secondary"
                            target="_blank" rel="noopener">
                            Ver vista previa
                        </x-controls.button>
                        <x-controls.button href="{{ route('templates.index') }}" variant="cancel">
                            Cancelar
                        </x-controls.button>
                    </div>

                </form>
            </div>
        </div>
    </section>
</x-layouts.super-layout>