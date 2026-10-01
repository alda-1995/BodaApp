@props([
    'schema' => [],
    'subKeys' => [],
    'name' => '',
    'index' => '',
    'itemData' => [],
    'defaults' => [],
    'sortable' => false,
    'label' => '',
    'open' => false,
    'focus' => false,
])

{{--
    Fila compacta de un repeater cuyos campos se dibujan a sí mismos (inline-text,
    inline-textarea). Arriba la vista previa con las acciones; debajo, al editar,
    un panel con cada campo a todo el ancho. Se usa igual para las filas que vienen
    del servidor y para el blueprint que clona "+ Agregar".
--}}
<div
    x-data="repeaterRow({{ json_encode(array_merge($defaults, $itemData)) }}, { open: {{ $open ? 'true' : 'false' }}, focus: {{ $focus ? 'true' : 'false' }} })"
    @click.outside="stopEdit()"
    class="group-item relative rounded-md border border-base-gray bg-white px-3 py-2"
>
    <div class="flex items-start gap-2">
        <x-forms.row-handle :sortable="$sortable" />

        <div class="flex-1 min-w-0 space-y-0.5">
            @foreach ($schema as $subIndex => $subField)
                <x-forms.inline-preview :field="$subField" :item-key="$subKeys[$subIndex] ?? $subIndex" />
            @endforeach
        </div>

        <x-forms.row-actions :label="$label" />
    </div>

    <div x-show="editing" x-cloak class="mt-3 pt-3 border-t border-gray-100 space-y-3">
        @foreach ($schema as $subIndex => $subField)
            @php
                $subKey = $subKeys[$subIndex] ?? $subIndex;
            @endphp
            <x-forms.render-field
                :field="$subField"
                :name="$name . '[' . $index . '][' . $subKey . ']'"
                :value="$itemData[$subKey] ?? null"
                :is-repeater="true" />
        @endforeach
    </div>
</div>
