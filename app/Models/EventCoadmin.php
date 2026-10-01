<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Invitación para administrar un evento junto a su dueño. Mientras no se acepte
 * sólo existe el correo; al aceptarla queda ligada a una cuenta.
 */
class EventCoadmin extends Model
{
    public const ROLE = 'coadmin';

    protected $fillable = [
        'event_id',
        'email',
        'user_id',
        'invited_by',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null && $this->user_id !== null;
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->whereNotNull('accepted_at')->whereNotNull('user_id');
    }

    /** El correo se guarda normalizado: así la búsqueda y el índice único coinciden. */
    protected function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = mb_strtolower(trim($value));
    }
}
