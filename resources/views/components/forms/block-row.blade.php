@props([
    'schema' => [],
    'subKeys' => [],
    'name' => '',
    'index' => '',
    'itemData' => [],
    'defaults' => [],
    'sortable' => false,
    'label' => '',
    'number' => null,
    'open' => true,
    'focus' => false,
])

{{--
    Fila de un repeater cuyos campos se dibujan completos (selectores, imágenes,
    colores...). Se usa igual para las filas que vienen del servidor y para el
    blueprint que clona "+ Agregar", así la cabecera se escribe una sola vez.

    La fila nace abierta: el formulario es lo primero que hay que ver. Quien
    quiera la lista entera de un vistazo la pliega con su botón, y entonces cada
    fila se resume en una línea —con su foto, si la tiene—.
--}}
<div
    x-data="repeaterRow({{ json_encode(array_merge($defaults, $itemData)) }}, { open: {{ $open ? 'true' : 'false' }}, focus: {{ $focus ? 'true' : 'false' }} })"
    class="group-item relative rounded-lg border border-[#EBEBEB] bg-white"
>
    <div class="flex items-start gap-2 p-4" :class="editing && 'border-b border-gray-100 pb-3'">
        {{-- El tirador va siempre: se reordena lo mismo abierta que cerrada. --}}
        <x-forms.row-handle :sortable="$sortable" />

        <div class="flex-1 min-w-0">
            <span class="item-number block text-xs font-bold text-gray-400 uppercase tracking-wider">
                #{{ $number ?? '__INDEX_NUMBER__' }}
            </span>

            {{-- Cerrada: la foto y el resumen de lo que el organizador escribió. --}}
            <span x-show="!editing" x-cloak class="flex items-center gap-2 mt-0.5">
                <template x-if="miniatura()">
                    <img :src="miniatura()" alt=""
                        class="shrink-0 h-10 w-10 rounded object-cover border border-[#EBEBEB]">
                </template>

                {{--
                    Una fila que sólo tiene foto se reconoce por la foto: poner
                    "Sin texto" al lado no aporta nada y parece un error.
                --}}
                <span x-show="resumen() || !miniatura()"
                    class="truncate font-inter text-size-small-heading"
                    :class="resumen() ? 'text-black' : 'text-gray-400'"
                    x-text="resumen() || 'Sin contenido'"></span>
            </span>
        </div>

        <div class="shrink-0 flex flex-col items-end gap-1 sm:flex-row sm:items-center sm:gap-3">
            {{--
                Insertar en medio sólo con la fila plegada: es cuando se ve la lista
                completa y se sabe dónde hace falta una nueva. Con la fila abierta
                estorbarían al lado del formulario que se está llenando.
            --}}
            <template x-if="!editing">
                <span class="flex items-center gap-3">
                    <button type="button" @click="addItem($el, 'before')"
                        x-show="count < max"
                        aria-label="Agregar {{ strtolower($label) ?: 'elemento' }} arriba de este"
                        class="text-[#3952F6] font-inter text-parrafo cursor-pointer">+ arriba</button>

                    <button type="button" @click="addItem($el, 'after')"
                        x-show="count < max"
                        aria-label="Agregar {{ strtolower($label) ?: 'elemento' }} abajo de este"
                        class="text-[#3952F6] font-inter text-parrafo cursor-pointer">+ abajo</button>
                </span>
            </template>

            {{-- Plegar y desplegar es su propio botón, no la cabecera entera. --}}
            <button type="button" @click="editing ? stopEdit() : startEdit()"
                :aria-expanded="editing ? 'true' : 'false'"
                :aria-label="editing ? 'Colapsar {{ strtolower($label) ?: 'elemento' }}' : 'Expandir {{ strtolower($label) ?: 'elemento' }}'"
                x-text="editing ? 'Colapsar' : 'Expandir'"
                class="text-[#3952F6] font-inter text-parrafo cursor-pointer">Colapsar</button>

            <button type="button" @click="removeItem($event)"
                aria-label="Eliminar {{ strtolower($label) ?: 'elemento' }}"
                class="item-remove-btn text-red-500 font-inter text-parrafo cursor-pointer">Eliminar</button>
        </div>
    </div>

    <div x-show="editing" x-cloak class="px-4 pb-4">
        @foreach ($schema as $subIndex => $subField)
            @php
                $subKey = $subKeys[$subIndex] ?? $subIndex;
            @endphp
            <x-forms.render-field :field="$subField" :name="$name . '[' . $index . '][' . $subKey . ']'"
                :value="$itemData[$subKey] ?? null" :is-repeater="true" />
        @endforeach
    </div>
</div>
