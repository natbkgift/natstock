<div class="form-row">
    <div class="form-group col-md-4">
        <label for="sku">รหัสสินค้า (SKU)</label>
        <input type="text" name="sku" id="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', optional($product)->sku) }}" placeholder="ปล่อยว่างให้ระบบสร้างอัตโนมัติ">
        <small class="form-text text-muted">ปล่อยว่างหากต้องการให้ระบบสร้างรหัสให้อัตโนมัติ</small>
        @error('sku')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-8">
        <label for="name">ชื่อสินค้า <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" placeholder="ระบุชื่อสินค้า" value="{{ old('name', optional($product)->name) }}" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="category_id">หมวดหมู่สินค้า <span class="text-danger">*</span></label>
        <div class="input-group">
            <select name="category_id" id="category_id" class="form-control select2 @error('category_id') is-invalid @enderror" data-placeholder="เลือกหมวดหมู่สินค้า">
                <option value="">-- เลือกหมวดหมู่ --</option>
                @foreach($categories as $categoryOption)
                    <option value="{{ $categoryOption->id }}" {{ (string) old('category_id', optional($product)->category_id) === (string) $categoryOption->id ? 'selected' : '' }}>{{ $categoryOption->name }}</option>
                @endforeach
            </select>
            <input type="text" name="new_category_name" id="new_category_name" class="form-control ml-2" placeholder="เพิ่มหมวดหมู่ใหม่" value="{{ old('new_category_name') }}">
            <div class="input-group-append">
                <button type="button" class="btn btn-outline-success" id="btn-save-category">บันทึกหมวดหมู่</button>
            </div>
        </div>
        <small class="form-text text-muted">หากต้องการเพิ่มหมวดหมู่ใหม่ ให้กรอกชื่อในช่อง "เพิ่มหมวดหมู่ใหม่"</small>
        @error('category_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        @error('new_category_name')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-6">
        <label for="note">หมายเหตุ</label>
        <textarea name="note" id="note" class="form-control @error('note') is-invalid @enderror" rows="2" placeholder="บันทึกข้อมูลเพิ่มเติม">{{ old('note', optional($product)->note) }}</textarea>
        @error('note')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label for="expire_in_days">หมดอายุใน (วัน)</label>
        <input type="number" min="1" name="expire_in_days" id="expire_in_days" class="form-control @error('expire_in_days') is-invalid @enderror" value="{{ old('expire_in_days') }}" placeholder="เช่น 15">
        @error('expire_in_days')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label for="expire_date">วันที่หมดอายุ</label>
        <input type="date" name="expire_date" id="expire_date" class="form-control @error('expire_date') is-invalid @enderror" value="{{ old('expire_date', optional(optional($product)->expire_date)->format('Y-m-d')) }}">
        @error('expire_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4 d-flex align-items-end">
        <div class="text-muted small">
            ระบุอย่างใดอย่างหนึ่ง: “หมดอายุใน (วัน)” หรือ “วันที่หมดอายุ” หากกรอกทั้งสอง ระบบจะใช้วันที่หมดอายุเป็นหลัก
        </div>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label for="reorder_point">จุดสั่งซื้อซ้ำ</label>
        <input type="number" min="0" name="reorder_point" id="reorder_point" class="form-control @error('reorder_point') is-invalid @enderror" value="{{ old('reorder_point', optional($product)->reorder_point ?? 0) }}">
        @error('reorder_point')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    @if($product === null)
        <div class="form-group col-md-4">
            <label for="initial_qty">จำนวนเริ่มต้น</label>
            <input type="number" min="0" name="initial_qty" id="initial_qty" class="form-control @error('initial_qty') is-invalid @enderror" value="{{ old('initial_qty', 0) }}">
            @error('initial_qty')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @else
        <div class="form-group col-md-4">
            <label for="qty">ปริมาณคงเหลือ</label>
            <input type="text" id="qty" class="form-control" value="{{ number_format($product->qty ?? 0) }}" readonly>
            <small class="form-text text-muted">ปรับปรุงยอดผ่านหน้าเคลื่อนไหวสต็อกเท่านั้น</small>
        </div>
    @endif
</div>
<div class="form-row">
    @if(config('inventory.enable_price'))
        <div class="form-group col-md-6">
            <label for="cost_price">ราคาทุนต่อหน่วย</label>
            <input type="number" step="0.01" min="0" name="cost_price" id="cost_price" class="form-control @error('cost_price') is-invalid @enderror" value="{{ old('cost_price', optional($product)->cost_price) }}">
            @error('cost_price')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group col-md-6">
            <label for="sale_price">ราคาขายต่อหน่วย</label>
            <input type="number" step="0.01" min="0" name="sale_price" id="sale_price" class="form-control @error('sale_price') is-invalid @enderror" value="{{ old('sale_price', optional($product)->sale_price) }}">
            @error('sale_price')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @else
        <div class="form-group col-md-8">
            <label class="d-block">ราคาทุนและราคาขาย</label>
            <div class="alert alert-secondary mb-0">ระบบปิดการใช้งานราคาทุน/ราคาขายอยู่ ค่าดังกล่าวจะไม่ถูกบันทึก</div>
        </div>
    @endif
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label class="d-block">สถานะการใช้งาน</label>
        <div class="custom-control custom-switch">
            <input type="checkbox" name="is_active" class="custom-control-input" id="is_active" value="1" {{ old('is_active', optional($product)->is_active ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label" for="is_active">เปิดใช้งานสินค้า</label>
        </div>
    </div>
</div>
<div class="d-flex justify-content-between">
    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">ยกเลิก</a>
    <button type="submit" class="btn btn-primary">บันทึกข้อมูลสินค้า</button>
</div>
