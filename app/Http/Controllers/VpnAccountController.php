<?php

namespace App\Http\Controllers;

use App\Models\VpsServer;
use App\Models\VpnAccount;
use App\Models\Transaction;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VpnAccountController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get user's VPN accounts with server info
        $accounts = VpnAccount::with('vpsServer')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('dashboard.accounts', compact('accounts'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $selectedServer = $request->get('server');
        
        // Get available VPS servers
        $vpsServers = VpsServer::where('status', 'online')->orderBy('name')->get();
        
        // Account types with info
        $accountTypes = [
            'ssh' => [
                'name' => 'SSH Tunnel',
                'description' => 'SSH Websocket + SSL/TLS',
                'icon' => 'terminal'
            ],
            'vmess' => [
                'name' => 'VMess',
                'description' => 'V2Ray VMess Protocol',
                'icon' => 'shield-alt'
            ],
            'vless' => [
                'name' => 'VLess', 
                'description' => 'V2Ray VLess Protocol',
                'icon' => 'user-shield'
            ],
            'trojan' => [
                'name' => 'Trojan',
                'description' => 'Trojan-Go Protocol',
                'icon' => 'mask'
            ],
            'shadowsocks' => [
                'name' => 'Shadowsocks',
                'description' => 'Shadowsocks Protocol',
                'icon' => 'eye-slash'
            ],
            'openvpn' => [
                'name' => 'OpenVPN',
                'description' => 'OpenVPN Protocol', 
                'icon' => 'vpn'
            ]
        ];

        $accountPrice = Setting::getAccountPrice();

        return view('dashboard.create-account', compact(
            'user',
            'vpsServers',
            'accountTypes',
            'selectedServer',
            'accountPrice'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'server' => 'required|exists:vps_servers,id',
            'account_type' => 'required|in:ssh,vmess,vless,trojan,shadowsocks,openvpn',
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:50',
            'duration' => 'required|integer|in:30,60,90',
        ]);

        $vpsServer = VpsServer::find($request->server);
        $accountPrice = Setting::getAccountPrice();
        
        // Calculate total price based on duration
        $totalPrice = $accountPrice;
        if ($request->duration == 60) $totalPrice = $accountPrice * 2;
        if ($request->duration == 90) $totalPrice = $accountPrice * 3;

        // Check if server is available
        if (!$vpsServer || $vpsServer->status !== 'online') {
            return back()->withErrors(['server' => 'Server tidak valid atau sedang offline!']);
        }

        // Check user balance
        if ($user->balance < $totalPrice) {
            return back()->withErrors(['balance' => 'Saldo tidak mencukupi! Minimal ' . number_format($totalPrice, 0, ',', '.')]);
        }

        // Check if username already exists on this server
        $existingAccount = VpnAccount::where('username', $request->username)
            ->where('vps_server_id', $vpsServer->id)
            ->where('status', 'active')
            ->first();

        if ($existingAccount) {
            return back()->withErrors(['username' => 'Username sudah digunakan di server ini!']);
        }

        DB::beginTransaction();
        
        try {
            $expiredDate = now()->addDays($request->duration);
            $uuid = Str::uuid()->toString();
            
            // Create VPN account record
            $vpnAccount = VpnAccount::create([
                'user_id' => $user->id,
                'vps_server_id' => $vpsServer->id,
                'account_type' => $request->account_type,
                'username' => $request->username,
                'password' => $request->password,
                'uuid' => $uuid,
                'expired_date' => $expiredDate,
                'status' => 'active'
            ]);

            // Deduct balance
            $user->decrement('balance', $totalPrice);

            // Record transaction
            Transaction::createPurchase(
                $user->id,
                $totalPrice,
                "Pembuatan akun {$request->account_type} - {$request->username} di {$vpsServer->name}",
                $vpnAccount->id
            );

            // Execute command on VPS target
            $result = $this->createVpnAccountOnServer($vpsServer, $request->account_type, $request->username, $request->password, $request->duration, $uuid);

            if ($result['success']) {
                DB::commit();
                return redirect()->route('accounts.index')->with('success', 'Akun VPN berhasil dibuat! Saldo terpotong ' . number_format($totalPrice, 0, ',', '.'));
            } else {
                // Rollback if VPS command failed
                DB::rollback();
                return back()->withErrors(['server' => 'Gagal membuat akun di server: ' . $result['message']]);
            }
            
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function show(VpnAccount $account)
    {
        // Check if user owns this account
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        $account->load('vpsServer');
        
        return view('dashboard.account-detail', compact('account'));
    }

    public function extend(Request $request, VpnAccount $account)
    {
        // Check if user owns this account
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'duration' => 'required|integer|in:30,60,90',
        ]);

        $user = Auth::user();
        $accountPrice = Setting::getAccountPrice();
        
        // Calculate price based on duration
        $totalPrice = $accountPrice;
        if ($request->duration == 60) $totalPrice = $accountPrice * 2;
        if ($request->duration == 90) $totalPrice = $accountPrice * 3;

        // Check user balance
        if ($user->balance < $totalPrice) {
            return back()->withErrors(['balance' => 'Saldo tidak mencukupi! Minimal ' . number_format($totalPrice, 0, ',', '.')]);
        }

        DB::beginTransaction();
        
        try {
            // Extend expiry date
            $newExpiredDate = $account->expired_date->addDays($request->duration);
            $account->update([
                'expired_date' => $newExpiredDate,
                'status' => 'active'
            ]);

            // Deduct balance
            $user->decrement('balance', $totalPrice);

            // Record transaction
            Transaction::createPurchase(
                $user->id,
                $totalPrice,
                "Perpanjangan akun {$account->account_type} - {$account->username}",
                $account->id
            );

            DB::commit();
            
            return back()->with('success', 'Akun berhasil diperpanjang hingga ' . $newExpiredDate->format('d M Y'));
            
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    private function createVpnAccountOnServer($vpsServer, $type, $username, $password, $duration, $uuid)
    {
        $commands = [
            'ssh' => "menu-ssh create {$username} {$password} {$duration}",
            'vmess' => "menu-vmess create {$username} {$uuid} {$duration}",
            'vless' => "menu-vless create {$username} {$uuid} {$duration}",
            'trojan' => "menu-trojan create {$username} {$password} {$duration}",
            'shadowsocks' => "menu-shadowsocks create {$username} {$password} {$duration}",
            'openvpn' => "menu-openvpn create {$username} {$password} {$duration}"
        ];

        if (!isset($commands[$type])) {
            return ['success' => false, 'message' => 'Tipe akun tidak valid'];
        }

        return $vpsServer->executeCommand($commands[$type]);
    }
}
