<?php

namespace App\Templates\Sections;

use App\Models\Event;
use Illuminate\Support\Str;

/**
 * Una sección del catálogo: lo que se le pregunta al organizador, cómo se lee
 * lo guardado y a qué tipo de bloque corresponde.
 *
 * Cada plantilla arma su lista de secciones; el wizard, el avance, la vista
 * previa y la invitación pública salen todos de aquí.
 */
abstract class Section
{
    /** Llave del paso del wizard y de features: "general", "itinerary"... */
    abstract public function key(): string;

    /** Título del paso en el wizard. */
    abstract public function title(): string;

    /** Tipo de bloque con el que se busca la vista en la plantilla. */
    abstract public function blockType(): string;

    /**
     * Campos del FormBuilder que se preguntan en el wizard. Vacío para secciones
     * que la plantilla trae fijas (por ejemplo, un bloque decorativo).
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Campos del paso que viven en columnas de events en vez de en features.
     *
     * @return array<string, string> campo => columna
     */
    public function modelAttributes(): array
    {
        return [];
    }

    /**
     * Reglas que no se pueden declarar en el campo porque dependen de la boda.
     *
     * Un campo se declara una vez y sirve para cualquier evento, así que no
     * puede saber, por ejemplo, qué día se casa esta pareja. Lo que sí necesita
     * ese dato se pide aquí, donde ya hay un Event, y se suma a las reglas del
     * paso en vez de reemplazarlas.
     *
     * @return array<string, array<int, string>> campo => reglas extra
     */
    public function rulesFor(Event $event): array
    {
        return [];
    }

    /**
     * Ajusta lo propio cuando otro paso cambió algo de lo que depende.
     *
     * La validación sólo alcanza al paso que se está guardando, así que no ve
     * lo que ese cambio descuadra en otro: mover la boda a antes de la fecha
     * límite para confirmar deja un límite imposible, y bloquear el cambio
     * sería peor —le impediría al organizador corregir la fecha de su boda—.
     *
     * Aquí la sección acomoda sus propios datos sobre $event (sin guardarlo: de
     * eso se encarga quien guarda el paso) y devuelve lo que hay que avisarle
     * al organizador, o null si no hubo nada que tocar.
     */
    public function reconcile(Event $event, string $savedStep): ?string
    {
        return null;
    }

    /**
     * Cómo se llama el Blade que pinta esta sección, cuando no se llama como su
     * tipo de bloque.
     *
     * Por convención el archivo lleva el nombre del tipo
     * (blocks/{tipo}.blade.php). Una plantilla puede nombrarlo distinto —el
     * itinerario de "Boda Editorial" es blocks/itinerary.blade.php aunque su
     * tipo siga siendo 'timeline'— y entonces lo dice aquí.
     */
    public function componentFile(): ?string
    {
        return null;
    }

    /**
     * Las imágenes de la plantilla que el superadmin puede reemplazar para una
     * boda concreta.
     *
     * No son datos de la pareja sino dibujo del diseño —un monograma, un mapa
     * ilustrado, la secuencia de una animación—, así que no se piden en el
     * wizard: se cambian desde el panel y vienen con el valor del diseño
     * original. De aquí sale esa pantalla, y una sección sin imágenes propias
     * no aparece en ella.
     *
     * @return array<string, array{label: string, help?: string, kind?: string, count?: int, extension?: string, default: string}>
     */
    public function templateAssets(): array
    {
        return [];
    }

    /** Esas imágenes tal como se ven si nadie las reemplaza, ya resueltas a URL. */
    public function assetDefaults(): array
    {
        return collect($this->templateAssets())
            ->map(fn (array $asset) => asset($asset['default']))
            ->all();
    }

    /**
     * Datos listos para el bloque, a partir de lo guardado.
     *
     * @param array $values valores del paso (features + columnas + archivos)
     * @param array $all    todos los pasos, para las secciones que se apoyan en otra
     */
    abstract public function data(array $values, array $all): array;

    /** Los mismos datos, de ejemplo, para la vista previa de la plantilla. */
    abstract public function demo(): array;

    /** Una sección sin contenido no se pinta. */
    public function isVisible(array $data): bool
    {
        return true;
    }

    /** Grupo tal como lo espera el wizard. */
    public function toWizardGroup(): array
    {
        return [
            'title' => $this->title(),
            'fields' => $this->fields(),
        ];
    }

    /** Lista guardada por un repeater: siempre un array de filas. */
    protected function rows(array $values, string $key): array
    {
        $rows = $values[$key] ?? [];

        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    /** URL de una imagen guardada con ImageUploadField. */
    protected function imageUrl(mixed $value): ?string
    {
        if (is_array($value)) {
            return $value['url'] ?? null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function text(array $values, string $key, ?string $default = null): ?string
    {
        $value = $values[$key] ?? null;

        return filled($value) ? (string) $value : $default;
    }

    protected function boolean(array $values, string $key, bool $default = false): bool
    {
        return isset($values[$key]) ? filter_var($values[$key], FILTER_VALIDATE_BOOLEAN) : $default;
    }

    protected function humanize(string $key): string
    {
        return Str::headline($key);
    }
}
