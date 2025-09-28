<?php ob_start(); ?>
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title">ประวัติการเคลื่อนไหว</h3>
        <a href="/admin/movements/create" class="btn btn-primary"><i class="fas fa-plus"></i> บันทึกการเคลื่อนไหว</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>สินค้า</th>
                    <th>ประเภท</th>
                    <th>จำนวน</th>
                    <th>หมายเหตุ</th>
                    <th>ผู้ดำเนินการ</th>
                    <th>เวลา</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movements as $item): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= e($item['type']) ?></td>
                    <td><?= e($item['amount']) ?></td>
                    <td><?= e($item['note']) ?></td>
                    <td><?= e($item['actor_name']) ?></td>
                    <td><?= e($item['happened_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $slot = ob_get_clean(); include resource_path('views/layouts/app.php'); ?>
