<x-layouts.auth-layout>
    <div class="flex flex-col max-w-[640px]">
        <form action="{{ route('users.store') }}" method="POST" novalidate>
            @csrf
            <div class="flex flex-wrap justify-between gap-3 py-4">
                <h2 class="text-complement300 font-roboto text-size-subtitle font-medium">Crear Usuario</h2>
            </div>
            <x-controls.error-main />
            <x-controls.validation-errors />
            {{-- Nombre --}}
            <x-controls.input id="name" type="text" name="name" placeholder="Nombre completo" label="Nombre"
                value="{{ old('name') }}" required autofocus />
            <x-controls.input id="email" type="email" name="email" placeholder="Correo electrónico" label="Correo"
                value="{{ old('email') }}" required />

            <x-controls.password value="{{ old('password') }}" placeholder="Escribe tu contraseña" id="password"
                name="password" label="Contraseña" required />
            <x-controls.password value="{{ old('password_confirmation') }}" placeholder="Confirma tu contraseña"
                id="password_confirmation" name="password_confirmation" label="Confirmar contraseña" required />
            {{-- Botones --}}
            <div class="flex gap-4 mt-8">
                <x-controls.button type="submit">Guardar</x-controls.button>
                <x-controls.button href="{{ route('users.index') }}" variant="secondary">
                    Cancelar
                </x-controls.button>
            </div>
        </form>
    </div>
</x-layouts.auth-layout>