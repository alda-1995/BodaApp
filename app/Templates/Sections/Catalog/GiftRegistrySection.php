<?php

namespace App\Templates\Sections\Catalog;

use App\Templates\Sections\Section;

use App\FormBuilder\Controls\TextareaField;
use App\FormBuilder\Controls\TextField;
use App\FormBuilder\Field;
use App\FormBuilder\FormSchemaCompiler;
use App\Models\SystemSection;
use App\Services\SystemSectionService;
use App\Templates\BlockType;

/**
 * Mesa de regalos. Las opciones (cuenta bancaria, tienda, sobre...) las define
 * el superadmin en "Tipos de mesa de regalo", así que las etiquetas de cada
 * dato se leen de ahí en vez de escribirse en la plantilla.
 */
class GiftRegistrySection extends Section
{
    public function __construct(private readonly ?SystemSectionService $sections = null)
    {
    }

    public function key(): string
    {
        return 'gift_registry';
    }

    public function title(): string
    {
        return 'Mesa de Regalos';
    }

    public function blockType(): string
    {
        return BlockType::GIFT_REGISTRY;
    }

    /**
     * Lo que se le pregunta al organizador en este paso.
     *
     * Las opciones de regalo no están escritas aquí: las define el superadmin
     * en "Tipos de mesa de regalo" y este método las compila en un campo más.
     * Lo pide la sección, no la estrategia, y por eso aparecen siempre en el
     * paso correcto y sólo en las plantillas que traen este bloque: agregar un
     * tipo o un dato allá se ve en el wizard sin tocar ninguna plantilla.
     */
    public function fields(): array
    {
        $fields = [
            'section_title' => TextField::make('section_title', 'Título (vista para invitados)')
                ->default('Mesa de regalos')
                ->placeholder('Mesa de regalos')
                ->required(),
            'message' => TextareaField::make('message', 'Mensaje para tus invitados')
                ->default('Tu presencia es nuestro mejor regalo, pero si deseas obsequiarnos algo, aquí tienes algunas opciones.')
                ->placeholder('Escribe un mensaje para tus invitados...')
                ->required(),
        ];

        if ($catalog = $this->registryField()) {
            $fields[$catalog->getName()] = $catalog;
        }

        return $fields;
    }

    /**
     * El catálogo del superadmin, ya compilado como campo del FormBuilder.
     *
     * Devuelve null si todavía no hay catálogo: entonces el paso sólo pide su
     * título y su mensaje, en vez de romperse.
     */
    private function registryField(): ?Field
    {
        $section = $this->sections?->findByKey('registries');

        if (!$section instanceof SystemSection) {
            return null;
        }

        return (new FormSchemaCompiler($section))->build();
    }

    public function data(array $values, array $all): array
    {
        $schema = $this->registrySchema();

        $options = collect($this->rows($values, 'registries'))
            ->map(fn (array $row) => [
                'type' => $this->typeLabel($schema, $row['type'] ?? null),
                // El valor crudo del tipo elegido, además de su etiqueta: la
                // plantilla decide su diseño con él (una cuenta bancaria no se
                // pinta como una tienda) y la etiqueta puede cambiar.
                'type_value' => (string) ($row['type'] ?? ''),
                'details' => $this->details($schema, $row),
            ])
            ->filter(fn (array $option) => filled($option['type']) || $option['details'] !== [])
            ->values()
            ->all();

        return [
            'title' => $this->text($values, 'section_title', 'Mesa de regalos'),
            'message' => $this->text($values, 'message'),
            'options' => $options,
        ];
    }

    /**
     * Los datos de ejemplo salen del catálogo del superadmin, no de aquí.
     *
     * Las opciones de mesa de regalo las define él en "Tipos de mesa de regalo":
     * qué tipos existen, qué se pregunta en cada uno y cuál de esos datos se
     * puede copiar. Inventar aquí una tienda o una cuenta enseñaría en la vista
     * previa algo que esa instalación no ofrece.
     *
     * Cada campo aporta su propio ejemplo desde el placeholder que el
     * superadmin le escribió ("Ej. BBVA" → "BBVA"). Si todavía no configuró
     * ningún tipo, no hay nada que enseñar y la sección no se pinta.
     */
    public function demo(): array
    {
        $schema = $this->registrySchema();

        $options = collect($this->typeOptions($schema))
            ->map(fn (array $type) => [
                'type' => (string) ($type['label'] ?? $type['value'] ?? ''),
                'type_value' => (string) ($type['value'] ?? ''),
                'details' => $this->demoDetails($schema, (string) ($type['value'] ?? '')),
            ])
            ->filter(fn (array $option) => $option['details'] !== [])
            ->values()
            ->all();

        return [
            'title' => 'Mesa de regalos',
            'message' => 'Con tu presencia es suficiente. Si deseas regalarnos algo, aquí nuestras opciones.',
            'options' => $options,
        ];
    }

