<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($siteName = config('app.name'))
    <title>{{ $siteName }} - @yield('title', $siteName)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Sarabun', 'Prompt', sans-serif; }
        .content-header h1 { font-size: 1.6rem; }
        /* Prevent oversized SVG icons (e.g., pagination chevrons) */
        .content-wrapper svg {
            width: 1em;
            height: 1em;
        }
        .content-wrapper .page-link svg { vertical-align: -0.125em; }
        .select2-container--bootstrap4 .select2-selection--single {
            height: 38px;
            padding: 0.375rem 0.75rem;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 1.6;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .badge-warning { color: #856404; }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav ml-auto">
            @auth
                @php($notificationCount = auth()->user()->unreadNotifications()->count())
                <li class="nav-item mr-2">
                    <a href="{{ route('admin.notifications.index') }}" class="nav-link position-relative">
                        <i class="far fa-bell"></i>
                        @if($notificationCount > 0)
                            <span class="badge badge-danger navbar-badge">{{ $notificationCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">ออกจากระบบ</button>
                    </form>
                </li>
            @endauth
        </ul>
    </nav>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('admin.dashboard') }}" class="brand-link text-center">
            <span class="brand-text font-weight-light">{{ $siteName }}</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>แดชบอร์ด</p>
                        </a>
                    </li>
                    @can('access-staff')
                        <li class="nav-item">
                            <a href="{{ route('admin.notifications.index') }}" class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                                <i class="nav-icon far fa-bell"></i>
                                <p>การแจ้งเตือน</p>
                            </a>
                        </li>
                        @if(config('inventory.import_enabled'))
                            <li class="nav-item">
                                <a href="{{ route('import_export.index') }}" class="nav-link {{ request()->routeIs('import_export.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-file-import"></i>
                                    <p>นำเข้าส่งออกไฟล์</p>
                                </a>
                            </li>
                        @endif
                    @endcan
                    <li class="nav-item">
                        <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-box"></i>
                            <p>สินค้า</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-layer-group"></i>
                            <p>หมวดหมู่</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.movements.index') }}" class="nav-link {{ request()->routeIs('admin.movements.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-exchange-alt"></i>
                            <p>เคลื่อนไหวสต็อก</p>
                        </a>
                    </li>
                    @can('access-admin')
                        <li class="nav-item">
                            <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>ตั้งค่าระบบ</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.backup.index') }}" class="nav-link {{ request()->routeIs('admin.backup.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-database"></i>
                                <p>สำรองข้อมูล</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.audit.index') }}" class="nav-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-clipboard-list"></i>
                                <p>บันทึกกิจกรรม</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>จัดการผู้ใช้</p>
                            </a>
                        </li>
                    @endcan
                    <li class="nav-item">
                        <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-pie"></i>
                            <p>รายงาน</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">@yield('page_title', 'ส่วนจัดการหลังบ้าน')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            @yield('breadcrumbs')
                        </ol>
                    </div>
                </div>
                @if(session('status'))
                    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
                @endif
                @if(session('warning'))
                    <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                @endif
                @if(session('info'))
                    <div class="alert alert-info" role="alert">{{ session('info') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <strong>พบข้อผิดพลาด:</strong>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer text-sm text-center">
        © {{ date('Y') }} <a href="#" class="text-decoration-none">Nat Stock V 1.5</a>. สงวนลิขสิทธิ์
    </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
@stack('scripts')
</body>
</html>
