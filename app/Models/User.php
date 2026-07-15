<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin', 'first_name', 'last_name', 'last_login'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
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
            'last_login' => 'datetime',
            'is_admin' => 'boolean',
            // Passwords may be legacy Django pbkdf2_sha256 hashes; the custom
            // hasher (config/hashing.php) transparently verifies both those and
            // bcrypt, and re-hashes to bcrypt when a password is set.
            'password' => 'hashed',
        ];
    }

    /**
     * Only administrators (Django is_staff / is_superuser) may reach the
     * Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function talksCreated(): HasMany
    {
        return $this->hasMany(Talk::class, 'created_by_id');
    }
}
