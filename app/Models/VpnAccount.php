<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VpnAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vps_server_id',
        'account_type',
        'username',
        'password',
        'uuid',
        'port',
        'path',
        'domain',
        'expired_date',
        'status'
    ];

    protected $casts = [
        'expired_date' => 'date',
        'port' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vpsServer()
    {
        return $this->belongsTo(VpsServer::class);
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active' && $this->expired_date->isFuture();
    }

    public function isExpired()
    {
        return $this->expired_date->isPast();
    }

    public function getDaysLeft()
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return $this->expired_date->diffInDays(now());
    }

    public function formatExpiredDate()
    {
        return $this->expired_date->format('d M Y');
    }

    public function getAccountTypeIcon()
    {
        $icons = [
            'ssh' => 'terminal',
            'vmess' => 'shield-alt',
            'vless' => 'user-shield',
            'trojan' => 'mask',
            'shadowsocks' => 'eye-slash',
            'openvpn' => 'vpn'
        ];

        return $icons[$this->account_type] ?? 'globe';
    }

    public function getAccountTypeName()
    {
        $names = [
            'ssh' => 'SSH Tunnel',
            'vmess' => 'VMess',
            'vless' => 'VLess',
            'trojan' => 'Trojan',
            'shadowsocks' => 'Shadowsocks',
            'openvpn' => 'OpenVPN'
        ];

        return $names[$this->account_type] ?? $this->account_type;
    }

    // Scope queries
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('expired_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')->orWhere('expired_date', '<=', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    // Auto-update expired accounts
    public static function updateExpiredAccounts()
    {
        return static::where('status', 'active')
            ->where('expired_date', '<=', now())
            ->update(['status' => 'expired']);
    }
}