    public function isVisible(array $data): bool
    {
        return $data['options'] !== [];
    }

    /** @return array<int, array<string, mixed>> */
    private function registrySchema(): array
    {
        $section = $this->sections?->findByKey('registries');

        if (!$section instanceof SystemSection) {
            return [];
        }

        /*
        | Con las claves repetidas ya unidas, igual que al armar el formulario.
        | Si se leyeran en crudo, un dato declarado para dos tipos aparecería
        | dos veces y al buscarlo por clave ganaría el último: el nombre de la
        | tienda de Liverpool se consultaría contra la condición de Amazon y
        | no se pintaría nunca.
        */
        return FormSchemaCompiler::mergeDuplicateKeys((array) ($section->schema ?? []));
    }

    /**
     * Los tipos que declaró el superadmin, tal como los guardó.
     *
     * @return array<int, array<string, mixed>>
     */
    private function typeOptions(array $schema): array
    {
        foreach ($schema as $field) {
            if (($field['key'] ?? null) === 'type') {
                return array_values(array_filter((array) ($field['options'] ?? []), 'is_array'));
            }
        }

        return [];
    }

    /**
     * Ejemplo de una opción: sus campos con el valor de muestra que el propio
     * superadmin escribió como placeholder.
     *
     * @return array<int, array{label: string, value: string, copy: bool}>
     */
    private function demoDetails(array $schema, string $type): array
    {
        $details = [];

        foreach ($schema as $field) {
            $key = (string) ($field['key'] ?? '');
            $dependsOn = $field['depends_on'] ?? null;

            if ($key === '' || $key === 'type') {
                continue;
            }

            // Un campo de otro tipo de mesa no va en este ejemplo.
            if ($dependsOn && ($dependsOn['field'] ?? null) === 'type'
                && !in_array($type, (array) ($dependsOn['values'] ?? []), true)) {
                continue;
            }

            $value = $this->sampleValue($field);

            if ($value === '') {
                continue;
            }

            $details[] = [
                'label' => (string) ($field['label'] ?? $this->humanize($key)),
                'value' => $value,
                // El tipo que declaró el superadmin. La plantilla lo usa para
                // saber qué es cada dato sin tener que adivinarlo del valor.
                'type' => (string) ($field['type'] ?? 'text'),

            ];
        }

        return $details;
    }

    /** "Ej. BBVA" es lo que el superadmin quiere que se vea de ejemplo: "BBVA". */
    private function sampleValue(array $field): string
    {
        $placeholder = trim((string) ($field['placeholder'] ?? ''));

        return (string) preg_replace('/^ej\.?\s*/iu', '', $placeholder);
    }

    private function typeLabel(array $schema, ?string $value): string
    {
        if (!filled($value)) {
            return '';
        }

        foreach ($schema as $field) {
            if (($field['key'] ?? null) !== 'type') {
                continue;
            }

            foreach ($field['options'] ?? [] as $option) {
                if (($option['value'] ?? null) === $value) {
                    return (string) ($option['label'] ?? $value);
                }
            }
        }

        return $this->humanize($value);
    }

    /**
     * Pares etiqueta/valor de la fila, saltando los campos que no aplican a su
     * tipo (un dato de tienda no se muestra en una cuenta bancaria).
     *
     * @return array<int, array{label: string, value: string, copy: bool}>
     */
    private function details(array $schema, array $row): array
    {
        $type = $row['type'] ?? null;
        $labels = collect($schema)->keyBy(fn (array $field) => $field['key'] ?? '');
        $details = [];

        foreach ($row as $key => $value) {
            if ($key === 'type' || !filled($value) || is_array($value)) {
                continue;
            }

            $field = (array) ($labels[$key] ?? []);
            $dependsOn = $field['depends_on'] ?? null;

            if ($dependsOn && ($dependsOn['field'] ?? null) === 'type'
                && !in_array($type, (array) ($dependsOn['values'] ?? []), true)) {
                continue;
            }

            $details[] = [
                'label' => (string) ($field['label'] ?? $this->humanize((string) $key)),
                'value' => $value,
                'type' => (string) ($field['type'] ?? 'text'),

            ];
        }

        return $details;
    }

    /*
    | Aquí vivía isCopyable(). Ya no: si un dato se copia o se enlaza lo decide
    | cada plantilla, con su diseño en la mano. La sección entrega el dato, su
    | etiqueta y el tipo que le puso el superadmin; qué control usar es cosa de
    | quien lo pinta, y cambia de una plantilla a otra.
    */
}