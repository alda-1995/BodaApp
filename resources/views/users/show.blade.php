<x-layouts.auth-layout>
    <!-- Modal de confirmación -->
    <x-modals.confirm-modal id="confirm-delete"
        title="¿Eliminar Usuario?"
        :message="'¿Estás seguro de que deseas eliminar el usuario <strong>' . $user->name . '</strong>? Esta acción no se puede deshacer.'"
        :action="route('users.destroy', $user->id)"
        confirm-text="Eliminar" />
    
    <div class="flex flex-col max-w-[960px]">
        <div class="flex flex-wrap gap-2 py-4">
            <a class="text-[#6a7681] text-base font-medium leading-normal" href="{{ route('users.index') }}">Usuarios</a>
            <span class="text-[#6a7681] text-base font-medium leading-normal">/</span>
            <span class="text-[#121416] text-base font-medium leading-normal">{{ $user->name }}</span>
        </div>
    
        <div class="flex flex-wrap justify-between gap-3 py-4">
            <div class="flex min-w-72 flex-col gap-3">
                <p class="text-[#121416] tracking-light text-[32px] font-bold leading-tight">Detalles del usuario</p>
                <p class="text-[#6a7681] text-sm font-normal leading-normal">Ver y administrar la información del usuario</p>
            </div>
        </div>
    
        {{-- Información de contacto --}}
        <h3 class="text-[#121416] text-lg font-bold leading-tight tracking-[-0.015em] pb-2 pt-4">Información del contacto</h3>
        <div class="py-4 grid grid-cols-[20%_1fr] gap-x-6">
            <div class="col-span-2 grid grid-cols-subgrid border-t border-t-[#dde1e3] py-5">
                <p class="text-[#6a7681] text-sm font-normal leading-normal">Correo electrónico</p>
                <p class="text-[#121416] text-sm font-normal leading-normal">{{ $user->email }}</p>
            </div>
        </div>
    
        <div class="flex justify-stretch">
            <div class="flex flex-1 gap-3 flex-wrap py-3 justify-end">
               <x-controls.button href="{{ route('users.edit', $user->id) }}" variant="primary">
                    Editar Usuario
                </x-controls.button> 
                <x-controls.button variant="remove" data-modal="confirm-delete" class="open-modal-btn">Eliminar Usuario</x-controls.button>
            </div>
        </div>
    </div>
</x-layouts.auth-layout>