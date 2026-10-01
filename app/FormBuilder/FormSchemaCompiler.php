<?php

namespace App\FormBuilder;

use App\Contracts\SchemaCompilerInterface;
use App\FormBuilder\Controls\BooleanField;
use App\FormBuilder\Controls\ColorField;
use App\FormBuilder\Controls\DateTimeField;
use App\FormBuilder\Controls\ImageUploadField;
use App\FormBuilder\Controls\InlineTextareaField;
use App\FormBuilder\Controls\InlineTextField;
use App\FormBuilder\Controls\NumberField;
use App\FormBuilder\Controls\RepeaterField;
use App\FormBuilder\Controls\SelectField;
use App\FormBuilder\Controls\TextField;
use App\FormBuilder\Controls\TextareaField;
use App\FormBuilder\Controls\UrlField;
use App\Models\SystemSection;

class FormSchemaCompiler implements SchemaCompilerInterface
{
    /** Registry extensible de tipos de control */
    protected static array $fieldMapping = [
        'text'        => TextField::class,
        'inline-text' => InlineTextField::class,
        'inline-textarea' => InlineTextareaField::class,
        'textarea'    => TextareaField::class,
        'url'         => UrlField::class,
        'number'      => NumberField::class,
        'image'       => ImageUploadField::class,
        'bool'        => BooleanField::class,
        'repeater'    => RepeaterField::class,
        'select'      => SelectField::class,
        'color'       => ColorField::class,
        'datetime'    => DateTimeField::class,
    ];

    public function __construct(protected ?SystemSection $section = null) {}

    /**
     * Registra nuevos controles dinámicamente (Open/Closed Principle)
     */
    public static function registerFieldType(string $alias, string $fieldClass): void
    {
        static::$fieldMapping[$alias] = $fieldClass;
    }

    /**
     * Compila un array de definición en una instancia de Field
     */
    public function compileField(array $attributes): Field
    {
        $type  = $attributes['type'] ?? 'text';
        $key   = $attributes['key'] ?? $attributes['name'] ?? '';
        $label = $attributes['label'] ?? '';

        $fieldClass = static::$fieldMapping[$type] ?? TextField::class;
        
        /** @var Field $field */
        $field = $fieldClass::make($key, $label);

        // Configuración directa usando métodos heredados de Field
        if (!empty($attributes['placeholder'])) {
            $field->placeholder($attributes['placeholder']);
        }

        if (array_key_exists('default', $attributes) && $attributes['default'] !== null && $attributes['default'] !== '') {
            $field->default($attributes['default']);
        }

        if (!empty($attributes['options'])) {
            $field->options($this->normalizeOptions((array) $attributes['options']));
        }

        if (!empty($attributes['rules'])) {
            $field->rules((array) $attributes['rules']);
        }

        if (!empty($attributes['messages'])) {
            $field->messages($attributes['messages']);
        }

        if (filter_var($attributes['is_required'] ?? $attributes['required'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $field->required();
        }

        // 1. Manejo de Dependencias / Condiciones (Soporta 'depends_on' o 'condition')
        $dependsData = $attributes['depends_on'] ?? $attributes['condition'] ?? null;
        if (!empty($dependsData['field'])) {
            $field->dependsOn($dependsData['field'], $dependsData['values'] ?? []);
        }

        // 2. Manejo Recursivo para Repeaters
        if ($field instanceof RepeaterField && !empty($attributes['schema'])) {
            $nestedFields = array_map(
                fn (array $nestedDef) => $this->compileField($nestedDef),
                static::mergeDuplicateKeys($attributes['schema'])
            );
            $field->schema($nestedFields);
        }

        return $field;
    }

    /**
     * Junta las definiciones de un repeater que comparten clave.
     *
     * Declarar el mismo dato para varios tipos es válido y normal: "nombre de
     * la tienda" sirve igual para Liverpool que para Amazon. Pero es UN dato:
     * un input, una regla y un solo lugar donde se guarda. Lo único que se suma
     * son los tipos a los que aplica.
     *
     * Sin esto se generaban dos inputs con el mismo name, y al guardar el
     * segundo —oculto y vacío— le ganaba al que el organizador había llenado.
     *
     * @param  array<int|string, array<string, mixed>>  $schema
     * @return array<int, array<string, mixed>>
     */
    public static function mergeDuplicateKeys(array $schema): array
    {
        $merged = [];

        foreach ($schema as $index => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $key = (string) ($definition['key'] ?? $definition['name'] ?? $index);

            if (!isset($merged[$key])) {
                $merged[$key] = $definition;

                continue;
            }

            $merged[$key]['depends_on'] = static::mergeDependencies(
                $merged[$key]['depends_on'] ?? null,
                $definition['depends_on'] ?? null
            );
        }

        return array_values(array_filter($merged, 'is_array'));
    }

    /**
     * Une las condiciones de dos definiciones del mismo dato.
     *
     * Si alguna no depende de nada, el dato aplica siempre y la condición
     * desaparece. Si las dos dependen del mismo campo, se suman sus valores:
     * "aplica a Liverpool" + "aplica a Amazon" = "aplica a los dos".
     */
    protected static function mergeDependencies(?array $first, ?array $second): ?array
    {
        if (!$first || !$second) {
            return null;
        }

        // Condiciones sobre campos distintos no se pueden sumar: manda la primera.
        if (($first['field'] ?? null) !== ($second['field'] ?? null)) {
            return $first;
        }

        $first['values'] = array_values(array_unique(array_merge(
            (array) ($first['values'] ?? []),
            (array) ($second['values'] ?? []),
        )));

        return $first;
    }

    /**
     * Convierte las opciones al mapa [valor => etiqueta] que usan los selects.
     *
     * Acepta la lista ordenada [['value' => ..., 'label' => ...]] que guarda el admin:
     * un objeto JSON {valor: etiqueta} pierde su orden en la columna JSON de MySQL,
     * que reordena las claves por longitud. El mapa legacy se devuelve tal cual.
     */
    protected function normalizeOptions(array $options): array
    {
        if (!array_is_list($options)) {
            return $options;
        }

        $map = [];
        foreach ($options as $option) {
            if (is_array($option) && array_key_exists('value', $option)) {
                $map[(string) $option['value']] = $option['label'] ?? $option['value'];
            } elseif (is_scalar($option)) {
                $map[(string) $option] = $option;
            }
        }

        return $map;
    }

    /**
     * Construye la sección completa basada en el SystemSection inyectado
     */
    public function build(): ?Field
    {
        if (!$this->section || empty($this->section->type)) {
            return null;
        }

        return $this->compileField([
            'type'        => $this->section->type,
            'key'         => $this->section->key,
            'label'       => $this->section->title,
            'schema'      => $this->section->schema ?? [],
            'is_required' => $this->section->is_required ?? false,
        ]);
    }
}