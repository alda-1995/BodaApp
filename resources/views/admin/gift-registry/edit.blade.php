<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <form action="{{ route('gift-registry.update', 'gift_registry') }}" novalidate method="POST"
                class="max-w-5xl">
                @csrf
                @method('PUT')

                {{-- Datos requeridos por el DTO / Model --}}
                <input type="hidden" name="key" value="registries">
                <input type="hidden" name="parent" value="gift_registry">
                <input type="hidden" name="title" value="Opciones de regalo">
                <input type="hidden" name="order" value="1">
                <input type="hidden" name="is_global" value="1">
                <input type="hidden" name="type" value="repeater">
                <input type="hidden" name="is_active" value="1">

                <!-- Encabezado -->
                <div class="flex flex-col mb-4">
                    <h2 class="text-size-subtitle text-black mb-2">Tipos de mesa de regalo</h2>
                    <p class="text-parrafo text-[#737373]">Define los tipos de mesa disponibles para todos los usuarios
                        y qué campos pide cada uno. Estos tipos alimentan el selector "Tipo de mesa" del paso 4 del
                        wizard.</p>
                </div>

                <!-- Componente Alpine para estructurar el $schema -->
                <div x-data="giftRegistryTypesManager({{ json_encode($section->schema ?? []) }})" class="space-y-6">

                    {{-- Payload codificado enviado al Backend --}}
                    <input type="hidden" name="schema" :value="schemaJson">

                    <template x-for="(type, typeIndex) in types" :key="typeIndex">
                        <div class="bg-white border border-[#EBEBEB] rounded-lg p-4 md:p-6 space-y-4">

                            <!-- Encabezado del tipo -->
                            <div class="flex items-center justify-between gap-4">
                                <div class="w-full max-w-md">
                                    <x-controls.inputs.input label="Nombre del tipo de mesa" type="text"
                                        placeholder="Ej. Cuenta bancaria" x-model="type.label"
                                        @input="if(!type.is_persisted) type.key = slugify(type.label)" required
                                        autofocus />
                                </div>
                                <x-controls.button type="button" @click="removeType(typeIndex)"
                                    variant="remove-text">Eliminar tipo</x-controls.button>
                            </div>

                            <p class="text-[#808080] text-size-small-heading">Campos que se piden al usuario</p>

                            <!-- Campos dinámicos del tipo -->
                            <div class="space-y-4">
                                <template x-for="(field, fieldIndex) in type.fields" :key="fieldIndex">
                                    {{-- Elemento contenedor único por iteración --}}
                                    <div class="space-y-2">
                                        <!-- Grid principal de inputs -->
                                        <div class="bg-[#FAFAFA] p-4 rounded-md">
                                            <div
                                                class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                                                <!-- Nombre del campo (3 cols) -->
                                                <div class="md:col-span-3">
                                                    <x-controls.inputs.input label="Nombre del campo" type="text"
                                                        placeholder="Ej. Banco" x-model="field.label"
                                                        @input="if(!field.is_persisted) field.key = slugify(field.label)"
                                                        required autofocus />
                                                </div>
                                                <!-- Placeholder del campo (3 cols) -->
                                                <div class="md:col-span-3">
                                                    <x-controls.inputs.input label="Texto de ejemplo (Placeholder)" type="text"
                                                        placeholder="Ej. BBVA o https://..." x-model="field.placeholder"
                                                        required autofocus />
                                                </div>
                                                <!-- Tipo de campo (3 cols) -->
                                                <div class="md:col-span-3">
                                                    <x-controls.selects.select label="Tipo de campo"
                                                        x-model="field.type" :options="[
                                                        'text' => 'Texto corto',
                                                        'url' => 'Liga',
                                                        'textarea' => 'Texto largo',
                                                        'number' => 'Número',
                                                    ]" />
                                                </div>
                                                <!-- Botón Eliminar (3 cols) -->
                                                <div class="md:col-span-3 flex justify-end">
                                                    <x-controls.button type="button" variant="remove-text" @click="removeField(typeIndex, fieldIndex)">Eliminar</x-controls.button>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-2 max-w-sm">
                                                <x-controls.check-label label="Obligatorio" x-model="field.is_required" />
                                                {{--
                                                    Ya no se elige aquí si un dato se copia: lo decide cada plantilla,
                                                    según su diseño. Una liga se enlaza y una clave se copia, y eso
                                                    cambia de una plantilla a otra.
                                                --}}
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Botón Agregar Campo -->
                            <div class="mt-2">
                                <x-controls.button type="button" @click="addField(typeIndex)" variant="secondary">+ Agregar campo</x-controls.button>
                            </div>
                        </div>
                    </template>

                    <!-- Acciones Inferiores -->
                    <div class="flex justify-between items-center pt-4">
                        <x-controls.button type="button" @click="addType()" variant="secondary">+ Agregar tipo de mesa</x-controls.button>
                        <x-controls.button type="submit" variant="main">Guardar cambios</x-controls.button>
                    </div>

                </div>
            </form>
        </div>
    </section>

    <script>
        function giftRegistryTypesManager(initialSchema = []) {
            let parsedTypes = [];

            if (typeof initialSchema === 'string') {
                try { initialSchema = JSON.parse(initialSchema); } catch (e) { initialSchema = []; }
            }

            if (Array.isArray(initialSchema) && initialSchema.length) {
                const selectObj = initialSchema.find(item => item.key === 'type');
                const fieldItems = initialSchema.filter(item => item.key !== 'type');

                if (selectObj && selectObj.options) {
                    const optionEntries = Array.isArray(selectObj.options)
                        ? selectObj.options.map(option => [option.value, option.label])
                        : Object.entries(selectObj.options);

                    optionEntries.forEach(([typeKey, typeLabel]) => {
                        const typeFields = fieldItems.filter(f => f.depends_on && f.depends_on.values.includes(typeKey));

                        parsedTypes.push({
                            key: typeKey,
                            label: typeLabel,
                            is_persisted: true,
                            fields: typeFields.map(f => ({
                                key: f.key,
                                label: f.label,
                                type: f.type || 'text',
                                placeholder: f.placeholder || '',
                                is_required: f.is_required === "1" || f.is_required === true,
                                is_persisted: true
                            }))
                        });
                    });
                }
            }

            return {
                types: parsedTypes,

                get schemaJson() {
                    const options = [];
                    this.types.forEach(type => {
                        const key = type.key || this.slugify(type.label);
                        if (key) {
                            options.push({ value: key, label: type.label });
                        }
                    });

                    const firstKey = options.length ? options[0].value : '';

                    // 1. Selector principal
                    const selectElement = {
                        key: "type",
                        type: "select",
                        label: "Tipo de mesa",
                        default: firstKey,
                        options: options,
                        is_required: "1",
                        placeholder: "Elige una opción"
                    };

                    // 2. Definición plana de campos dinámicos
                    const flatFields = [];

                    this.types.forEach(type => {
                        const typeKey = type.key || this.slugify(type.label);

                        (type.fields || []).forEach(field => {
                            const fieldKey = field.key || this.slugify(field.label);
                            if (!fieldKey) return;

                            const rules = [];

                            if (field.type === 'number') {
                                // Con 'numeric', max:255 limitaría el VALOR a 255 y una
                                // CLABE no cabría: se limita el número de dígitos.
                                rules.push('numeric', 'max_digits:20');
                            } else if (field.type === 'url') {
                                // Igual que UrlField: sólo ligas http/https.
                                rules.push('url:http,https', 'max:255');
                            } else {
                                rules.push('string', 'max:255');
                            }

                            flatFields.push({
                                key: fieldKey,
                                type: field.type || "text",
                                label: field.label,
                                is_required: field.is_required ? "1" : "0",
                                // Sólo lo lee la plantilla al pintar la invitación.
                                placeholder: field.placeholder || "",
                                depends_on: {
                                    field: "type",
                                    values: [typeKey]
                                },
                                // El mensaje pertenece al campo que se vuelve obligatorio,
                                // no al selector, y nombra su propio tipo de mesa.
                                messages: {
                                    required_if: `El campo :attribute es obligatorio cuando el tipo de mesa es ${type.label}`
                                },
                                rules: rules
                            });
                        });
                    });

                    return JSON.stringify([selectElement, ...flatFields]);
                },

                slugify(text) {
                    if (!text) return '';
                    return text
                        .toString()
                        .toLowerCase()
                        .trim()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .replace(/\s+/g, '_')
                        .replace(/[^\w\-]+/g, '')
                        .replace(/\-\-+/g, '_');
                },

                addType() {
                    this.types.push({
                        key: '',
                        label: '',
                        is_persisted: false,
                        fields: [
                            { key: '', label: '', placeholder: '', type: 'text', is_required: true, is_persisted: false }
                        ]
                    });
                },

                removeType(index) {
                    this.types.splice(index, 1);
                },

                addField(typeIndex) {
                    this.types[typeIndex].fields.push({
                        key: '',
                        label: '',
                        placeholder: '',
                        type: 'text',
                        is_required: true,
                        is_persisted: false
                    });
                },

                removeField(typeIndex, fieldIndex) {
                    this.types[typeIndex].fields.splice(fieldIndex, 1);
                }
            }
        }
    </script>
</x-layouts.dashboard-layout>