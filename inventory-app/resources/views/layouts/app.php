<?php $user = auth()->user(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(config('APP_NAME', 'ระบบคลังยา')) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Prompt', 'Tahoma', sans-serif; }
        .required::after { content: '*'; color: #dc3545; margin-left: 4px; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <form method="POST" action="/logout" class="d-inline">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-link nav-link" onclick="return confirm('ยืนยันออกจากระบบ?')">
                        <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                    </button>
                </form>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="/admin/dashboard" class="brand-link">
            <span class="brand-text font-weight-light">ระบบคลังยา</span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="#" class="d-block"><?= e($user->name ?? 'ผู้ใช้') ?> (<?= e($user->role ?? '') ?>)</a>
                </div>
            </div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item"><a href="/admin/dashboard" class="nav-link"><i class="nav-icon fas fa-chart-line"></i><p>แดชบอร์ด</p></a></li>
                    <li class="nav-item"><a href="/admin/products" class="nav-link"><i class="nav-icon fas fa-box"></i><p>สินค้า</p></a></li>
                    <li class="nav-item"><a href="/admin/categories" class="nav-link"><i class="nav-icon fas fa-tags"></i><p>หมวดหมู่</p></a></li>
                    <li class="nav-item"><a href="/admin/movements" class="nav-link"><i class="nav-icon fas fa-exchange-alt"></i><p>เคลื่อนไหวสต็อก</p></a></li>
                    <li class="nav-item"><a href="/admin/import" class="nav-link"><i class="nav-icon fas fa-file-import"></i><p>นำเข้าไฟล์</p></a></li>
                    <li class="nav-item"><a href="/admin/reports/expiring" class="nav-link"><i class="nav-icon fas fa-file-alt"></i><p>รายงาน</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <?php if ($message = flash('success')): ?>
                    <div class="alert alert-success"><?= e($message) ?></div>
                <?php endif; ?>
                <?php if ($message = flash('error')): ?>
                    <div class="alert alert-danger"><?= e($message) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                <?= $slot ?? '' ?>
            </div>
        </section>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
