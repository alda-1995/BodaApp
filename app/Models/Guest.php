<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
    ];

    public function getWhatsappNumber(): string
    {
        return $this->phone;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): BelongsToMany
    {
       return $this->belongsToMany(Event::class, 'event_guest')
                ->using(EventGuest::class)
                ->withPivot('id', 'uuid', 'max_passes')
                ->withTimestamps();
    }

    /**
     * Envíos (correo, WhatsApp...) que se le han hecho a este invitado.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(GuestNotification::class);
    }

    /**
     * Invitación (pivote event_guest) de este invitado para un evento: guarda su
     * tope de acompañantes y el uuid de su enlace personal.
     */
    public function invitationFor(?Event $event): ?EventGuest
    {
        if (!$event) {
            return null;
        }

        $related = $this->relationLoaded('events')
            ? $this->events->firstWhere('id', $event->id)
            : $this->events()->whereKey($event->id)->first();

        $pivot = $related?->pivot;

        // El enlace del invitado necesita el evento: se reutiliza el que ya tenemos
        // para no hacer una consulta por cada invitado del listado.
        $pivot?->setRelation('event', $event);

        return $pivot;
    }
}