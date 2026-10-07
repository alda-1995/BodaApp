<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="mb-8">
                <h2 class="text-size-title text-black mb-2">Información del evento</h2>
                <p class="text-parrafo text-[#737373]">Aquí configuras la invitación digital de tu boda.</p>
            </div>

            <x-organizer.buy-invitation :past="$pastEvents" :has-shared-events="$hasSharedEvents" />
        </div>
    </section>
</x-layouts.dashboard-layout>
