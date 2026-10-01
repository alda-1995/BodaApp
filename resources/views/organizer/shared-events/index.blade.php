<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-xl">
                <div class="mb-8">
                    <h2 class="text-size-title text-black mb-2">Invitaciones compartidas</h2>
                    <p class="text-parrafo text-[#737373]">Bodas donde te invitaron a ayudar como coadministrador.</p>
                </div>

                @if ($events->isEmpty())
                    <div class="rounded-xl border border-[#F2F2F2] bg-white p-8 text-center">
                        <p class="text-parrafo text-[#737373]">
                            Todavía nadie te ha invitado a administrar su boda.
                        </p>
                    </div>
                @else
                    <ul class="space-y-3">
                        @foreach ($events as $event)
                            @php
                                $names = $event->displayNames();
                                $available = $event->isAvailable();
                            @endphp
                            <li class="flex flex-col gap-4 rounded-xl border border-[#F2F2F2] bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="truncate text-parrafo text-black">
                                        {{ $names ? implode(' y ', $names) : ($event->title ?: 'Boda sin nombre') }}
                                    </p>
                                    <p class="truncate text-size-small-heading text-[#8C8C8C]">
                                        De {{ $event->user->name ?: $event->user->email }}
                                        @if ($event->expires_at)
                                            · {{ $event->event_date->format('d/m/Y') }}
                                        @endif
                                    </p>
                                    <span @class([
                                        'mt-2 inline-block rounded-full px-3 py-1 text-xs',
                                        'bg-[#E0F2E3] text-[#267333]' => $available,
                                        'bg-[#F2E0E0] text-[#8C2626]' => !$available,
                                    ])>
                                        {{ $available ? 'Activa' : 'Vencida' }}
                                    </span>
                                </div>

                                @if ($available)
                                    <div class="flex shrink-0 flex-wrap items-center gap-3">
                                        <x-controls.button variant="secondary"
                                            href="{{ route('events.wizard.edit', ['event' => $event->slug]) }}">
                                            Editar información
                                        </x-controls.button>

                                        @if ($event->custom_url)
                                            <a href="{{ $event->invitationUrl() }}" target="_blank" rel="noopener"
                                                class="text-size-small-heading text-[#2563EB] hover:underline">
                                                Ver invitación
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>
