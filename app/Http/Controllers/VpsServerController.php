<?php

namespace App\Http\Controllers;

use App\Models\VpsServer;
use App\Models\VpnAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VpsServerController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isAdmin()) {
                abort(403, 'Access denied. Admin only.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $vpsServers = VpsServer::withCount(['vpnAccounts as active_accounts_count' => function ($query) {
            $query->where('status', 'active');
        }])->orderBy('created_at', 'desc')->get();

        return view('admin.vps-servers', compact('vpsServers'));
    }

    public function store(Request $request)
    {
        if ($request->action === 'auto_detect') {
            return $this->autoDetect($request);
        }

        $request->validate([
            'server_key' => 'required|string|max:50|unique:vps_servers,server_key',
            'name' => 'required|string|max:100',
            'hostname' => 'nullable|string',
            'ip_address' => 'required|ip',
            'location' => 'nullable|string|max:100',
            'provider' => 'nullable|string|max:50',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_user' => 'nullable|string|max:50',
        ]);

        VpsServer::create([
            'server_key' => $request->server_key,
            'name' => $request->name,
            'hostname' => $request->hostname,
            'ip_address' => $request->ip_address,
            'location' => $request->location ?: 'Unknown',
            'provider' => $request->provider ?: 'Unknown',
            'ssh_port' => $request->ssh_port ?: 22,
            'ssh_user' => $request->ssh_user ?: 'root',
            'ssh_key_path' => '/var/www/.ssh/alrelshop_panel',
            'status' => 'online'
        ]);

        return back()->with('success', 'VPS berhasil ditambahkan!');
    }

    public function update(Request $request, VpsServer $vpsServer)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'hostname' => 'nullable|string',
            'location' => 'nullable|string|max:100',
            'provider' => 'nullable|string|max:50',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_user' => 'nullable|string|max:50',
            'status' => 'required|in:online,offline,maintenance',
        ]);

        $vpsServer->update([
            'name' => $request->name,
            'hostname' => $request->hostname,
            'location' => $request->location,
            'provider' => $request->provider,
            'ssh_port' => $request->ssh_port ?: 22,
            'ssh_user' => $request->ssh_user ?: 'root',
            'status' => $request->status,
        ]);

        return back()->with('success', 'VPS berhasil diupdate!');
    }

    public function destroy(VpsServer $vpsServer)
    {
        // Check if VPS has active accounts
        $activeAccountsCount = $vpsServer->vpnAccounts()->where('status', 'active')->count();
        
        if ($activeAccountsCount > 0) {
            return back()->withErrors(['error' => "Tidak dapat menghapus VPS! Masih ada {$activeAccountsCount} akun aktif."]);
        }

        $vpsServer->delete();
        
        return back()->with('success', 'VPS berhasil dihapus!');
    }

    public function testConnection(VpsServer $vpsServer)
    {
        $result = $vpsServer->testConnection();

        if ($result['success']) {
            return back()->with('success', "Koneksi ke {$vpsServer->name} berhasil! Response: " . $result['output']);
        } else {
            return back()->withErrors(['error' => "Koneksi ke {$vpsServer->name} gagal! Error: " . $result['output']]);
        }
    }

    private function autoDetect(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
        ]);

        $ipAddress = $request->ip_address;
        $sshKey = '/var/www/.ssh/alrelshop_panel';
        
        // Test SSH connection
        $testCommand = "ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no -i {$sshKey} root@{$ipAddress} 'echo \"CONNECTION_OK\"' 2>&1";
        exec($testCommand, $output, $returnVar);
        
        if ($returnVar !== 0) {
            return back()->withErrors(['ip_address' => 'SSH connection failed: ' . implode("\n", $output)]);
        }

        // Get system information
        $commands = [
            'hostname' => 'hostname',
            'os_info' => 'cat /etc/os-release | grep PRETTY_NAME | cut -d= -f2 | tr -d \'"\'',
            'location' => 'curl -s ipinfo.io/country 2>/dev/null || echo "Unknown"',
            'provider' => 'curl -s ipinfo.io/org 2>/dev/null | cut -d\' \' -f2- || echo "Unknown"',
        ];

        $detectedInfo = [];
        
        foreach ($commands as $key => $command) {
            $cmd = "ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no -i {$sshKey} root@{$ipAddress} '{$command}' 2>/dev/null";
            $result = trim(shell_exec($cmd));
            $detectedInfo[$key] = $result ?: 'Unknown';
        }

        // Generate server key
        $serverKey = 'vps_' . substr(md5($ipAddress . time()), 0, 8);

        // Map country code to location
        $locationMap = [
            'US' => 'United States',
            'SG' => 'Singapore',
            'ID' => 'Indonesia',
            'JP' => 'Japan',
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'NL' => 'Netherlands'
        ];

        $location = $locationMap[$detectedInfo['location']] ?? $detectedInfo['location'];

        // Determine provider from org info
        $providerMap = [
            'digitalocean' => 'DigitalOcean',
            'vultr' => 'Vultr',
            'linode' => 'Linode',
            'amazon' => 'AWS',
            'google' => 'Google Cloud',
            'microsoft' => 'Azure'
        ];

        $provider = 'Unknown';
        foreach ($providerMap as $key => $value) {
            if (stripos($detectedInfo['provider'], $key) !== false) {
                $provider = $value;
                break;
            }
        }

        $autoDetectedData = [
            'server_key' => $serverKey,
            'name' => strtoupper(str_replace(['-', '.'], ' ', $detectedInfo['hostname'])) . ' SERVER',
            'hostname' => $detectedInfo['hostname'],
            'ip_address' => $ipAddress,
            'location' => $location,
            'provider' => $provider,
        ];

        return back()->with(['success' => 'Info VPS berhasil dideteksi!', 'detected_data' => $autoDetectedData]);
    }
}
