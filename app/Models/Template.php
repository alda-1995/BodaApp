<?php

namespace App\Models;

use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'view_path',
        'description',
        'stripe_price_id',
        'price',
        'is_active',
        'admin_fields',
        'duration_days',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'admin_fields' => 'array',
        'duration_days' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Template $template) {
            $template->slug = Str::slug($template->name);

            $originalSlug = $template->slug;
            $count = 1;

            while (static::where('slug', $template->slug)->withTrashed()->exists()) {
                $template->slug = "{$originalSlug}-{$count}";
                $count++;
            }
        });
    }

    /** Sección y campo con que se guarda su imagen de presentación. */
    public const PRESENTATION_SECTION = 'presentation';
    public const PREVIEW_IMAGE_FIELD = 'preview_image';

    /** Sus archivos, como los del wizard: el servicio de archivos los maneja igual. */
    public function files(): MorphMany
    {
        return $this->morphMany(AppFile::class, 'fileable')->orderBy('sort_order');
    }

    /** El registro de su imagen de presentación, si subieron una. */
    public function previewImage(): ?AppFile
    {
        return $this->files
            ->firstWhere('field_name', self::PREVIEW_IMAGE_FIELD);
    }

    /**
     * La imagen con la que se presenta, lista para un src.
     *
     * Null si no subieron ninguna: quien la pinta decide qué poner en su lugar,
     * porque la landing y el resumen de compra no se ven igual.
     */
    public function previewImageUrl(): ?string
    {
        return $this->previewImage()?->url;
    }

    public function getAvailableFeaturesAttribute(): array
    {
        return config("templates.{$this->slug}.required_features", []);
    }

    protected static function newFactory()
    {
        return TemplateFactory::new();
    }
}
