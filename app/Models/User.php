<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email', 
        'password',
        'full_name',
        'balance',
        'role',
        'status',
        'reset_token',
        'reset_expires'
    ];

    protected $hidden = [
        'password',
        'reset_token',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'reset_expires' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    // Relationships
    public function vpnAccounts()
    {
        return $this->hasMany(VpnAccount::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function formatBalance()
    {
        return 'Rp ' . number_format($this->balance, 0, ',', '.');
    }

    public function getActiveAccountsCount()
    {
        return $this->vpnAccounts()->where('status', 'active')->count();
    }

    public function getExpiredAccountsCount()
    {
        return $this->vpnAccounts()->where('status', 'expired')->count();
    }
}
