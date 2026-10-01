<div class="bg-primary400 py-16 md:pb-20 md:pt-10 lg:pb-24 lg:pt-12">
    <div class="container">
        <div class="max-w-lg mx-auto">
            <form action="{{ route('rsvp.store') }}" method="POST" novalidate>
                @csrf
                <h2 class="text-complement300 font-secondary text-size-title text-center mb-6 md:mb-10 lg:mb-12">
                    ¡Queremos reservarte un asiento con nosotros!</h2>
                <h4 class="text-complement400 font-secondary text-size-heading font-medium mb-6 md:mb-8">Por
                    favor, confirma antes del 15 de septiembre 2025</h4>
                @if ($errors->any())
                    <div class="bg-red-100 text-red-800 p-4 rounded mb-6">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (session('errorReservation'))
                    <div class="bg-red-100 text-red-800 p-4 rounded mb-6 text-center">
                        {{ session('errorReservation') }}
                    </div>
                @endif
                <div class="relative">
                    <x-controls.input variant="secondary" id="name_autocomplete" type="text" name="name"
                        placeholder="Escribe tu nombre" autocomplete="off" label="Nombre completo" value="{{ old('name') }}" required />
                    <ul id="suggestions" class="hidden"></ul>
                </div>
                <x-controls.rsvp-radio label="Asistirás a la boda" class="mb-8" name="asistencia"
                    :selected="old('asistencia')" />
                <x-controls.select label="¿Tienes alguna restricción alimentaria o alergias?"
                    name="dietary_restrictions" :options="[
        'ninguna' => 'Ninguna',
        'vegetariano' => 'Vegetariano',
        'vegano' => 'Vegano',
        'celiaco' => 'Sin gluten (celíaco)',
        'lactosa' => 'Intolerancia a la lactosa',
        'frutos-secos' => 'Alergia a frutos secos',
        'mariscos' => 'Alergia a mariscos',
        'huevo' => 'Alergia al huevo',
        'soya' => 'Alergia a la soya',
        'otra' => 'Otra'
    ]" placeholder="Selecciona una opción" />
                <x-controls.textarea variant="secondary" label="¿Deseas enviar un mensaje o felicitación para los novios?" name="comments"
                    placeholder="Escribe tu mensaje" :value="old('comments')" />
                <div class="mt-4 flex justify-center">
                    <x-controls.button variant="main" type="submit">Confirmar asistencia</x-controls.button>
                </div>
            </form>
        </div>
    </div>
</div>