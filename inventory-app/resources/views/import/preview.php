<?php ob_start(); ?>
<div class="card">
    <div class="card-header">พรีวิวข้อมูลนำเข้า</div>
    <div class="card-body">
        <form method="POST" action="/admin/import/process">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label>โหมดเมื่อพบ SKU ซ้ำ</label>
                <select name="mode" class="form-control">
                    <option value="upsert">อัปเดตข้อมูล (UPSERT)</option>
                    <option value="skip">ข้ามแถวซ้ำ (SKIP)</option>
                </select>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" name="auto_category" class="form-check-input" checked>
                <label class="form-check-label">สร้างหมวดหมู่อัตโนมัติถ้ายังไม่มี</label>
            </div>
            <button type="submit" class="btn btn-primary">เริ่มนำเข้า</button>
            <a href="/admin/import" class="btn btn-secondary">ยกเลิก</a>
        </form>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <?php if (!empty($rows)): ?>
                        <?php foreach (array_keys($rows[0]) as $header): ?>
                        <th><?= e($header) ?></th>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($row as $value): ?>
                    <td><?= e($value) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $slot = ob_get_clean(); include resource_path('views/layouts/app.php'); ?>
