<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_number',
        'to_number',
        'wa_id',
        'profile_name',
        'body',
        'message_sid',
        'status',
    ];
}
