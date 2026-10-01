<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Str;

class AppFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'fileable_type',
        'fileable_id',
        'section',
        'field_name',
        'original_name',
        'file_path',
        'disk',
        'mime_type',
        'file_type',
        'file_size',
        'sort_order',
        'meta_data',
        'uuid'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'meta_data' => 'array',
    ];

    protected $appends = ['url'];

    /**
     * Relación polimórfica inversa.
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Accesor para la URL pública del archivo.
     */
    public function getUrlAttribute(): ?string
    {
        if (empty($this->file_path)) {
            return null;
        }

        if (filter_var($this->file_path, FILTER_VALIDATE_URL)) {
            return $this->file_path;
        }

        $disk = $this->disk ?: 'public';

        // ltrim elimina cualquier "/" al inicio del path guardado en la DB
        $relativePath = ltrim($this->file_path, '/');

        return Storage::disk($disk)->url($relativePath);
    }

    /**
     * Helper para detectar el tipo general del archivo según el MimeType.
     */
    public static function detectFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }
        if ($mimeType === 'application/pdf') {
            return 'pdf';
        }

        return 'document';
    }

    protected static function booted(): void
    {
        static::creating(function ($appFile) {
            if (empty($appFile->uuid)) {
                $appFile->uuid = (string) Str::uuid();
            }
        });
    }
}