@props([])

@php
    $guest = $guest ?? null;
    $invitation = $invitation ?? null;
@endphp

<x-controls.inputs.input label="Nombre" type="text" name="name" placeholder="Ej. Betsy Torres"
    value="{{ old('name', $guest?->name) }}" required autofocus />

{{-- Teléfono con selector de país (intl-tel-input). El input visible es 'phone_number';
     el número completo en formato internacional viaja en el oculto 'phone'. --}}
<div class="flex flex-col gap-1.5 w-full mb-4">
    <label for="phone" class="font-inter text-size-small-heading text-black">
        Teléfono (WhatsApp) <span class="text-red-500">*</span>
    </label>
    <input id="phone" type="tel" name="phone_number" value="{{ old('phone', $guest?->phone) }}"
        @class([
            'border rounded-md px-4 py-1 h-[50px] text-size-small-heading font-inter w-full',
            'border-red-500 text-red-900' => $errors->has('phone'),
            'border-base-gray text-black' => !$errors->has('phone'),
        ]) />
    @error('phone')
        <span class="error text-red-500 text-size-small-heading font-inter block">{{ $message }}</span>
    @enderror
</div>

<x-controls.inputs.input label="Correo electrónico (opcional)" type="email" name="email"
    placeholder="Ej. betsy@correo.com" value="{{ old('email', $guest?->email) }}" />

<x-controls.inputs.input label="Tope de acompañantes" type="number" name="max_passes" min="0" max="50"
    placeholder="Ej. 2" value="{{ old('max_passes', $invitation?->max_passes ?? 1) }}" required />

@once
    @push('scripts')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/css/intlTelInput.css">
        <style>.iti { width: 100%; }</style>
        <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/js/intlTelInput.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const input = document.querySelector('#phone');
                if (!input || !window.intlTelInput) return;

                window.intlTelInput(input, {
                    initialCountry: 'mx',
                    separateDialCode: true,
                    strictMode: true,
                    nationalMode: true,
                    hiddenInput: () => ({ phone: 'phone', country: 'country_code' }),
                    loadUtils: () => import('https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/js/utils.js'),
                });
            });
        </script>
    @endpush
@endonce
