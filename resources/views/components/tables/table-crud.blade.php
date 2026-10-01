@props([
    'columns',
    'items',
    'detailRoute' => null,
    'editRoute' => null,
    'deleteRoute' => null,
    'showActions' => true
])
@php
    $showActions = filter_var($showActions, FILTER_VALIDATE_BOOLEAN);
@endphp
<div class="overflow-x-auto overflow-y-hidden rounded-xl border border-[#F2F2F2] bg-white font-inter">
    <table class="min-w-full divide-y divide-[#F2F2F2]">
        <thead>
            <tr class="bg-[#FAFAFA]">
                @foreach ($columns as $column)
                    <th scope="col"
                        class="px-6 font-normal py-4 text-left w-[{{ $column['width'] ?? 'auto' }}] text-size-small-heading text-[#808080] tracking-wider">
                        {{ $column['label'] }}
                    </th>
                @endforeach
                @if ($showActions)
                <th scope="col" class="px-6 font-normal py-4 text-left w-[120px] text-size-small-heading text-[#808080] tracking-wider">
                    Acciones
                </th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-[#F2F2F2] bg-white">
            @forelse ($items as $item)
                <tr class="transition-colors duration-150">
                    {{-- Celdas de datos --}}
                    @foreach ($columns as $column)
                        <td class="h-16 px-6 py-4 text-parrafo font-normal text-[#1A1A1A] whitespace-nowrap">
                            @php
                                $cellContent = $column['render']($item);
                            @endphp

                            @if (!empty($column['type']) && $column['type'] === 'status')
                                {{-- Manejo de la columna de ESTADO vinculada a is_active --}}
                                @php
                                    $rawStatus = $cellContent ?? $item->is_active;
                                    $isEnabled = filter_var($rawStatus, FILTER_VALIDATE_BOOLEAN);
                                    $statusBadgeClass = $isEnabled ? 'bg-[#E0F2E3] text-[#267333]' : 'bg-[#F2E0E0] text-[#8C5926]';
                                    $statusText = $isEnabled ? 'Activo' : 'Inactivo';
                                @endphp
                                <div class="flex justify-start">
                                    <span class="inline-flex items-center justify-center px-3 py-1 text-parrafo font-normal rounded-full {{ $statusBadgeClass }}">
                                        {{ $statusText }}
                                    </span>
                                </div>
                            @elseif (!empty($column['isHtml']) && $column['isHtml'] === true)
                                {{-- Contenido HTML/Renderizado --}}
                                <div class="line-clamp-2">{!! html_entity_decode($cellContent) !!}</div>
                            @else
                                {{-- Contenido de texto simple --}}
                                {!! $cellContent !!}
                            @endif
                        </td>
                    @endforeach
                    @if ($showActions)
                    <td class="h-16 px-6 py-4 w-[120px] text-sm font-normal whitespace-nowrap">
                        <div class="flex items-center space-x-3">
                            @if (isset($detailRoute))
                                <a href="{{ route($detailRoute, $item['id'] ?? $item->id) }}"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors duration-150"
                                    title="Ver detalle">
                                    Ver
                                </a>
                            @endif
                            @if (isset($editRoute))
                                <a href="{{ route($editRoute, $item['id'] ?? $item->id) }}"
                                    class="text-[#3952F6] text-parrafo transition-colors duration-150"
                                    title="Editar">
                                    Editar
                                </a>
                            @endif
                            @if (isset($deleteRoute))
                                <button type="button"
                                    onclick="toggleModalDelete('deleteModalTableCrud', '{{ route($deleteRoute, $item->id) }}')"
                                    class="text-red-600 hover:text-red-800 text-parrafo transition-colors duration-150" title="Eliminar">
                                    Eliminar
                                </button>
                            @endif
                        </div>
                    </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + ($showActions ? 1 : 0) }}" class="text-center py-6 text-gray-500 text-parrafo">
                        No hay registros disponibles.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($items->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $items->withQueryString()->links() }}
        </div>
    @endif
</div>

@if(isset($deleteRoute))
    <x-modals.modal-confirm-delete id="deleteModalTableCrud" cancelId="btnCloseTableCrud" />
@endif

@push('scripts')
    <script>
        const btnCloseModal = document.getElementById("btnCloseTableCrud");
        if (btnCloseModal) {
            btnCloseModal.addEventListener("click", function (e) {
                e.preventDefault();
                const modal = document.getElementById("deleteModalTableCrud");
                if (modal) {
                    modal.classList.add('hidden');
                }
            });
        }
    </script>
@endpush