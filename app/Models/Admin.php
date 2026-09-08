<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
        'role',
        'is_active',
        'two_factor_enabled',
        'two_factor_secret',
        'last_login_at',
        'last_login_ip',
        'nav_order',
        'table_prefs',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'nav_order' => 'array',
        'table_prefs' => 'array',
    ];

    /** Columns this admin has hidden on a given table. */
    public function hiddenColumns(string $table): array
    {
        return (array) (($this->table_prefs ?? [])[$table] ?? []);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(AdminPermission::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(AdminLoginLog::class);
    }

    // Convenience helper: is this admin a super admin?
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    // Check whether the admin has a given capability on a module.
    public function hasPermission(string $module, string $ability = 'read'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permission = $this->permissions->firstWhere('module', $module);

        return $permission ? (bool) $permission->{'can_' . $ability} : false;
    }
}
