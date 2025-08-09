<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('username', 'password');
        
        // Try to login with username or email
        $loginField = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        if (Auth::attempt([$loginField => $credentials['username'], 'password' => $credentials['password']])) {
            $user = Auth::user();
            
            // Check if user is active
            if ($user->status !== 'active') {
                Auth::logout();
                return back()->withErrors(['username' => 'Akun Anda telah dinonaktifkan.']);
            }
            
            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        return back()->withErrors(['username' => 'Username/email atau password salah!']);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reg_username' => 'required|string|max:50|unique:users,username',
            'reg_email' => 'required|string|email|max:100|unique:users,email',
            'reg_fullname' => 'required|string|max:100',
            'reg_password' => 'required|string|min:6',
            'reg_confirm_password' => 'required|string|same:reg_password',
        ], [
            'reg_username.unique' => 'Username sudah digunakan!',
            'reg_email.unique' => 'Email sudah digunakan!',
            'reg_password.min' => 'Password minimal 6 karakter!',
            'reg_confirm_password.same' => 'Password dan konfirmasi password tidak sama!',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'username' => $request->reg_username,
            'email' => $request->reg_email,
            'full_name' => $request->reg_fullname,
            'password' => Hash::make($request->reg_password),
        ]);

        return back()->with('success', 'Akun berhasil dibuat! Silakan login.');
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'forgot_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::where('email', $request->forgot_email)->first();

        if (!$user) {
            return back()->withErrors(['forgot_email' => 'Email tidak ditemukan!']);
        }

        // Generate reset token
        $resetToken = Str::random(32);
        $resetExpires = now()->addHour();

        $user->update([
            'reset_token' => $resetToken,
            'reset_expires' => $resetExpires,
        ]);

        // In real implementation, send email with reset link
        // For now, just show success message
        return back()->with('success', 'Link reset password telah dikirim ke email Anda.');
    }

    public function showResetPassword($token)
    {
        $user = User::where('reset_token', $token)
            ->where('reset_expires', '>', now())
            ->first();

        if (!$user) {
            return redirect()->route('login')->withErrors(['token' => 'Token reset password tidak valid atau sudah expired.']);
        }

        return view('auth.reset-password', compact('token'));
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'required|string|min:6',
            'password_confirmation' => 'required|string|same:password',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $user = User::where('reset_token', $request->token)
            ->where('reset_expires', '>', now())
            ->first();

        if (!$user) {
            return back()->withErrors(['token' => 'Token reset password tidak valid atau sudah expired.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'reset_token' => null,
            'reset_expires' => null,
        ]);

        return redirect()->route('login')->with('success', 'Password berhasil direset! Silakan login dengan password baru.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }
}
