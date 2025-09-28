<?php ob_start(); ?>
<div class="card">
    <div class="card-header">บันทึกการเคลื่อนไหวสต็อก</div>
    <div class="card-body">
        <form method="POST" action="/admin/movements">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label class="required">สินค้า</label>
                <select name="product_id" class="form-control">
                    <option value="">-- เลือกสินค้า --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= $product['id'] ?>"><?= e($product['name']) ?> (คงเหลือ <?= e($product['quantity']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <?php if ($error = errors('product_id')): ?><small class="text-danger"><?= e($error) ?></small><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="required">ประเภท</label>
                <select name="type" class="form-control">
                    <option value="in">รับเข้า</option>
                    <option value="out">เบิกออก</option>
                    <option value="adjust">ปรับยอด</option>
                </select>
            </div>
            <div class="form-group">
                <label class="required">จำนวน</label>
                <input type="number" name="amount" class="form-control" value="0">
                <?php if ($error = errors('amount')): ?><small class="text-danger"><?= e($error) ?></small><?php endif; ?>
            </div>
            <div class="form-group">
                <label>หมายเหตุ</label>
                <textarea name="note" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label>เวลาเกิดเหตุการณ์</label>
                <input type="datetime-local" name="happened_at" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">บันทึก</button>
            <a href="/admin/movements" class="btn btn-secondary">ยกเลิก</a>
        </form>
    </div>
</div>
<?php $slot = ob_get_clean(); include resource_path('views/layouts/app.php'); ?>
