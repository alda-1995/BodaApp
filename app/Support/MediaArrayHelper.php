<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MediaArrayHelper
{
    /**
     * Limpia los datos del formulario extrayendo únicamente los valores normales,
     * omitiendo los objetos UploadedFile, sus campos auxiliares (_url, _uuid) y
     * eliminando las claves contenedoras que queden vacías tras la limpieza.
     */
    public static function extractFormValues(array $data): array
    {
        $values = [];

        foreach ($data as $key => $value) {
            // Omitir archivos subidos
            if ($value instanceof UploadedFile) {
                continue;
            }

            // Omitir los auxiliares del componente multimedia (_url y _uuid)
            if (self::isMediaAuxiliaryKey($key, $data)) {
                continue;
            }

            if (is_array($value)) {
                $cleanedArray = self::extractFormValues($value);

                // Solo conservamos la clave si el arreglo procesado contiene valores
                if (!empty($cleanedArray)) {
                    $values[$key] = $cleanedArray;
                }
            } else {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /**
     * Extrae de forma recursiva todos los campos multimedia en un mapa plano indexado por dotPath.
     */
    public static function extractFilesAndMedia(array $data, string $prefix = ''): array
    {
        $files = [];

        foreach ($data as $key => $value) {
            // Los auxiliares _url/_uuid de un campo multimedia se leen de request() después
            if (self::isMediaAuxiliaryKey($key, $data)) {
                continue;
            }

            $currentKey = $prefix ? "{$prefix}.{$key}" : (string) $key;

            if (self::isFileOrMediaReference($value)) {
                $files[$currentKey] = $value;
            } elseif (is_array($value)) {
                // DETECCIÓN: Verificar si el arreglo contiene claves auxiliares (_url o _uuid)
                $mediaKeys = self::detectMediaKeysInArray($value);

                if (!empty($mediaKeys)) {
                    // Reconstruimos la entrada para cada campo multimedia encontrado en este sub-arreglo
                    foreach ($mediaKeys as $baseName) {
                        $fullDotPath = "{$currentKey}.{$baseName}";
                        // Asignamos el UploadedFile si existe en el sub-arreglo, o null (los _url / _uuid se leen de request())
                        $files[$fullDotPath] = $value[$baseName] ?? null;
                    }
                } else {
                    // Es un sub-arreglo normal (ej. iteración de repetidor)
                    $files = array_merge($files, self::extractFilesAndMedia($value, $currentKey));
                }
            } elseif ($value === null) {
                $files[$currentKey] = null;
            }
        }

        /*
        | Un campo multimedia puede llegar SÓLO con sus auxiliares.
        |
        | Cuando el organizador no toca el input de archivo, la petición trae
        | 'foto_url' y 'foto_uuid' pero no 'foto', así que el bucle de arriba se
        | salta los auxiliares y el campo no entra en el mapa. Sin esa entrada,
        | processStepFiles() nunca marca su uuid como activo y la barrida final
        | borra la imagen guardada por creerla omitida: editar un texto dejaba
        | al evento sin su foto.
        |
        | Los sub-arreglos ya lo resuelven al detectar sus claves auxiliares;
        | esto hace lo mismo en este nivel.
        */
        foreach (self::detectMediaKeysInArray($data) as $baseName) {
            $fullDotPath = $prefix ? "{$prefix}.{$baseName}" : $baseName;

            if (!array_key_exists($fullDotPath, $files)) {
                $files[$fullDotPath] = $data[$baseName] ?? null;
            }
        }

        return $files;
    }

    /**
     * Evalúa si un valor representa un archivo o referencia multimedia.
     */
    public static function isFileOrMediaReference(mixed $value): bool
    {
        if ($value instanceof UploadedFile) {
            return true;
        }

        if (is_string($value) && !empty($value)) {
            return Str::isUuid($value) || str_contains($value, '/storage/') || str_contains($value, 'http');
        }

        return false;
    }

    /**
     * Inspecciona un arreglo para identificar claves base que tengan un par _url o _uuid.
     * Ej: ['image_url' => '...', 'image_uuid' => '...'] -> devuelve ['image']
     */
    private static function detectMediaKeysInArray(array $array): array
    {
        $baseKeys = [];

        foreach (array_keys($array) as $k) {
            $base = self::mediaBaseKey($k, $array);
            if ($base !== null) {
                $baseKeys[] = $base;
            }
        }

        return array_values(array_unique($baseKeys));
    }

    /**
     * Indica si una clave es un auxiliar (_url / _uuid) de un campo multimedia.
     *
     * No basta con el sufijo: el admin genera claves con slugify(label), y un campo
     * de texto como "Lista URL" produce 'lista_url'. Un auxiliar verdadero siempre
     * viaja junto a su campo base ('image') o a su pareja ('image_url' <-> 'image_uuid'),
     * porque el componente de imagen envía los tres inputs.
     */
    public static function isMediaAuxiliaryKey(mixed $key, array $siblings): bool
    {
        return self::mediaBaseKey($key, $siblings) !== null;
    }

    /**
     * Devuelve el campo base de un auxiliar multimedia ('image_url' -> 'image'),
     * o null si la clave no es un auxiliar.
     */
    private static function mediaBaseKey(mixed $key, array $siblings): ?string
    {
        if (!is_string($key)) {
            return null;
        }

        foreach (['_url' => '_uuid', '_uuid' => '_url'] as $suffix => $counterpart) {
            if (!str_ends_with($key, $suffix)) {
                continue;
            }

            $base = substr($key, 0, -strlen($suffix));

            return ($base !== '' && (array_key_exists($base, $siblings) || array_key_exists($base . $counterpart, $siblings)))
                ? $base
                : null;
        }

        return null;
    }
}