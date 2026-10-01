<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Cviebrock\EloquentSluggable\Sluggable;
use Str;

class Event extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'user_id',
        'template_id',
        'order_id',
        'title',
        'slug',
        'custom_url',
        'url_partner_1',
        'url_partner_2',
        'event_date',
        'expires_at',
        'is_active',
        'features',
        'template_assets',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'features' => 'array',
        'template_assets' => 'array',
    ];

    /**
     * Configuración de eloquent-sluggable para la URL amigable (custom_url).
     *
     * El slug se genera a mano con EventService::uniqueCustomUrl() a partir de los
     * nombres de la pareja. La generación automática del trait está cancelada en
     * booted(): ver el comentario ahí.
     */
    public function sluggable(): array
    {
        return [
            'custom_url' => [
                'source' => null,
                'unique' => true,
                'separator' => '-',
                'maxLength' => 80,
                'onUpdate' => false,
            ],
        ];
    }

    /**
     * URL pública y amigable de la invitación digital, ej. /invitacion/ana-y-luis.
     * Mientras el evento no tenga slug amigable, usa su identificador interno.
     */
    public function invitationUrl(): string
    {
        return static::invitationUrlFor($this->custom_url ?: $this->slug);
    }

    /**
     * Único lugar donde vive el formato de la URL pública de la invitación.
     */
    public static function invitationUrlFor(string $slug): string
    {
        return url('/invitacion/' . $slug);
    }

    public function getFeature(string $key, $default = null)
    {
        return $this->features[$key] ?? $default;
    }

    public function hasFeatures(): bool
    {
        if (is_null($this->features) || !is_array($this->features)) {
            return false;
        }
        
        return count(array_filter($this->features)) > 0;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function guests(): BelongsToMany
    {
        return $this->belongsToMany(Guest::class, 'event_guest')
            ->using(EventGuest::class)
            ->withPivot('id', 'uuid', 'max_passes')
            ->withTimestamps();
    }

    /** URLs amigables anteriores: redirigen a la actual. */
    public function urlRedirects(): HasMany
    {
        return $this->hasMany(EventUrlRedirect::class);
    }

    public function coadmins(): HasMany
    {
        return $this->hasMany(EventCoadmin::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    /**
     * Dueño o coadministrador que ya aceptó la invitación.
     */
    public function isManagedBy(User $user): bool
    {
        return $this->isOwnedBy($user)
            || $this->coadmins()->accepted()->where('user_id', $user->id)->exists();
    }

    /**
     * Días que la invitación sigue activa después del evento: los de la
     * plantilla o, si no define, los de config/events.php.
     */
    public function durationDays(): int
    {
        $template = $this->template ?? Template::find($this->template_id);

        return (int) ($template?->duration_days ?: config('events.default_duration_days'));
    }

    /** Estados que el superadmin ve en la lista de usuarios. */
    public const STATUS_PENDING_DATE = 'pending_date';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRING = 'expiring';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUSPENDED = 'suspended';

    /** Días antes del vencimiento en los que se considera "por vencer". */
    public const EXPIRING_SOON_DAYS = 30;

    public function statusKey(): string
    {
        return match (true) {
            !$this->is_active => self::STATUS_SUSPENDED,
            $this->isExpired() => self::STATUS_EXPIRED,
            // Sin fecha de boda no hay vigencia que contar.
            $this->event_date === null => self::STATUS_PENDING_DATE,
            $this->expires_at?->lte(now()->addDays(self::EXPIRING_SOON_DAYS)) => self::STATUS_EXPIRING,
            default => self::STATUS_ACTIVE,
        };
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Se puede usar y editar: activa y dentro de su vigencia. No depende de que
     * el comando programado ya la haya deshabilitado.
     */
    public function isAvailable(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /**
     * Nombres de la pareja tal como los verá el invitado en la plantilla (paso
     * "general" del wizard). Pueden llevar apellidos.
     */
    public function coupleNames(): array
    {
        return [
            $this->features['general']['name_wife'] ?? '',
            $this->features['general']['name_husband'] ?? '',
        ];
    }

    /** Nombres cortos con los que se arma la URL amigable. */
    public function urlNames(): array
    {
        return [(string) $this->url_partner_1, (string) $this->url_partner_2];
    }

    /**
     * Nombres para mostrar en el panel: los de la plantilla y, si aún no los
     * capturan, los cortos de la URL.
     *
     * @return array<int, string>
     */
    public function displayNames(): array
    {
        return array_values(array_filter($this->coupleNames()) ?: array_filter($this->urlNames()));
    }

    public function files(): MorphMany
    {
        return $this->morphMany(AppFile::class, 'fileable')->orderBy('sort_order');
    }

    public function getFilesBySection(string $section): MorphMany
    {
        return $this->files()->where('section', $section);
    }

    public function colorPalette(): BelongsTo
    {
        return $this->belongsTo(ColorPalette::class, 'features->theme->palette_id');
    }

    public function getThemeColorsAttribute(): array
    {
        $theme = $this->getFeature('theme', []);

        return [
            'primary' => $theme['primary_color'] ?? '#1E1E1E',
            'secondary' => $theme['secondary_color'] ?? '#F5E9DC',
            'accent' => $theme['accent_color'] ?? '#FFFFFF',
            'is_custom' => $theme['is_custom'] ?? false,
        ];
    }

    public function applyColorPalette(ColorPalette $palette): void
    {
        $features = $this->features ?? [];

        $features['theme'] = [
            'palette_id' => $palette->id,
            'primary_color' => $palette->primary_color,
            'secondary_color' => $palette->secondary_color,
            'accent_color' => $palette->accent_color,
            'is_custom' => false,
        ];

        $this->features = $features;
        $this->save();
    }

    protected static function booted(): void
    {
        // El trait Sluggable se usa sólo por SlugService::createSlug() (lo necesita para
        // buscar duplicados). Su generación automática se cancela: se dispara en cada
        // guardado mientras custom_url esté vacío, y sin 'source' tomaría el JSON del
        // evento como texto. Los eventos se crean en el checkout, sin nombres aún.
        static::slugging(fn () => false);

        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                $event->slug = (string) Str::uuid();
            }
        });

        static::saving(function (Event $event) {
            // La vigencia se cuenta desde la fecha de la boda; sin fecha no corre.
            if ($event->isDirty('event_date')) {
                $event->expires_at = $event->event_date?->copy()->addDays($event->durationDays());
            }
        });
    }
}