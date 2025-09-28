<div class="form-group">
    <label for="name">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" placeholder="ระบุชื่อหมวดหมู่" value="{{ old('name', optional($category)->name) }}" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="note">หมายเหตุ</label>
    <textarea name="note" id="note" class="form-control @error('note') is-invalid @enderror" rows="3" placeholder="บันทึกรายละเอียดเพิ่มเติม (ถ้ามี)">{{ old('note', optional($category)->note) }}</textarea>
    @error('note')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <div class="custom-control custom-switch">
        <input type="checkbox" name="is_active" class="custom-control-input" id="is_active" value="1" {{ old('is_active', optional($category)->is_active ?? true) ? 'checked' : '' }}>
        <label class="custom-control-label" for="is_active">สถานะการใช้งาน</label>
    </div>
    <small class="form-text text-muted">เปิดเพื่อให้หมวดหมู่นี้ใช้งานได้</small>
</div>
<div class="d-flex justify-content-between">
    <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">ยกเลิก</a>
    <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
</div>
