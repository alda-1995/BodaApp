@props([
    'field' => null,
    'name' => '',
    'label' => '',
    'value' => null,
])

@php
    $isObject = is_object($field);
    $schema = $isObject ? $field->getSchema() : ($field['schema'] ?? []);

    $sortable = $isObject && method_exists($field, 'isSortable') && $field->isSortable();

    // Cuando cada fila la dibuja su propio control (lápiz + eliminar), el repeater
    // omite su cabecera "#N / Eliminar" para no duplicar el botón de borrado.
    $bareRows = $isObject && method_exists($field, 'rowsRenderThemselves') && $field->rowsRenderThemselves();

    // Mínimo de filas al eliminar: un repeater obligatorio no deja borrar la última;
    // uno opcional puede quedar vacío. No crea filas por su cuenta (ver $items).
    $minItems = $isObject && $field->isRequired() ? 1 : 0;

    // Claves reales de los subcampos: el schema llega como lista numérica, así que
    // la clave es el name del propio campo ('question', 'answer'), no el índice.
    $subKeys = [];
    foreach ($schema as $subIndex => $subField) {
        $subKeys[] = is_object($subField) ? $subField->getName() : ($subField['name'] ?? $subIndex);
    }

    // Sembrar el item con todas las claves evita que x-model arranque en undefined.
    $rowDefaults = array_fill_keys($subKeys, '');

    // Base en notación de puntos para consultar errores de una fila ('faqs.0.*').
    $dotBase = trim(str_replace(['[', ']'], ['.', ''], $name), '.');

    $items = old($name, $value);

    if (!is_array($items) || empty($items)) {
        // Nunca se crea una fila por su cuenta: aparece al pulsar "+ Agregar". Si el
        // repeater es obligatorio, el servidor rechaza el paso sin filas.
        $items = [];
    } else {
        $items = array_values($items);
    }
@endphp

