<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-lg">
                <nav class="flex flex-wrap gap-2 mb-4 text-parrafo text-[#737373]" aria-label="Ruta">
                    <a href="{{ route('organizer.guests.index') }}" class="hover:text-black">Invitados</a>
                    <span>/</span>
                    <span class="text-black">Importar</span>
                </nav>

                <div class="mb-6">
                    <h2 class="text-size-title text-black mb-2">Importar invitados</h2>
                    <p class="text-parrafo text-[#737373]">
                        Sube tu lista en CSV. Desde Excel o Google Sheets: <em>Archivo › Descargar › CSV</em>.
                        Descarga el formato y llena una fila por invitado: <strong>Nombre</strong> y <strong>Teléfono</strong>
                        son obligatorios; <strong>Correo</strong> y <strong>Acompañantes</strong> son opcionales
                        (1 acompañante por defecto).
                    </p>
                </div>

                @if (session('import_errors'))
                    <div class="mb-6 rounded-md bg-[#F2E0E0] px-4 py-3 text-parrafo text-[#8C5926]">
                        <p class="mb-1">Estas filas no se importaron:</p>
                        <ul class="list-disc pl-5 space-y-0.5">
                            @foreach (session('import_errors') as $importError)
                                <li>{{ $importError }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-6">
                    <x-controls.button href="{{ route('organizer.guests.import_template') }}" variant="cancel">
                        Descargar formato
                    </x-controls.button>
                </div>

                <form method="POST" action="{{ route('organizer.guests.process_import') }}" enctype="multipart/form-data" novalidate>
                    @csrf

                    <div class="flex flex-col gap-1.5 w-full">
                        <label for="file" class="font-inter text-size-small-heading text-black">Archivo CSV</label>
                        <input type="file" name="file" id="file" accept=".csv,text/csv"
                            class="block w-full text-parrafo text-black border border-base-gray rounded-md cursor-pointer bg-white file:mr-4 file:border-0 file:bg-[#F2F2F2] file:px-4 file:py-3 file:text-black" />
                        @error('file')
                            <span class="error text-red-500 text-size-small-heading font-inter block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mt-8 flex gap-4">
                        <x-controls.button type="submit">Importar invitados</x-controls.button>
                        <x-controls.button href="{{ route('organizer.guests.index') }}" variant="cancel">
                            Cancelar
                        </x-controls.button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>
