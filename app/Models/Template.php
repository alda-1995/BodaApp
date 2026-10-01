<?php

namespace App\Models;

use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'view_path',
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

    public function getAvailableFeaturesAttribute(): array
    {
        return config("templates.{$this->slug}.required_features", []);
    }

    protected static function newFactory()
    {
        return TemplateFactory::new();
    }
}
