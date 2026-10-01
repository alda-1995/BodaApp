@props([
    'id' => 'deleteModal',
    'formId' => 'confirmDeleteForm',
    'cancelId' => 'cancelDeleteBtn',
    'title' => 'Confirmar eliminación',
    'message' => '¿Estás seguro de que deseas eliminar?',
    'action' => '',
    'confirmText' => 'Sí, Eliminar'

])

<div id="{{ $id }}" class="fixed inset-0 z-40 overflow-y-auto hidden">
    <div class="fixed inset-0 bg-primary/80 transition-opacity"
        onclick="document.getElementById('{{ $id }}').classList.add('hidden')">
    </div>

    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative font-inter bg-white rounded-lg shadow-xl max-w-sm w-full p-6 text-center">
            
            <h3 class="text-black text-size-subtitle">{{ $title }}</h3>

            <p class="js-modal-message text-parrafo mt-4 text-accent">{{ $message }}</p>

            <div class="mt-6 flex justify-center space-x-3">
                <form id="{{ $formId }}" {{ $action ? "action=$action" : "" }} method="POST" class="inline-block">
                    @csrf
                    @method('DELETE')
                    <x-controls.button type="submit" variant="remove">
                        {{ $confirmText }}
                    </x-controls.button>
                </form>
                <x-controls.button id="{{ $cancelId }}" variant="cancel">
                    Cancelar
                </x-controls.button>
            </div>
        </div>
    </div>
</div>