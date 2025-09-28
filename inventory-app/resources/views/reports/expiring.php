<?php ob_start(); ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">รายงานสินค้าหมดอายุใน <?= $days ?> วัน</h3>
        <div>
            <a href="?days=<?= $days ?>&export=csv" class="btn btn-outline-success"><i class="fas fa-file-csv"></i> ส่งออก CSV</a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>ชื่อสินค้า</th>
                    <th>หมวดหมู่</th>
                    <th>คงเหลือ</th>
                    <th>วันหมดอายุ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= e($product['sku']) ?></td>
                    <td><?= e($product['name']) ?></td>
                    <td><?= e($product['category_name']) ?></td>
                    <td><?= e($product['quantity']) ?></td>
                    <td><?= e($product['expire_date']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $slot = ob_get_clean(); include resource_path('views/layouts/app.php'); ?>
