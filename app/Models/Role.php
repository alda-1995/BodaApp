<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'display_name'];

    /**
     * Rol por nombre. Los roles existen desde el seeder: si falta, es un error
     * de instalación y debe verse, no pasar de largo dejando al usuario sin rol.
     */
    public static function named(string $name): self
    {
        return static::where('name', $name)->firstOrFail();
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
