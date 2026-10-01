<x-layouts.dashboard-layout>
    <section>
        @if (!$available)
            {{-- Sin invitación propia, o la que tenía ya venció. --}}
            <section class="pt-32 pb-20 md:py-10 font-inter">
                <div class="container">
                    <div class="mb-8 space-y-2">
                        <h2 class="text-size-title">Dashboard</h2>
                        <p class="text-parrafo text-[#595959]">Hola, {{ auth()->user()->name ?: 'Usuario' }}</p>
                    </div>
                    <x-organizer.buy-invitation :event="$event" :has-shared-events="$hasSharedEvents" />
                </div>
            </section>
        @elseif (!$hasFeatures)
            <x-reusable.welcome-home :slug="$eventSlug" />
        @else
            <x-organizer.dashboard.dashboard-overview
                :metrics="$metrics"
                :progress-percentage="$progress['percentage']"
                :progress-url="$progressUrl"
                :invitation-url="$event->custom_url ? $event->invitationUrl() : null"
                :notifications-url="route('organizer.notifications.index')"
                :rsvps-url="route('organizer.rsvps.index')" />
        @endif
    </section>
</x-layouts.dashboard-layout>
