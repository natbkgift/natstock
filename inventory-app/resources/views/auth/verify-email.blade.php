<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ยืนยันอีเมล - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h1 class="h4 mb-3">ตรวจสอบอีเมลของคุณ</h1>
                    <p class="mb-3">กรุณาเปิดอีเมลและคลิกลิงก์ยืนยันเพื่อเปิดใช้งานบัญชี</p>
                    @if ($status)
                        <div class="alert alert-success">ส่งลิงก์ยืนยันใหม่เรียบร้อยแล้ว</div>
                    @endif
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">ส่งอีเมลยืนยันอีกครั้ง</button>
                    </form>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-link">ออกจากระบบ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
