@props([
    'slug' => '',
])
<section class="bg-base-redop pt-32 pb-20 md:py-16 md:min-h-screen md:flex md:flex-col md:justify-center">
    <div class="container">
        <div class="max-w-lg mx-auto flex flex-col items-center text-center">
            <h2 class="font-inter text-size-title mb-4">UX/UI – Acceso y Gestión de Cuenta</h2>
            <h4 class="font-inter text-size-heading mb-4 md:mb-6">Ya puedes comenzar a configurar tu invitación.</h4>
            @if($slug)
            <x-controls.button href="{{ route('events.wizard.edit', $slug) }}">
                Comenzar
            </x-controls.button>
            @endif
        </div>
    </div>
</section>