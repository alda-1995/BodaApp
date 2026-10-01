<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rsvp extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_guest_id',
        'attendance',
        'confirmed_passes',
        'dietary_restrictions',
        'comments',
        'answers',
        'confirmed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'answers' => 'array',
    ];

    public function eventGuest(): BelongsTo
    {
        return $this->belongsTo(EventGuest::class, 'event_guest_id');
    }
}