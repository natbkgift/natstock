<?php ob_start(); ?>
<div class="card">
    <div class="card-header">
        <form method="GET" action="/admin/products" class="form-inline">
            <div class="form-group mr-2">
                <input type="text" name="keyword" class="form-control" placeholder="ค้นหา SKU หรือชื่อ" value="<?= e($keyword) ?>">
            </div>
            <button type="submit" class="btn btn-primary">ค้นหา</button>
            <a href="/admin/products/create" class="btn btn-success ml-auto"><i class="fas fa-plus"></i> เพิ่มสินค้า</a>
        </form>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>ชื่อสินค้า</th>
                    <th>หมวดหมู่</th>
                    <th>คงเหลือ</th>
                    <th>จุดสั่งซื้อซ้ำ</th>
                    <th>วันหมดอายุ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= e($product['sku']) ?></td>
                    <td><?= e($product['name']) ?></td>
                    <td><?= e($product['category_name']) ?></td>
                    <td><?= e($product['quantity']) ?></td>
                    <td><?= e($product['reorder_point']) ?></td>
                    <td><?= e($product['expire_date']) ?></td>
                    <td>
                        <a href="/admin/products/edit?id=<?= $product['id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                        <form method="POST" action="/admin/products/delete" class="d-inline" onsubmit="return confirm('ยืนยันการลบ?')">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">ลบ</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <nav>
            <ul class="pagination mb-0">
                <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
                <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&keyword=<?= e($keyword) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>
<?php $slot = ob_get_clean(); include resource_path('views/layouts/app.php'); ?>
