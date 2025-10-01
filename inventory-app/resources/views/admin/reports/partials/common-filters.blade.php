<div class="form-row">
    <div class="form-group col-md-3">
        <label for="category_id">หมวดหมู่</label>
        <select name="category_id" id="category_id" class="form-control">
            <option value="">ทั้งหมด</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-3">
        <label for="status">สถานะสินค้า</label>
        <select name="status" id="status" class="form-control">
            <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>ทั้งหมด</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')>ใช้งาน</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>ปิดใช้งาน</option>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="search">ค้นหา SKU/ชื่อสินค้า</label>
        <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="ระบุคำค้น">
    </div>
</div>
