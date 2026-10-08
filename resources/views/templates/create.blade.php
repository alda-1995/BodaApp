<x-layouts.super-layout>
    <section class="pt-32 pb-20 md:py-16">
        <div class="container">
            <div class="max-w-lg">
                <h2 class="text-size-subtitle font-inter text-black mb-6">Nueva Plantilla</h2>

                <form method="POST" action="{{ route('templates.store') }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <x-controls.inputs.input label="Nombre de la Plantilla" type="text" name="name"
                        placeholder="Ej: Boda Emerald Esmeralda" value="{{ old('name') }}" required autofocus />
                    <x-controls.inputs.input label="Precio ($MXN)" type="number" name="price" placeholder="0.00"
                        step="0.01" min="0" value="{{ old('price') }}" required />
                    <x-controls.inputs.input label="Días de vigencia después de la boda" type="number"
                        name="duration_days" min="1" max="365"
                        placeholder="{{ config('events.default_duration_days') }}"
                        value="{{ old('duration_days') }}" />
                    <p class="-mt-2 mb-4 font-inter text-size-small-heading text-[#8C8C8C]">
                        Días que la invitación sigue activa después de la fecha del evento.
                        Si lo dejas vacío se usan {{ config('events.default_duration_days') }}.
                    </p>
                    {{-- Con qué se presenta antes de comprarla: en la landing y en el resumen.
                         El mismo control que el wizard, para que subir una imagen se haga igual
                         en todo el panel. --}}
                    <x-controls.inputs.image-upload label="Imagen de presentación" name="preview_image" />
                    <p class="-mt-3 mb-4 font-inter text-size-small-heading text-[#8C8C8C]">
                        Se ve en la landing y en el resumen de compra.
                    </p>

                    <x-controls.inputs.textarea label="Descripción" name="description" rows="3"
                        placeholder="Qué trae esta plantilla y para qué boda es" />
                    <p class="-mt-2 mb-4 font-inter text-size-small-heading text-[#8C8C8C]">
                        El texto que acompaña a la plantilla antes de comprarla. Hasta 500 caracteres.
                    </p>

                    <x-controls.selects.select label="Ruta de platilla - código" name="view_path"
                        :options="$availableViews" :selected="old('view_path')"
                        placeholder="-- Selecciona el archivo/vista de destino --" />
                    <x-controls.check-label name="active" label="Template activo (visible en la landing)" :initial-state="true" />

                    <div class="mt-8 flex gap-4">
                        <x-controls.button type="submit">Guardar Plantilla</x-controls.button>
                        <x-controls.button href="{{ route('templates.index') }}" variant="cancel">
                            Cancelar
                        </x-controls.button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-layouts.super-layout>