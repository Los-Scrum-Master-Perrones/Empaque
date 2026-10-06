<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
use HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'photo',
        'is_active',
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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function esSoloSupervisor(): bool
    {
        if ($this->hasAnyRole(['SuperAdmin', 'Admin', 'Digitalizador', 'Operador'])) {
            return false;
        }

        if ($this->hasRole('Supervisor')) {
            return true;
        }

        $nombre = mb_strtolower(trim((string) $this->name));
        $email = mb_strtolower(trim((string) $this->email));

        return str_contains($nombre, 'supervisor')
            || str_starts_with($email, 'supervisor');
    }
}
