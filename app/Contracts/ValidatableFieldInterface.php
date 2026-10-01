<?php

namespace App\Contracts;

interface ValidatableFieldInterface
{
    /**
     * Devuelve las reglas de validación asignadas a sus correspondientes keys con dot-notation.
     */
    public function toValidationRules(string $parentKey = ''): array;

    /**
     * Devuelve los nombres amigables de los atributos para la respuesta de errores.
     */
    public function toValidationAttributes(string $parentKey = ''): array;
}