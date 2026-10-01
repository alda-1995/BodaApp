@props([
'id',
'title' => '¿Estás seguro?',
'message' => 'Esta acción no se puede deshacer.',
'confirmText' => 'Eliminar',
'action' => '#',
'method' => 'DELETE',
])

<div id="{{ $id }}" class="modal hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center font-roboto">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold text-[#121416] mb-4">{{ $title }}</h2>
        <p class="text-sm text-[#6a7681] mb-6">{!! $message !!}</p>

        <div class="flex justify-end gap-3">
            <button class="modal-cancel px-4 py-2 rounded-full bg-gray-200 text-sm font-medium text-[#121416]">
                Cancelar
            </button>
            <form method="POST" action="{{ $action }}">
                @csrf
                @method('DELETE')
                <button
                    class="modal-confirm px-4 py-2 rounded-full bg-red-500 text-white text-sm font-medium hover:bg-red-600 transition">
                    {{ $confirmText }}
                </button>
            </form>
        </div>
    </div>
</div>