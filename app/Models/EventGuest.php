<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventGuest extends Pivot
{
    use HasFactory;

    public $incrementing = true;

    protected $table = 'event_guest';

    protected $fillable = [
        'uuid',
        'max_passes',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function rsvp(): HasOne
    {
        return $this->hasOne(Rsvp::class, 'event_guest_id');
    }

    /**
     * Enlace personal del invitado: la URL amigable del evento más su código
     * propio, ej. /invitacion/ana-y-luis/{uuid}. El código identifica al
     * invitado sin que su enlace se pueda adivinar.
     */
    public function invitationUrl(): string
    {
        return $this->event->invitationUrl() . '/' . $this->uuid;
    }
}
