<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * URL amigable que un evento tuvo antes. Se conserva para redirigir los enlaces
 * que ya se enviaron a los invitados.
 */
class EventUrlRedirect extends Model
{
    protected $fillable = [
        'event_id',
        'custom_url',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
