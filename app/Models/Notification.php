<?php

namespace App\Models;

use App\Notifications\MessageTemplate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un envío masivo: el mensaje que el organizador mandó por un medio, con una
 * fila de seguimiento (GuestNotification) por cada invitado.
 */
class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'title',
        'message',
        'channel',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(GuestNotification::class);
    }

    public function template(): MessageTemplate
    {
        return new MessageTemplate($this->title, $this->message);
    }
}
