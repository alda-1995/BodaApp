<?php
namespace App\FormBuilder\Controls;

use App\Contracts\ValidatableFieldInterface;
use App\FormBuilder\Field;

class ImageUploadField extends Field implements ValidatableFieldInterface
{
    protected string $component = 'image-upload-field';
    protected int $maxSize = 5120;
    protected array $allowedMimes = ['jpg', 'jpeg', 'png', 'webp'];

    protected function defineType(): string
    {
        return 'image';
    }

    public static function make(string $name, string $label): static
    {
        return new static($name, $label);
    }

    public function maxSize(int $kilobytes): static
    {
        $this->maxSize = $kilobytes;
        return $this;
    }

    public function allowedMimes(array $mimes): static
    {
        $this->allowedMimes = $mimes;
        return $this;
    }

    public function toValidationRules(string $parentKey = ''): array
    {
        $formatKey = function (string $suffix = '') use ($parentKey) {
            $base = $parentKey ? "{$parentKey}.{$this->name}" : $this->name;

            if (!$suffix) {
                return $base;
            }

            return str_contains($base, ']')
                ? preg_replace('/\]$/', "_{$suffix}]", $base)
                : "{$base}_{$suffix}";
        };

        $key = $formatKey();
        $keyUrl = $formatKey('url');
        $keyUuid = $formatKey('uuid');

        $fileRules = ['nullable'];

        if (request()->hasFile($key)) {
            $fileRules = array_merge(
                $fileRules,
                ['file', 'image', 'mimes:' . implode(',', $this->allowedMimes), 'max:' . $this->maxSize],
                ["prohibits:{$keyUrl}"]
            );
        } elseif ($this->isRequired) {
            $fileRules[] = "required_without:{$keyUrl}";
        }

        $urlRules = ['nullable', 'string'];
        if (request()->hasFile($key)) {
            $urlRules[] = "prohibits:{$key}";
        }

        $uuidRules = ['nullable', 'string', 'uuid'];

        if (request()->filled($keyUrl) && !request()->hasFile($key)) {
            $uuidRules[] = "required_with:{$keyUrl}";
        }

        return [
            $key => array_merge($fileRules, $this->formatRules()),
            $keyUrl => $urlRules,
            $keyUuid => $uuidRules,
        ];
    }

    public function toValidationAttributes(string $parentKey = ''): array
    {
        $key = $parentKey ? "{$parentKey}.{$this->name}" : $this->name;
        return [$key => strtolower($this->label)];
    }

    public function toValidationMessages(string $parentKey = ''): array
    {
        $key = $parentKey ? "{$parentKey}.{$this->name}" : $this->name;
        
        $defaults = [
            "{$key}.required" => "Debes adjuntar una imagen para el campo '{$this->label}'.",
            "{$key}.required_without" => "Imagen requerida. Debes subir una nueva imagen o mantener la existente.",
            "{$key}.image" => "El archivo seleccionado en '{$this->label}' debe ser una imagen válida.",
            "{$key}.mimes" => "El formato de '{$this->label}' no es válido. Usa: " . implode(', ', $this->allowedMimes) . ".",
            "{$key}.max" => "La imagen de '{$this->label}' no debe superar los " . round($this->maxSize / 1024, 1) . " MB.",
            "{$key}.prohibits" => "No puedes subir un archivo nuevo y enviar una URL existente al mismo tiempo.",
        ];
        $custom = [];
        foreach ($this->customMessages as $rule => $message) {
            $custom["{$key}.{$rule}"] = $message;
        }

        return array_merge($defaults, $custom);
    }

    /**
     * La imagen llega como ['uuid' => ..., 'url' => ...] desde app_files.
     */
    public function isFilled(mixed $value): bool
    {
        if (is_array($value)) {
            return !empty($value['uuid']) || !empty($value['url']);
        }

        return is_string($value) && trim($value) !== '';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'max_size' => $this->maxSize,
            'allowed_mimes' => $this->allowedMimes,
        ]);
    }
}