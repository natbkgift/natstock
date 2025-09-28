<?php ob_start(); ?>
<div class="card">
    <div class="card-header">นำเข้าสินค้าจากไฟล์</div>
    <div class="card-body">
        <p>รองรับไฟล์ CSV หรือ XLSX ตามเทมเพลตที่กำหนด</p>
        <div class="mb-3">
            <a href="/templates/product_template.csv" class="btn btn-outline-secondary"><i class="fas fa-download"></i> ดาวน์โหลดเทมเพลต CSV</a>
            <a href="/templates/product_template.xlsx" class="btn btn-outline-secondary"><i class="fas fa-download"></i> ดาวน์โหลดเทมเพลต XLSX</a>
        </div>
        <form method="POST" action="/admin/import/preview" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label class="required">ไฟล์นำเข้า</label>
                <input type="file" name="file" class="form-control" accept=".csv,.xlsx">
            </div>
            <button type="submit" class="btn btn-primary">พรีวิวข้อมูล</button>
        </form>
    </div>
</div>
<?php $slot = ob_get_clean(); include resource_path('views/layouts/app.php'); ?>
