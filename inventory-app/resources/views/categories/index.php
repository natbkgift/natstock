<?php ob_start(); ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">รายการหมวดหมู่</h3>
        <a href="/admin/categories/create" class="btn btn-primary"><i class="fas fa-plus"></i> เพิ่มหมวดหมู่</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ชื่อหมวดหมู่</th>
                    <th>หมายเหตุ</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $item): ?>
                <tr>
                    <td><?= e($item['name']) ?></td>
                    <td><?= e($item['note']) ?></td>
                    <td><?= $item['is_active'] ? 'ใช้งาน' : 'ปิดใช้งาน' ?></td>
                    <td>
                        <a href="/admin/categories/edit?id=<?= $item['id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                        <form method="POST" action="/admin/categories/delete" class="d-inline" onsubmit="return confirm('ยืนยันการลบ?')">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">ลบ</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $slot = ob_get_clean(); include resource_path('views/layouts/app.php'); ?>
