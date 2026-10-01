<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSection extends Model
{

    use HasFactory;

    protected $fillable = [
        'key',
        'title',
        'order',
        'is_global',
        'is_active',
        'schema',
        'type',
        'parent',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'order'     => 'integer',
        'schema'    => 'array',
    ];

    /**
     * Scope para consultar solo las secciones activas y globales ordenadas.
     */
    // public function scopeActiveGlobal($query)
    // {
    //     return $query->where('is_active', true)
    //                  ->where('is_global', true)
    //                  ->orderBy('order', 'asc');
    // }
}
