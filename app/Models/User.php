<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function frontendProfile(): array
    {
        $labels = [
            'administrator' => 'Administrador',
            'consultant' => 'Consultor',
            'auditor' => 'Consulta / auditoría',
        ];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'roleLabel' => $labels[$this->role] ?? $this->role,
            'status' => $this->is_active ? 'Activo' : 'Inactivo',
            'permissions' => [
                'createMinutes' => in_array($this->role, ['administrator', 'consultant'], true),
                'manageUsers' => $this->role === 'administrator',
                'viewAudit' => in_array($this->role, ['administrator', 'auditor'], true),
                'viewConfiguration' => $this->role === 'administrator',
            ],
        ];
    }
}
