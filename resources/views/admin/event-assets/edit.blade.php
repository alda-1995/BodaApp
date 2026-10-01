@php
    $empty = '—';
@endphp

<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-2xl mb-6">
                <h2 class="text-size-title text-black mb-2">Imágenes de la plantilla</h2>
                <p class="text-parrafo text-[#737373]">
                    Lo que cambies aquí aplica sólo a la invitación de
                    <span class="text-black">{{ $event->user?->name ?? 'este usuario' }}</span>
                    ({{ $event->template?->name ?? $empty }}). No toca lo que captura el organizador ni a las demás bodas.
                </p>
            </div>

            <x-alerts.success />
            <x-alerts.error />

            @if (session('warning'))
                <div class="mb-6 max-w-2xl rounded-md bg-[#FCFDCA] px-4 py-3 text-parrafo text-[#54501A]">
                    {{ session('warning') }}
                </div>
            @endif

            @forelse ($groups as $group)
                <div class="mb-8">
                    <h3 class="text-size-subtitle text-black mb-4">{{ $group['title'] }}</h3>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($group['assets'] as $key => $asset)
                            <div class="rounded-lg border border-[#EBEBEB] bg-white p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-parrafo text-black">{{ $asset['label'] }}</p>

                                        @if ($asset['help'])
                                            <p class="text-size-small-heading text-[#808080] mt-1">{{ $asset['help'] }}</p>
                                        @endif
                                    </div>

                                    <span @class([
                                        'shrink-0 rounded-full px-3 py-1 text-size-small-heading',
                                        'bg-[#E0F2E3] text-[#267333]' => $asset['is_overridden'],
                                        'bg-gray-100 text-gray-700' => !$asset['is_overridden'],
                                    ])>
                                        {{ $asset['is_overridden'] ? 'Personalizada' : 'De la plantilla' }}
                                    </span>
                                </div>

                                {{-- Lo que se ve hoy en la invitación. --}}
                                <div class="my-4 flex items-center justify-center rounded-md bg-[#FAFAFA] p-3 min-h-[120px]">
                                    @if ($asset['kind'] === 'sequence')
                                        <img src="{{ rtrim($asset['current'], '/') }}/frame1.{{ $asset['extension'] }}"
                                            alt="Primer cuadro de la secuencia" class="max-h-28">
                                    @else
                                        <img src="{{ $asset['current'] }}" alt="{{ $asset['label'] }}" class="max-h-28">
                                    @endif
                                </div>

                                <form method="POST" action="{{ route('superadmin.events.assets.update', $event) }}"
                                    enctype="multipart/form-data" class="flex flex-col gap-3">
                                    @csrf
                                    <input type="hidden" name="section" value="{{ $group['section'] }}">
                                    <input type="hidden" name="asset" value="{{ $key }}">

                                    @if ($asset['kind'] === 'sequence')
                                        <x-controls.inputs.file-drop name="frames" multiple
                                            :help="'Selecciona los ' . $asset['count'] . ' cuadros juntos. Se renombran en el orden de sus nombres de archivo.'" />
                                    @else
                                        <x-controls.inputs.file-drop name="image" />
                                    @endif

                                    <div class="flex flex-wrap gap-3">
                                        <x-controls.button type="submit">Guardar</x-controls.button>
                                    </div>
                                </form>

                                @if ($asset['is_overridden'])
                                    <form method="POST" action="{{ route('superadmin.events.assets.reset', $event) }}"
                                        class="mt-3">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="section" value="{{ $group['section'] }}">
                                        <input type="hidden" name="asset" value="{{ $key }}">

                                        <x-controls.button type="submit" variant="cancel">
                                            Volver a la de la plantilla
                                        </x-controls.button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-parrafo text-[#737373]">
                    Esta plantilla no declara imágenes que se puedan reemplazar.
                </p>
            @endforelse

            <div class="mt-8 flex flex-wrap gap-4">
                <x-controls.button variant="secondary" href="{{ route('admin.users.index') }}">
                    Volver a usuarios
                </x-controls.button>

                @if ($event->custom_url)
                    <x-controls.button variant="secondary" href="{{ $event->invitationUrl() }}" target="_blank">
                        Ver la invitación
                    </x-controls.button>
                @endif
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>
