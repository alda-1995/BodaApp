<x-layouts.dashboard-layout>
    <div class="container mx-auto py-10">

        <!-- ENCABEZADO Y STEPPER -->
        <div class="mb-8">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-2">
                <h2 class="text-size-title">Información del evento</h2>

                @if ($event->custom_url)
                    {{-- Cómo va quedando, tal como la verán los invitados. --}}
                    <x-controls.button href="{{ $event->invitationUrl() }}" variant="secondary"
                        target="_blank" rel="noopener">
                        Ver mi invitación
                    </x-controls.button>
                @endif
            </div>
            <p class="text-[#737373] text-parrafo mb-6">
                Cuéntanos los datos generales de su boda. Puedes guardar en cualquier momento y continuar después.
            </p>

            <x-events.wizard-stepper
                :steps="$steps"
                :current-step-key="$currentStepKey"
                :event="$event"
                :progress="$progress" />
        </div>

        <!-- FORMULARIO DEL PASO ACTIVO -->
        <form method="POST" novalidate enctype="multipart/form-data" action="{{ route('events.wizard.update', ['event' => $event->slug, 'step' => $currentStepKey]) }}">
            @csrf
            @method('PUT')

            <h2 class="text-size-heading mb-6 text-black">{{ $activeStep['title'] }}</h2>

            <div class="max-w-xl">
                @foreach($activeStep['fields'] as $fieldKey => $field)
                    <x-forms.render-field 
                        :field="$field" 
                        :name="$fieldKey"
                        :value="$savedValues[$fieldKey] ?? null"
                        :help-replacements="$helpReplacements"
                    />
                @endforeach
            </div>

            <div class="mt-8 flex items-center gap-4">
                <x-controls.button type="submit">Guardar y continuar</x-controls.button>
                <span class="text-size-small-heading text-[#8C8C8C]">Tus cambios se guardan al dar click en el botón.</span>
            </div>
        </form>
    </div>
</x-layouts.dashboard-layout>