<?php ob_start(); ?>
<div class="card">
    <div class="card-header">ผลการนำเข้า</div>
    <div class="card-body">
        <p>สำเร็จ: <?= $result['success'] ?> แถว</p>
        <p>ข้าม: <?= $result['skipped'] ?> แถว</p>
        <p>ผิดพลาด: <?= count($result['errors']) ?> แถว</p>
        <?php if (!empty($result['errors'])): ?>
            <a href="/admin/import/errors" class="btn btn-outline-danger">ดาวน์โหลดรายการผิดพลาด</a>
        <?php endif; ?>
        <a href="/admin/import" class="btn btn-primary">กลับไปหน้าอัปโหลด</a>
    </div>
    <?php if (!empty($result['errors'])): ?>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>บรรทัด</th>
                    <th>ข้อความ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['errors'] as $error): ?>
                <tr>
                    <td><?= e($error['line']) ?></td>
                    <td><?= e($error['message']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php $slot = ob_get_clean(); include resource_path('views/layouts/app.php'); ?>
