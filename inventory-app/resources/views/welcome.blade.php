<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-5 mb-3">{{ config('app.name') }}</h1>
            <p class="lead">โปรดเข้าสู่ระบบเพื่อจัดการสต็อกสินค้า</p>
            <a class="btn btn-primary" href="{{ route('login') }}">เข้าสู่ระบบ</a>
            <a class="btn btn-outline-secondary" href="{{ route('register') }}">ลงทะเบียน</a>
        </div>
    </div>
</div>
</body>
</html>
