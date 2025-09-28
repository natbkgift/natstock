<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Prompt', 'Tahoma', sans-serif; }
        .login-box { max-width: 400px; margin: 80px auto; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="card">
        <div class="card-header text-center bg-primary text-white">
            <h4>เข้าสู่ระบบระบบคลังยา</h4>
        </div>
        <div class="card-body">
            <?php if ($message = flash('error')): ?>
                <div class="alert alert-danger"><?= e($message) ?></div>
            <?php endif; ?>
            <form method="POST" action="/login">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" name="email" class="form-control" value="<?= e(old('email')) ?>" required>
                </div>
                <div class="form-group">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
