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

    {{--
        Plegar todas de una vez: con varias filas abiertas el paso es larguísimo y
        esto lo deja en una lista que se lee de un vistazo. Sólo aparece cuando hay
        más de una fila; con una sola, su propio botón basta.

        No va en las filas compactas (preguntas frecuentes, preguntas del
        formulario): ésas ya nacen resumidas y se abren una a una para editarlas,
        así que no hay nada que plegar.
    --}}
    @unless($bareRows)
    <div class="flex justify-end" x-show="count > 1" x-cloak>
        <button type="button" @click="alternarTodas()"
            x-text="todasPlegadas ? 'Expandir todas' : 'Colapsar todas'"
            class="text-[#3952F6] font-inter text-parrafo cursor-pointer">Colapsar todas</button>
    </div>
    @endunless

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
                {{-- Abierta: el formulario es lo primero que hay que ver. --}}
                <x-forms.block-row :schema="$schema" :sub-keys="$subKeys" :name="$name" :index="$index"
                    :item-data="$itemData" :defaults="$rowDefaults" :sortable="$sortable" :label="$label"
                    :number="$loop->iteration" :open="true" />
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
            {{-- Una fila recién agregada nace abierta y con foco: vacía no tendría nada que mostrar. --}}
            <x-forms.block-row :schema="$schema" :sub-keys="$subKeys" :name="$name" index="__INDEX__"
                :defaults="$rowDefaults" :sortable="$sortable" :label="$label"
                :open="true" :focus="true" />
        @endif
    </template>

    {{--
        Al llegar al tope el botón se apaga de verdad —antes seguía viéndose igual
        y el clic no hacía nada— y al lado se dice cuántos van, para que el tope no
        aparezca de sorpresa.
    --}}
    <div class="flex items-center gap-4 pb-3 border-b border-gray-200">
        <x-controls.button type="button" variant="secondary"
            x-on:click="addItem()"
            x-bind:disabled="count >= max">+ Agregar {{ strtolower($label) ?: 'elemento' }}</x-controls.button>

        <span class="font-inter text-size-small-heading text-[#8C8C8C]"
            x-text="count >= max ? `Llegaste al máximo de ${max}` : `${count} de ${max}`"></span>
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

            /**
             * Lo que se lee de la fila cuando está cerrada.
             *
             * Se arma con lo que el organizador escribió, no con nombres de campo:
             * para reconocer una fila entre varias sirve su contenido. Se dejan
             * fuera las URL y los identificadores de archivo, que no dicen nada.
             */
            resumen() {
                const valores = Object.values(this.editing ? this.snapshot : this.item)
                    .filter(valor => typeof valor === 'string')
                    .map(valor => valor.trim())
                    .filter(valor => valor !== '' && !/^(https?:\/\/|\/storage\/)/.test(valor))
                    .filter(valor => !/^[0-9a-f]{8}-[0-9a-f]{4}-/i.test(valor));

                return valores.slice(0, 3).join(' · ');
            },

            /**
             * La foto de la fila, para verla cerrada sin tener que abrirla.
             *
             * Se reconoce por la firma del control de imagen: deja un '<campo>_url'
             * junto a su '<campo>_uuid'. Así no se confunde con una liga que el
             * organizador escribió a mano, que es texto y no una foto.
             */
            miniatura() {
                const datos = this.editing ? this.snapshot : this.item;

                for (const [clave, valor] of Object.entries(datos)) {
                    if (!clave.endsWith('_url') || typeof valor !== 'string' || valor.trim() === '') continue;
                    if (!(clave.replace(/_url$/, '_uuid') in datos)) continue;

                    return valor;
                }

                return null;
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

                    /*
                     * El snapshot se arma antes de esta lectura, así que le faltan
                     * las claves que sólo existen en el HTML —la URL y el uuid de
                     * una imagen—. Sin esto, una fila abierta no encontraba su
                     * foto para la miniatura ni su texto para el resumen.
                     */
                    this.snapshot = { ...this.item };
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
            // Cuántas filas hay ahora. Es estado y no una cuenta del DOM porque de
            // él dependen el botón de agregar y el aviso del tope.
            count: 0,
            todasPlegadas: false,

            init() {
                this.updateIndexes();

                if (this.sortable) {
                    this.enableSorting();
                }
            },

            /**
             * Arrastre con eventos de puntero, delegado en el contenedor para que
             * las filas añadidas después queden cubiertas sin reenganchar nada.
             *
             * No se usa el arrastre nativo HTML5 porque en pantalla táctil no
             * existe: en un celular o una tablet el tirador no hacía nada. Con
             * punteros vale lo mismo para el ratón, el dedo y el lápiz.
             *
             * Sólo arranca desde el tirador, así el texto de los campos se sigue
             * pudiendo seleccionar con el ratón.
             */
            enableSorting() {
                const container = this.$refs.container;

                container.addEventListener('pointerdown', evento => {
                    const tirador = evento.target.closest('[data-drag-handle]');
                    const fila = evento.target.closest('.group-item');

                    if (!tirador || !fila || evento.button !== 0) return;

                    evento.preventDefault();
                    this.dragging = fila;
                    fila.classList.add('opacity-60');
                    // Capturar el puntero mantiene los eventos aunque el cursor
                    // se salga del tirador, que es lo que pasa al arrastrar.
                    tirador.setPointerCapture(evento.pointerId);
                });

                container.addEventListener('pointermove', evento => {
                    if (!this.dragging) return;

                    evento.preventDefault();

                    const debajo = document.elementFromPoint(evento.clientX, evento.clientY);
                    const destino = debajo?.closest?.('.group-item');

                    if (!destino || destino === this.dragging || !container.contains(destino)) return;

                    const caja = destino.getBoundingClientRect();
                    const porDebajo = evento.clientY > caja.top + caja.height / 2;

                    container.insertBefore(this.dragging, porDebajo ? destino.nextSibling : destino);
                });

                const soltar = () => {
                    if (!this.dragging) return;

                    this.dragging.classList.remove('opacity-60');
                    this.dragging = null;
                    // El orden del DOM es el orden del array enviado: al reindexar
                    // los name, la nueva posición queda persistida sin campo extra.
                    this.updateIndexes();
                };

                container.addEventListener('pointerup', soltar);
                container.addEventListener('pointercancel', soltar);
            },

            /**
             * Agrega una fila.
             *
             * Sin referencia va al final, que es lo de siempre. Con una fila de
             * referencia se inserta antes o después de ella, para poder meter un
             * elemento en medio de la lista sin tener que arrastrarlo luego.
             *
             * Los índices de los name se recalculan al final, así que da igual en
             * qué posición entre: el orden del DOM es el orden que se guarda.
             */
            /**
             * Pliega o despliega todas las filas de una vez.
             *
             * Se le escribe el estado a cada fila en vez de llamar a su startEdit(),
             * que además mueve el foco: desplegando diez filas, el foco acabaría en
             * la última y la pantalla saltaría hasta allá.
             */
            alternarTodas() {
                const plegar = !this.todasPlegadas;

                this.$refs.container.querySelectorAll('.group-item').forEach(fila => {
                    const datos = window.Alpine?.$data(fila);
                    if (datos) datos.editing = !plegar;
                });

                this.todasPlegadas = plegar;
            },

            addItem(referencia = null, posicion = 'after') {
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

                const vecina = referencia?.closest?.('.group-item');

                if (vecina && container.contains(vecina)) {
                    container.insertBefore(newItem, posicion === 'before' ? vecina : vecina.nextSibling);
                } else {
                    container.appendChild(newItem);
                }

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

                this.count = items.length;

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