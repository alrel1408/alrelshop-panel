<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('panel.name'))</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-header {
            padding: 25px 20px;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }

        .sidebar-header .logo i {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .sidebar-header .logo span {
            font-size: 20px;
            font-weight: 700;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            display: block;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .menu-item:hover,
        .menu-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: #3498db;
        }

        .menu-item i {
            width: 20px;
            margin-right: 12px;
        }

        .menu-section {
            padding: 10px 25px 5px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Main Content */
        .main-content {
            margin-left: 0;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .main-content.sidebar-open {
            margin-left: 280px;
        }

        /* Header */
        .header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 20px;
            color: #666;
            cursor: pointer;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: #f8f9fa;
            color: #333;
        }

        .header-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 15px;
            background: #f8f9fa;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-info:hover {
            background: #e9ecef;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-details .name {
            font-weight: 600;
            font-size: 14px;
        }

        .user-details .balance {
            font-size: 12px;
            color: #666;
        }

        .balance-info {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        /* Content Area */
        .content {
            padding: 30px;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee, #fdd);
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: linear-gradient(135deg, #efe, #dfd);
            color: #363;
            border: 1px solid #cfc;
        }

        .alert-info {
            background: linear-gradient(135deg, #e1f5fe, #b3e5fc);
            color: #0277bd;
            border: 1px solid #81d4fa;
        }

        /* Sidebar Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content.sidebar-open {
                margin-left: 0;
            }
            
            .header {
                padding: 15px 20px;
            }
            
            .content {
                padding: 20px;
            }

            .header-right .user-details {
                display: none;
            }
        }

        @yield('styles')
    </style>
</head>
<body>
    @auth
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="logo">
                <i class="fas fa-shield-alt"></i>
                <span>{{ config('panel.name') }}</span>
            </a>
        </div>
        
        <nav class="sidebar-menu">
            <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-home"></i> Dashboard
            </a>
            
            <div class="menu-section">Server Management</div>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.vps-servers.index') }}" class="menu-item {{ request()->routeIs('admin.vps-servers.*') ? 'active' : '' }}">
                <i class="fas fa-server"></i> Kelola VPS
            </a>
            @endif
            <a href="{{ route('accounts.create') }}" class="menu-item {{ request()->routeIs('accounts.create') ? 'active' : '' }}">
                <i class="fas fa-plus-circle"></i> Buat Akun VPN
            </a>
            <a href="{{ route('accounts.index') }}" class="menu-item {{ request()->routeIs('accounts.index', 'accounts.show') ? 'active' : '' }}">
                <i class="fas fa-list"></i> Akun Saya
            </a>
            
            <div class="menu-section">Keuangan</div>
            <a href="{{ route('transactions.topup') }}" class="menu-item {{ request()->routeIs('transactions.topup') ? 'active' : '' }}">
                <i class="fas fa-wallet"></i> Top Up Saldo
            </a>
            <a href="{{ route('transactions.index') }}" class="menu-item {{ request()->routeIs('transactions.index') ? 'active' : '' }}">
                <i class="fas fa-history"></i> Riwayat Transaksi
            </a>
            
            @if(auth()->user()->isAdmin())
            <div class="menu-section">Admin</div>
            <a href="{{ route('admin.users.index') }}" class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i> Kelola User
            </a>
            <a href="{{ route('admin.settings.index') }}" class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <i class="fas fa-cogs"></i> Pengaturan
            </a>
            @endif
            
            <div class="menu-section">Akun</div>
            <a href="{{ route('profile') }}" class="menu-item {{ request()->routeIs('profile') ? 'active' : '' }}">
                <i class="fas fa-user"></i> Profil Saya
            </a>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-title">
                    <h1>@yield('page-title', 'Dashboard')</h1>
                </div>
            </div>
            
            <div class="header-right">
                @if(request()->routeIs('accounts.create'))
                <div class="balance-info">
                    <i class="fas fa-wallet"></i> {{ auth()->user()->formatBalance() }}
                </div>
                @endif
                
                <div class="user-info">
                    <div class="user-avatar">
                        {{ strtoupper(substr(auth()->user()->full_name, 0, 1)) }}
                    </div>
                    <div class="user-details">
                        <div class="name">{{ auth()->user()->full_name }}</div>
                        <div class="balance">{{ auth()->user()->formatBalance() }}</div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Content -->
        <div class="content">
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
    @endauth

    @guest
    @yield('content')
    @endguest

    <script>
        @auth
        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-open');
            sidebarOverlay.classList.toggle('active');
        });
        
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            mainContent.classList.remove('sidebar-open');
            sidebarOverlay.classList.remove('active');
        });
        
        // Close sidebar on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebarOverlay.classList.remove('active');
            }
        });
        @endauth

        @yield('scripts')
    </script>
</body>
</html>