<div x-data="dynamicGroup({ min: {{ $minItems }}, max: 10, sortable: {{ $sortable ? 'true' : 'false' }} })"
    class="space-y-4 border border-gray-200 rounded-xl p-5 bg-gray-50/50 mb-4">
    @error($name)
        <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
    @enderror

    {{-- 1. Filas cargadas en Servidor (old() o Base de Datos) --}}
    <div x-ref="container" class="space-y-4">
        @foreach ($items as $index => $item)
            @php
                $itemData = is_array($item) ? $item : [];
            @endphp
            @if($bareRows)
                {{-- Una fila con errores de validación vuelve abierta, para que se vean. --}}
                <x-forms.inline-row :schema="$schema" :sub-keys="$subKeys" :name="$name" :index="$index"
                    :item-data="$itemData" :defaults="$rowDefaults" :sortable="$sortable" :label="$label"
                    :open="$errors->has($dotBase . '.' . $index . '.*')" />
            @else
                <div x-data="repeaterRow({{ json_encode(array_merge($rowDefaults, $itemData)) }})"
                    class="group-item relative p-4 rounded-lg border border-[#EBEBEB]">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                        <span class="flex items-center gap-2">
                            <x-forms.row-handle :sortable="$sortable" />
                            <span class="item-number text-xs font-bold text-gray-400 uppercase tracking-wider">
                                #{{ $loop->iteration }}
                            </span>
                        </span>
                        <button type="button" @click="removeItem($event)"
                            class="item-remove-btn text-red-500 cursor-pointer text-size-small-heading font-inter transition-colors">
                            Eliminar
                        </button>
                    </div>

                    <div class="grid grid-cols-1">
                        @foreach ($schema as $subIndex => $subField)
                            @php
                                $subKey = $subKeys[$subIndex] ?? $subIndex;
                                $subPrefix = "{$name}[{$index}][{$subKey}]";
                            @endphp
                            <div>
                                <x-forms.render-field :field="$subField" :name="$subPrefix" :value="$itemData[$subKey] ?? null"
                                    :is-repeater="true" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- 2. Plantilla Blueprint para Clonar desde JS --}}
    <template x-ref="template">
        @if($bareRows)
            {{-- Una fila recién agregada nace abierta y con foco: vacía no tendría nada que mostrar. --}}
            <x-forms.inline-row :schema="$schema" :sub-keys="$subKeys" :name="$name" index="__INDEX__"
                :defaults="$rowDefaults" :sortable="$sortable" :label="$label" :open="true" :focus="true" />
        @else
            <div x-data="repeaterRow({{ json_encode($rowDefaults) }})"
                class="group-item relative p-4 rounded-lg border border-[#EBEBEB]">
                <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <x-forms.row-handle :sortable="$sortable" />
                        <span class="item-number text-xs font-bold text-gray-400 uppercase tracking-wider">
                            #__INDEX_NUMBER__
                        </span>
                    </span>
                    <button type="button" @click="removeItem($event)"
                        class="item-remove-btn text-red-500 cursor-pointer text-size-small-heading font-inter transition-colors">
                        Eliminar
                    </button>
                </div>

                <div class="grid grid-cols-1">
                    @foreach ($schema as $subIndex => $subField)
                        @php
                            $subKey = $subKeys[$subIndex] ?? $subIndex;
                            $subPrefix = "{$name}[__INDEX__][{$subKey}]";
                        @endphp
                        <div>
                            <x-forms.render-field :field="$subField" :name="$subPrefix" :value="null"
                                :is-repeater="true" />
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </template>

    <div class="flex items-center justify-between pb-3 border-b border-gray-200">
        <x-controls.button type="button" @click="addItem" variant="secondary">+ Agregar {{ strtolower($label) ?: 'elemento' }}</x-controls.button>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('repeaterRow', (initialData = {}, options = {}) => ({
            item: initialData,
            editing: options.open === true,
            // Lo que muestra la vista previa mientras se edita: el texto de arriba
            // sólo cambia al cerrar el editor.
            snapshot: { ...initialData },

            // La fila entra y sale de edición como una unidad, así una fila de varios
            // campos (pregunta + descripción) se edita de una sola vez.
            startEdit() {
                this.snapshot = { ...this.item };
                this.editing = true;
                this.focusFirstField();
            },

            focusFirstField() {
                this.$nextTick(() => {
                    const first = this.$el.querySelector('textarea, input[type="text"]');
                    if (first) {
                        first.focus();
                        first.select();
                    }
                });
            },

            stopEdit() {
                this.editing = false;
            },

            cancelEdit() {
                Object.assign(this.item, this.snapshot);
                this.editing = false;
            },

            init() {
                if (options.focus === true) {
                    this.focusFirstField();
                }

                this.$nextTick(() => {
                    this.$el.querySelectorAll('[name]').forEach(input => {
                        if (input.type === 'file') return;

                        const match = input.name.match(/\[([^\]]+)\]$/);
                        if (match && match[1]) {
                            const key = match[1];
                            if (input.type === 'checkbox' || input.type === 'radio') {
                                if (input.checked) this.item[key] = input.value;
                            } else {
                                this.item[key] = input.value;
                            }
                        }
                    });
                });
            },

            updateField(key, value) {
                this.item[key] = value;
            }
        }));

        Alpine.data('dynamicGroup', (config = {}) => ({
            min: config.min ?? 1,
            max: config.max || 100,
            sortable: config.sortable === true,
            dragging: null,

            init() {
                this.updateIndexes();

                if (this.sortable) {
                    this.enableSorting();
                }
            },

            /**
             * Arrastre nativo HTML5, delegado en el contenedor para que las filas
             * añadidas después queden cubiertas sin volver a enganchar listeners.
             *
             * Las filas sólo son draggable mientras se sujeta el handle: así el
             * texto de los inputs se sigue pudiendo seleccionar con el ratón.
             */
            enableSorting() {
                const container = this.$refs.container;

                container.addEventListener('pointerdown', event => {
                    const handle = event.target.closest('[data-drag-handle]');
                    const item = event.target.closest('.group-item');
                    if (handle && item) item.setAttribute('draggable', 'true');
                });

                container.addEventListener('pointerup', () => this.clearDraggable());

                container.addEventListener('dragstart', event => {
                    const item = event.target.closest('.group-item');
                    if (!item) return;

                    this.dragging = item;
                    item.classList.add('opacity-50');
                    event.dataTransfer.effectAllowed = 'move';
                    // Firefox exige datos en el dataTransfer para iniciar el arrastre.
                    event.dataTransfer.setData('text/plain', '');
                });

                container.addEventListener('dragover', event => {
                    if (!this.dragging) return;
                    event.preventDefault();

                    const target = event.target.closest('.group-item');
                    if (!target || target === this.dragging) return;

                    const box = target.getBoundingClientRect();
                    const insertAfter = event.clientY > box.top + box.height / 2;

                    container.insertBefore(
                        this.dragging,
                        insertAfter ? target.nextSibling : target
                    );
                });

                container.addEventListener('drop', event => event.preventDefault());

                container.addEventListener('dragend', () => {
                    if (this.dragging) this.dragging.classList.remove('opacity-50');
                    this.dragging = null;
                    this.clearDraggable();
                    // El orden del DOM es el orden del array enviado: al reindexar
                    // los name, la nueva posición queda persistida sin campo extra.
                    this.updateIndexes();
                });
            },

            clearDraggable() {
                this.$refs.container
                    .querySelectorAll('.group-item[draggable]')
                    .forEach(item => item.removeAttribute('draggable'));
            },

            addItem() {
                const container = this.$refs.container;
                const template = this.$refs.template;
                const currentItems = container.querySelectorAll('.group-item').length;

                if (currentItems >= this.max) return;

                let content = template.innerHTML;
                const nextIndex = currentItems;
                const nextIndexNumber = currentItems + 1;

                content = content
                    .replaceAll('__INDEX__', nextIndex)
                    .replaceAll('__INDEX_NUMBER__', nextIndexNumber);

                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = content.trim();
                const newItem = tempDiv.firstElementChild;

                container.appendChild(newItem);

                if (window.Alpine) {
                    Alpine.initTree(newItem);
                }

                this.initChildPlugins(newItem);
                this.updateIndexes();
            },

            removeItem(event) {
                const container = this.$refs.container;
                const items = container.querySelectorAll('.group-item');

                if (items.length <= this.min) return;

                const targetItem = event.target.closest('.group-item');
                if (targetItem) {
                    targetItem.remove();
                    this.updateIndexes();
                }
            },

            updateIndexes() {
                const container = this.$refs.container;
                const items = container.querySelectorAll('.group-item');

                items.forEach((item, index) => {
                    const numberSpan = item.querySelector('.item-number');
                    if (numberSpan) {
                        numberSpan.textContent = `#${index + 1}`;
                    }

                    const deleteBtn = item.querySelector('.item-remove-btn');
                    if (deleteBtn) {
                        deleteBtn.style.display = items.length <= this.min ? 'none' : 'block';
                    }

                    // Reindexar atributos name, id y asociar labels considerando profundidad
                    item.querySelectorAll('[name]').forEach(input => {
                        const name = input.getAttribute('name');
                        if (name) {
                            // Reemplaza el primer índice del repeater conservando sub-claves como [uuid] o [image]
                            const updatedName = name.replace(/^([^\[]+)\[(\d+|__INDEX__)\]/, `$1[${index}]`);
                            input.setAttribute('name', updatedName);

                            if (input.id) {
                                const updatedId = input.id.replace(/^([^\[]+)\[(\d+|__INDEX__)\]/, `$1[${index}]`);
                                input.setAttribute('id', updatedId);

                                const label = item.querySelector(`label[for="${input.id}"], label[for="${updatedId}"]`);
                                if (label) {
                                    label.setAttribute('for', updatedId);
                                }
                            }
                        }
                    });
                });
            },

            initChildPlugins(element) {
                if (window.flatpickr) {
                    element.querySelectorAll('[data-flatpickr]').forEach(input => {
                        window.flatpickr(input, {});
                    });
                }
            }
        }));
    });
</script>