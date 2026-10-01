<x-layouts.guest-layout>
    <div class="min-h-screen pt-32 pb-20 md:py-16 flex flex-col md:justify-center bg-white">
        <div class="container">
            <div class="max-w-lg mx-auto">
                <h2 class="text-size-title font-inter text-center mb-4 text-black">Nueva contraseña</h2>
                <form action="{{ route('password.update') }}" method="POST" novalidate>
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

                    <div class="space-y-4">
                        <x-controls.password name="password" icon="lock" placeholder="Nueva contraseña" required
                            autofocus />
                        <x-controls.password name="password_confirmation" icon="lock"
                            placeholder="Confirmar nueva contraseña" required />
                    </div>
                    <p class="text-accent font-inter text-parrafo mt-6">
                        Debe tener al menos 8 caracteres, una mayúscula y un número.
                    </p>
                    <div class="flex gap-4 mt-8">
                        <x-controls.button type="submit">Actualizar contraseña</x-controls.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.guest-layout>