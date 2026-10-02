@props(['label' => ''])

{{--
    Acciones de una fila compacta. Viven en el repeater y no en el control, para que
    una fila de varios campos tenga un solo lápiz y una sola papelera.
    startEdit/stopEdit/removeItem se resuelven en el scope Alpine de la fila.
--}}
{{-- En móvil los botones se apilan para no robarle ancho al texto de la fila. --}}
<div class="shrink-0 flex flex-col items-end gap-1 sm:flex-row sm:items-center sm:gap-3">
    {{--
        Insertar en medio sólo con la fila plegada: es cuando se ve la lista
        completa y se sabe dónde hace falta una nueva.
    --}}
    <template x-if="!editing">
        <span class="flex items-center gap-3">
            <button type="button"
                @click="addItem($el, 'before')"
                x-show="count < max"
                aria-label="Agregar {{ strtolower($label) ?: 'elemento' }} arriba de este"
                class="text-[#3952F6] font-inter text-parrafo cursor-pointer">+ arriba</button>

            <button type="button"
                @click="addItem($el, 'after')"
                x-show="count < max"
                aria-label="Agregar {{ strtolower($label) ?: 'elemento' }} abajo de este"
                class="text-[#3952F6] font-inter text-parrafo cursor-pointer">+ abajo</button>
        </span>
    </template>

    <button type="button"
        @click="editing ? stopEdit() : startEdit()"
        :aria-label="editing ? 'Terminar edición' : @js('Editar ' . $label)"
        x-text="editing ? 'Listo' : 'Editar'"
        class="text-[#3952F6] font-inter text-parrafo transition-colors duration-150 cursor-pointer">
        Editar
    </button>

    <button type="button"
        @click="removeItem($event)"
        aria-label="Eliminar {{ $label }}"
        class="item-remove-btn text-red-500 font-inter text-parrafo transition-colors cursor-pointer">
        Eliminar
    </button>
</div>
