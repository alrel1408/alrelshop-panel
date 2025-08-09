<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpsServer extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_key',
        'name',
        'hostname',
        'ip_address',
        'location',
        'provider',
        'ssh_port',
        'ssh_user',
        'ssh_key_path',
        'status',
        'max_accounts',
        'current_accounts'
    ];

    protected $casts = [
        'ssh_port' => 'integer',
        'max_accounts' => 'integer',
        'current_accounts' => 'integer',
    ];

    // Relationships
    public function vpnAccounts()
    {
        return $this->hasMany(VpnAccount::class);
    }

    // Helper methods
    public function isOnline()
    {
        return $this->status === 'online';
    }

    public function getActiveAccountsCount()
    {
        return $this->vpnAccounts()->where('status', 'active')->count();
    }

    public function canCreateAccount()
    {
        return $this->isOnline() && $this->getActiveAccountsCount() < $this->max_accounts;
    }

    public function testConnection()
    {
        $ssh_command = "ssh -o ConnectTimeout=30 -o StrictHostKeyChecking=no -i {$this->ssh_key_path} {$this->ssh_user}@{$this->ip_address} -p {$this->ssh_port} 'echo \"Connection test successful\"' 2>&1";
        
        $output = [];
        $return_var = 0;
        exec($ssh_command, $output, $return_var);
        
        return [
            'success' => $return_var === 0,
            'output' => implode("\n", $output),
            'return_code' => $return_var
        ];
    }

    public function executeCommand($command)
    {
        $ssh_command = "ssh -o ConnectTimeout=30 -o StrictHostKeyChecking=no -i {$this->ssh_key_path} {$this->ssh_user}@{$this->ip_address} -p {$this->ssh_port} '{$command}' 2>&1";
        
        $output = [];
        $return_var = 0;
        exec($ssh_command, $output, $return_var);
        
        return [
            'success' => $return_var === 0,
            'output' => implode("\n", $output),
            'return_code' => $return_var
        ];
    }
}
