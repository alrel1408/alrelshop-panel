<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VpnAccountController;
use App\Http\Controllers\VpsServerController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to dashboard if authenticated, otherwise to login
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Logout route (can be accessed by authenticated users)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    
    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::post('/profile', [DashboardController::class, 'updateProfile']);
    Route::post('/change-password', [DashboardController::class, 'changePassword'])->name('change-password');
    
    // VPN Account Routes
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [VpnAccountController::class, 'index'])->name('index');
        Route::get('/create', [VpnAccountController::class, 'create'])->name('create');
        Route::post('/create', [VpnAccountController::class, 'store'])->name('store');
        Route::get('/{account}', [VpnAccountController::class, 'show'])->name('show');
        Route::post('/{account}/extend', [VpnAccountController::class, 'extend'])->name('extend');
    });
    
    // Transaction Routes
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/topup', [TransactionController::class, 'showTopup'])->name('topup');
        Route::post('/topup', [TransactionController::class, 'processTopup'])->name('topup.process');
    });
    
    // Admin Routes (Admin Only)
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        
        // VPS Server Management
        Route::prefix('vps-servers')->name('vps-servers.')->group(function () {
            Route::get('/', [VpsServerController::class, 'index'])->name('index');
            Route::post('/', [VpsServerController::class, 'store'])->name('store');
            Route::put('/{vpsServer}', [VpsServerController::class, 'update'])->name('update');
            Route::delete('/{vpsServer}', [VpsServerController::class, 'destroy'])->name('destroy');
            Route::post('/{vpsServer}/test', [VpsServerController::class, 'testConnection'])->name('test');
        });
        
        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::post('/{user}/balance', [UserController::class, 'updateBalance'])->name('update-balance');
            Route::post('/{user}/status', [UserController::class, 'updateStatus'])->name('update-status');
        });
        
        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::post('/', [SettingController::class, 'update'])->name('update');
        });
    });
});

// Legacy routes for backward compatibility (redirect to new routes)
Route::get('/create-account.php', function () {
    return redirect()->route('accounts.create');
});

Route::get('/dashboard.php', function () {
    return redirect()->route('dashboard');
});

Route::get('/login.php', function () {
    return redirect()->route('login');
});

Route::get('/logout.php', function () {
    return redirect()->route('logout');
});

Route::get('/admin-vps.php', function () {
    return redirect()->route('admin.vps-servers.index');
});
