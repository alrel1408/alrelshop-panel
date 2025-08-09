<?php

namespace App\Http\Controllers;

use App\Models\VpsServer;
use App\Models\VpnAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get VPS servers
        $vpsServers = VpsServer::where('status', 'online')->orderBy('name')->get();
        
        // Get user statistics
        $totalAccounts = VpnAccount::where('user_id', $user->id)
            ->where('status', 'active')
            ->count();
            
        $expiredAccounts = VpnAccount::where('user_id', $user->id)
            ->where('status', 'expired')
            ->count();
        
        // Get recent transactions
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Get active accounts with server info
        $activeAccounts = VpnAccount::with('vpsServer')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'user',
            'vpsServers',
            'totalAccounts',
            'expiredAccounts',
            'recentTransactions',
            'activeAccounts'
        ));
    }

    public function profile()
    {
        $user = Auth::user();
        return view('dashboard.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'full_name' => $request->full_name,
            'email' => $request->email,
        ]);

        return back()->with('success', 'Profil berhasil diupdate!');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:6',
            'confirm_password' => 'required|string|same:new_password',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah!']);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Password berhasil diubah!');
    }
}
