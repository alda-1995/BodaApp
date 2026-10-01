<?php

namespace App\Templates\Sections;

use App\FormBuilder\Controls\RepeaterField;
use App\FormBuilder\Field;

/**
 * Una sección del catálogo, como la usa una plantilla concreta.
 *
 * El catálogo dice qué se le pregunta a cualquier boda; cada diseño pinta un
 * poco más, un poco menos y en otro orden. En vez de escribir una clase por
 * bloque y por plantilla, el diseño lo declara en su estrategia:
 *
 *     $this->section(GeneralSection::class)
 *         ->add('event_city', TextField::make('event_city', 'Ciudad'))
 *         ->drop('family_parents')
 *         ->first('event_city')
 *         ->example(['event_city' => 'San Miguel de Allende, Gto.'])
 *
 * Lo que se agrega llega solo a la vista: no hay que copiarlo a mano en data(),
 * que era de donde salían la mitad de los olvidos.
 *
 * Esto es para DECLARAR. Si una plantilla necesita comportamiento —transformar
 * un valor guardado, decidir si su bloque se pinta, calcular un ejemplo— eso es
 * una clase de sección propia, y está bien que lo sea.
 */
final class TemplateSection extends Section
{
    /** @var array<string, Field> campos que agrega este diseño */
    private array $added = [];

    /** @var array<int, string> campos del catálogo que este diseño no pinta */
    private array $dropped = [];

    /** @var array<int, string> campos que van al principio del paso */
    private array $first = [];

    /** @var array<string, mixed> valores para la vista previa */
    private array $example = [];

    /** @var array<string, array<string, mixed>> imágenes que repone el superadmin */
    private array $assets = [];

    private ?string $blade = null;

    public function __construct(private readonly Section $section)
    {
    }

    /* =====================================================================
     | Lo que declara el diseño
     * ===================================================================*/

    /** Un campo que pide este diseño y el catálogo no. */
    public function add(string $name, Field $field): self
    {
        $this->added[$name] = $field;

        return $this;
    }

    /**
     * Campos del catálogo que este diseño no pinta, y por lo tanto no pregunta.
     *
     * Un nombre a secas quita un campo del paso ('message'). Con punto entra a
     * un repetidor y quita uno de sus subcampos ('hotels.url'): un diseño puede
     * usar la lista de hoteles pero no su liga de reservación.
     */
    public function drop(string ...$names): self
    {
        $this->dropped = array_merge($this->dropped, $names);

        return $this;
    }

    /**
     * Campos que van al principio del paso.
     *
     * Sólo se nombran los que cambian de lugar; los demás quedan después tal
     * como venían, para no tener que listarlos todos.
     */
    public function first(string ...$names): self
    {
        $this->first = array_merge($this->first, $names);

        return $this;
    }

    /**
     * Valores para la vista previa del catálogo, donde no hay evento.
     *
     * Las imágenes se escriben con asset() a la vista: aquí no se adivina si un
     * texto parece una ruta.
     *
     * @param  array<string, mixed>  $values
     */
    public function example(array $values): self
    {
        $this->example = array_merge($this->example, $values);

        return $this;
    }

    /**
     * Una imagen de la plantilla que el superadmin puede reponer para una boda.
     *
     * No es dato de la pareja sino dibujo del diseño —un mapa ilustrado, la
     * secuencia de una animación—, así que no se pide en el wizard. $extra
     * lleva 'help' y, para una secuencia, 'kind', 'count' y 'extension'.
     *
     * @param  array<string, mixed>  $extra
     */
    public function asset(string $key, string $label, string $default, array $extra = []): self
    {
        $this->assets[$key] = $extra + ['label' => $label, 'default' => $default];

        return $this;
    }

    /**
     * El Blade que pinta el bloque, cuando no se llama como su tipo.
     *
     * Así 'itinerary.blade.php' sirve al tipo 'timeline' sin que las demás
     * plantillas se enteren.
     */
    public function blade(string $file): self
    {
        $this->blade = $file;

        return $this;
    }

    /* =====================================================================
     | Lo que ve el resto del sistema
     * ===================================================================*/

    public function key(): string
    {
        return $this->section->key();
    }

    public function title(): string
    {
        return $this->section->title();
    }

    public function blockType(): string
    {
        return $this->section->blockType();
    }

    public function componentFile(): ?string
    {
        return $this->blade;
    }

    public function modelAttributes(): array
    {
        return $this->section->modelAttributes();
    }

    public function isVisible(array $data): bool
    {
        return $this->section->isVisible($data);
    }

    public function templateAssets(): array
    {
        return $this->assets;
    }

    /** Los del catálogo más los del diseño, sin los que no pinta y en su orden. */
    public function fields(): array
    {
        $fields = array_merge($this->section->fields(), $this->added);

        foreach ($this->dropped as $name) {
            if (!str_contains($name, '.')) {
                unset($fields[$name]);

                continue;
            }

            [$repetidor, $subcampo] = explode('.', $name, 2);

            if (($campo = $fields[$repetidor] ?? null) instanceof RepeaterField) {
                $campo->schema(array_values(array_filter(
                    $campo->getSchema(),
                    fn (Field $sub) => $sub->getName() !== $subcampo,
                )));
            }
        }

        $ordenados = [];

        foreach ($this->first as $name) {
            if (array_key_exists($name, $fields)) {
                $ordenados[$name] = $fields[$name];
            }
        }

        return $ordenados + $fields;
    }

    /**
     * Lo guardado, listo para la vista.
     *
     * La sección arma lo suyo y los campos del diseño se agregan solos: por eso
     * declararlos arriba basta, sin copiarlos aquí uno por uno.
     */
    public function data(array $values, array $all): array
    {
        $data = $this->section->data($values, $all);

        foreach (array_keys($this->added) as $name) {
            $data[$name] = $this->imageUrl($values[$name] ?? null) ?? ($values[$name] ?? null);
        }

        return $data;
    }

    public function demo(): array
    {
        return array_merge($this->section->demo(), $this->assetDefaults(), $this->example);
    }
}
