<?php ob_start(); ?>
<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= $expiringCounts[30] ?></h3>
                <p>ใกล้หมดอายุภายใน 30 วัน</p>
            </div>
            <a href="/admin/reports/expiring?days=30" class="small-box-footer">ดูรายการ <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= $expiringCounts[60] ?></h3>
                <p>ใกล้หมดอายุภายใน 60 วัน</p>
            </div>
            <a href="/admin/reports/expiring?days=60" class="small-box-footer">ดูรายการ <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= $expiringCounts[90] ?></h3>
                <p>ใกล้หมดอายุภายใน 90 วัน</p>
            </div>
            <a href="/admin/reports/expiring?days=90" class="small-box-footer">ดูรายการ <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= $lowStock ?></h3>
                <p>สต็อกต่ำกว่าจุดสั่งซื้อซ้ำ</p>
            </div>
            <a href="/admin/reports/low-stock" class="small-box-footer">ดูรายการ <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= format_currency($totalValue) ?> บาท</h3>
                <p>มูลค่าสต็อกตามราคาทุนรวม</p>
            </div>
            <a href="/admin/reports/stock-value" class="small-box-footer">ดูรายละเอียด <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">การเคลื่อนไหวล่าสุด</div>
    <div class="card-body p-0">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>สินค้า</th>
                    <th>ประเภท</th>
                    <th>จำนวน</th>
                    <th>ผู้บันทึก</th>
                    <th>เวลา</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movements as $movement): ?>
                <tr>
                    <td><?= e($movement['product_name']) ?></td>
                    <td><?= e($movement['type']) ?></td>
                    <td><?= e($movement['amount']) ?></td>
                    <td><?= e($movement['actor_name']) ?></td>
                    <td><?= e($movement['happened_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $slot = ob_get_clean(); include resource_path('views/layouts/app.php'); ?>
