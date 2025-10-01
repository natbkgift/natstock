<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'NatStock') }} | ยินดีต้อนรับ</title>

    <!-- Google Font: Sarabun & Prompt -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Sarabun:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Prompt:300,400,400i,700&display=fallback">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    
    <!-- AdminLTE style -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        body { 
            font-family: 'Sarabun', 'Prompt', sans-serif; 
        }
        .login-box {
            width: 420px;
        }
        .login-logo a {
            font-size: 2.5rem;
            font-weight: 300;
        }
        .login-card-body .btn {
            font-size: 1.1rem;
            padding: 0.75rem;
        }
    </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <a href="{{ route('welcome') }}"><b>Nat</b>Stock</a>
    </div>
    <!-- /.login-logo -->
    <div class="card card-outline card-primary">
        <div class="card-body login-card-body text-center">
            <p class="login-box-msg" style="font-size: 1.2rem;">ระบบจัดการสต็อกสินค้า</p>
            <p class="text-muted">บริหารจัดการสต็อกอย่างมีประสิทธิภาพ<br>ติดตามสินค้าคงคลัง, การรับเข้า, และการเบิกออก</p>

            <div class="d-grid gap-2 mt-4">
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        เข้าสู่ระบบ
                    </a>
                @endif

                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-user-plus mr-2"></i>
                        ลงทะเบียน
                    </a>
                @endif
            </div>
        </div>
        <!-- /.login-card-body -->
    </div>
    <div class="text-center text-muted mt-3">
        Phase 3
    </div>
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>