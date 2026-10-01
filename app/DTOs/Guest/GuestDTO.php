<?php

namespace App\DTOs\Guest;

/**
 * Datos de un invitado del organizador. Lo usan el formulario y la importación,
 * así ambos validan y guardan exactamente igual.
 */
final class GuestDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email,
        public readonly string $phone,
        public readonly int $maxPasses,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) $data['name']),
            email: filled($data['email'] ?? null) ? trim((string) $data['email']) : null,
            phone: trim((string) $data['phone']),
            maxPasses: (int) ($data['max_passes'] ?? 1),
        );
    }

    /**
     * Columnas de la tabla guests. El tope de acompañantes vive en el pivote event_guest.
     */
    public function guestAttributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }

    /**
     * Reglas compartidas por el formulario y la importación.
     * La unicidad del correo (por organizador) la agrega el FormRequest.
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+(?:[0-9] ?){6,16}[0-9]$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'max_passes' => ['required', 'integer', 'min:0', 'max:50'],
        ];
    }

    public static function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder los :max caracteres.',

            'phone.required' => 'El teléfono es obligatorio.',
            'phone.regex' => 'El teléfono debe incluir la lada del país, por ejemplo +522221234567.',

            'email.email' => 'El correo electrónico no es válido.',
            'email.max' => 'El correo electrónico no debe exceder los :max caracteres.',
            'email.unique' => 'Ya tienes un invitado con este correo electrónico.',

            'max_passes.required' => 'Indica cuántos acompañantes puede llevar.',
            'max_passes.integer' => 'El tope de acompañantes debe ser un número entero.',
            'max_passes.min' => 'El tope de acompañantes no puede ser negativo.',
            'max_passes.max' => 'El tope de acompañantes no puede ser mayor a :max.',
        ];
    }
}
