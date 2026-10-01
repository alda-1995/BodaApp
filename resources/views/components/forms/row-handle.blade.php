@props(['sortable' => false])

@if($sortable)
    {{-- Sólo marca el punto de agarre; el arrastre lo gobierna dynamicGroup. --}}
    <button type="button"
        data-drag-handle
        aria-label="Reordenar"
        class="shrink-0 cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 touch-none">
        <x-icons.drag-handle />
    </button>
@endif
