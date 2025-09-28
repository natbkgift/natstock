<?php ob_start(); ?>
<div class="card">
    <div class="card-header">เพิ่มหมวดหมู่</div>
    <div class="card-body">
        <form method="POST" action="/admin/categories">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label class="required">ชื่อหมวดหมู่</label>
                <input type="text" name="name" class="form-control" value="<?= e(old('name')) ?>">
                <?php if ($error = errors('name')): ?><small class="text-danger"><?= e($error) ?></small><?php endif; ?>
            </div>
            <div class="form-group">
                <label>หมายเหตุ</label>
                <textarea name="note" class="form-control"><?= e(old('note')) ?></textarea>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" name="is_active" class="form-check-input" checked>
                <label class="form-check-label">เปิดใช้งาน</label>
            </div>
            <button type="submit" class="btn btn-primary">บันทึก</button>
            <a href="/admin/categories" class="btn btn-secondary">ยกเลิก</a>
        </form>
    </div>
</div>
<?php $slot = ob_get_clean(); include resource_path('views/layouts/app.php'); ?>
