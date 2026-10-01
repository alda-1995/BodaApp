@props([
    'field' => null,
    'itemKey' => '',
])

@php
    $multiline = is_object($field) && $field->getType() === 'inline-textarea';
    $emptyText = Js::from((is_object($field) ? $field->getPlaceholder() : null) ?: 'Sin contenido');

    // Mientras la fila se edita se lee el snapshot (el valor antes de editar), así
    // que el texto de arriba sólo cambia al cerrar el editor.
    $raw = 'String((editing ? snapshot : item)[' . Js::from($itemKey) . '] ?? \'\')';
@endphp

{{--
    Línea de vista previa de un campo inline, dentro del scope del repeaterRow.
    El título va en negro; la descripción, en gris y sólo su primera línea.
--}}
<p
    @click="startEdit()"
    class="truncate font-inter text-size-small-heading cursor-pointer"
    @if($multiline)
        :class="{{ $raw }} ? 'text-gray-500' : 'text-gray-400'"
        x-text="{{ $raw }}.split('\n')[0] || {{ $emptyText }}"
    @else
        :class="{{ $raw }} ? 'text-black' : 'text-gray-400'"
        x-text="{{ $raw }} || {{ $emptyText }}"
    @endif
></p>
