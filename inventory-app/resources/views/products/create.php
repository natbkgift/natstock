<?php ob_start(); ?>
<div class="card">
    <div class="card-header">เพิ่มสินค้า</div>
    <div class="card-body">
        <form method="POST" action="/admin/products">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label class="required">SKU</label>
                    <input type="text" name="sku" class="form-control" value="<?= e(old('sku')) ?>">
                    <?php if ($error = errors('sku')): ?><small class="text-danger"><?= e($error) ?></small><?php endif; ?>
                </div>
                <div class="form-group col-md-8">
                    <label class="required">ชื่อสินค้า</label>
                    <input type="text" name="name" class="form-control" value="<?= e(old('name')) ?>">
                    <?php if ($error = errors('name')): ?><small class="text-danger"><?= e($error) ?></small><?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label>หมายเหตุ</label>
                <textarea name="note" class="form-control"><?= e(old('note')) ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label class="required">หมวดหมู่</label>
                    <select name="category_id" class="form-control">
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($error = errors('category_id')): ?><small class="text-danger"><?= e($error) ?></small><?php endif; ?>
                </div>
                <div class="form-group col-md-4">
                    <label>ราคาทุน</label>
                    <input type="number" step="0.01" name="cost_price" class="form-control" value="<?= e(old('cost_price', 0)) ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>ราคาขาย</label>
                    <input type="number" step="0.01" name="sale_price" class="form-control" value="<?= e(old('sale_price', 0)) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>วันหมดอายุ</label>
                    <input type="date" name="expire_date" class="form-control" value="<?= e(old('expire_date')) ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>จุดสั่งซื้อซ้ำ</label>
                    <input type="number" name="reorder_point" class="form-control" value="<?= e(old('reorder_point', 0)) ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>ปริมาณคงเหลือ</label>
                    <input type="number" name="quantity" class="form-control" value="<?= e(old('quantity', 0)) ?>">
                </div>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" name="is_active" class="form-check-input" checked>
                <label class="form-check-label">เปิดใช้งาน</label>
            </div>
            <button type="submit" class="btn btn-primary">บันทึก</button>
            <a href="/admin/products" class="btn btn-secondary">ยกเลิก</a>
        </form>
    </div>
</div>
<?php $slot = ob_get_clean(); include resource_path('views/layouts/app.php'); ?>
