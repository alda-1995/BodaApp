<?php

namespace App\Contracts;

use App\FormBuilder\Field;

interface SchemaCompilerInterface
{
    /**
     * Compila un array de definición de atributos en una instancia de Field.
     * 
     * @param array<string, mixed> $attributes
     */
    public function compileField(array $attributes): Field;

    /**
     * Construye y devuelve el objeto Field resultante (útil cuando se inicializa con un modelo como SystemSection).
     */
    public function build(): ?Field;

    /**
     * Permite registrar dinámicamente nuevos tipos de controles en el mapa del compilador.
     *
     * @param string $alias El identificador del tipo (ej: 'text', 'signature', 'map')
     * @param class-string<Field> $fieldClass La FQCN de la clase que hereda de Field
     */
    public static function registerFieldType(string $alias, string $fieldClass): void;
}