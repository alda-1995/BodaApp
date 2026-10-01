<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'password_changed_at' => 'datetime',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole($roleName)
    {
        return $this->roles->contains('name', $roleName);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    }

    /**
     * Dashboard que le corresponde al usuario según su rol.
     */
    public function homeUrl(): string
    {
        if ($this->hasRole('superadmin')) {
            return route('superadmin.dashboard');
        }

        if ($this->hasAnyRole(['organizer', EventCoadmin::ROLE])) {
            return route('panel');
        }

        return route('home');
    }
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Su invitación digital actual: la última que compró (activa o vencida).
     * Sólo puede tener una activa a la vez; al vencer puede comprar otra.
     */
    public function currentEvent(): ?Event
    {
        return $this->latestEvent()->first();
    }

    /**
     * Su invitación más reciente, para listarla junto a otros usuarios.
     *
     * Con hasMany + limit(1) el límite aplica a toda la consulta y sólo se
     * cargaría un evento entre todos los usuarios.
     */
    public function latestEvent(): HasOne
    {
        return $this->hasOne(Event::class)->latestOfMany();
    }

    /** Eventos que administra por invitación de su dueño. */
    public function coadminships(): HasMany
    {
        return $this->hasMany(EventCoadmin::class);
    }
}