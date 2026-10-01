@php
$columns = [
    [
        'label' => 'Nombre',
        'width' => '400px',
        'render' => fn($user) => e($user->name),
    ],
    [
        'label' => 'Correo',
        'width' => '400px',
        'render' => fn($user) => e($user->email),
    ],
     [
        'label' => 'Acciones',
        'width' => '240px',
        'render' => fn($user) => '<a href="' . route('users.show', $user->id) . '" class="hover:underline font-bold">Ver detalle</a>',
    ],
];
@endphp
<x-layouts.auth-layout>
    <div class="flex flex-col max-w-[960px]">
        <div class="flex flex-wrap justify-between gap-3 py-4">
            <h2 class="text-complement300 font-roboto text-size-subtitle font-medium">Usuarios</h2>
             <x-controls.button href="{{ route('users.create') }}" variant="primary">
                Añadir usuario
            </x-controls.button>
        </div>
        <div class="py-3">
            <x-tables.table-with-pagination :columns="$columns" :items="$users" />
        </div>
    </div>
</x-layouts.auth-layout>