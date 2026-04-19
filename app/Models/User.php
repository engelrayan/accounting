<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // ── Role constants ────────────────────────────────────────────────────────
    const ROLE_ADMIN      = 'admin';
    const ROLE_ACCOUNTANT = 'accountant';
    const ROLE_VIEWER     = 'viewer';

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── Role helpers ──────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAccountant(): bool
    {
        return $this->role === self::ROLE_ACCOUNTANT;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    /**
     * Admin and accountant may create / modify records.
     * Viewer is read-only.
     */
    public function canWrite(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_ACCOUNTANT], true);
    }

    /** Arabic role label shown in UI. */
    public function roleName(): string
    {
        return match ($this->role ?? self::ROLE_ACCOUNTANT) {
            self::ROLE_ADMIN      => 'مدير',
            self::ROLE_ACCOUNTANT => 'محاسب',
            self::ROLE_VIEWER     => 'مشاهد',
            default               => 'محاسب',
        };
    }

    /** CSS modifier for the role badge (.ac-role-badge--{class}). */
    public function roleClass(): string
    {
        return match ($this->role ?? self::ROLE_ACCOUNTANT) {
            self::ROLE_ADMIN      => 'admin',
            self::ROLE_ACCOUNTANT => 'accountant',
            self::ROLE_VIEWER     => 'viewer',
            default               => 'accountant',
        };
    }
}
