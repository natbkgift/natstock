<?php ob_start(); ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">รายงานมูลค่าสต็อก</h3>
        <a href="?export=csv" class="btn btn-outline-success"><i class="fas fa-file-csv"></i> ส่งออก CSV</a>
    </div>
    <div class="card-body">
        <p><strong>มูลค่ารวม:</strong> <?= format_currency($total) ?> บาท</p>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>ชื่อสินค้า</th>
                    <th>หมวดหมู่</th>
                    <th>คงเหลือ</th>
                    <th>ราคาทุน</th>
                    <th>มูลค่าคงเหลือ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= e($product['sku']) ?></td>
                    <td><?= e($product['name']) ?></td>
                    <td><?= e($product['category_name']) ?></td>
                    <td><?= e($product['quantity']) ?></td>
                    <td><?= e($product['cost_price']) ?></td>
                    <td><?= format_currency($product['cost_price'] * $product['quantity']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $slot = ob_get_clean(); include resource_path('views/layouts/app.php'); ?>
