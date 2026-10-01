@props(['label' => ''])

{{--
    Acciones de una fila compacta. Viven en el repeater y no en el control, para que
    una fila de varios campos tenga un solo lápiz y una sola papelera.
    startEdit/stopEdit/removeItem se resuelven en el scope Alpine de la fila.
--}}
{{-- En móvil los botones se apilan para no robarle ancho al texto de la fila. --}}
<div class="shrink-0 flex flex-col items-end gap-1 sm:flex-row sm:items-center sm:gap-3">
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
