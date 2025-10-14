@extends('layouts.admin')

@section('title', 'นำเข้าส่งออกไฟล์')
@section('page_title', 'นำเข้าส่งออกไฟล์')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">หน้าหลัก</a></li>
    <li class="breadcrumb-item active">นำเข้าส่งออกไฟล์</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-primary card-outline">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">นำเข้าไฟล์</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle mr-2"></i>คอลัมน์ราคา (ถ้ามี) จะถูกเพิกเฉย
                    </div>
                    <form id="csv-preview-form" action="{{ route('import_export.preview') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="import-file">เลือกไฟล์ CSV</label>
                            <input type="file" class="form-control-file" id="import-file" name="file" accept=".csv" required>
                            <small class="form-text text-muted">รองรับไฟล์ .csv เท่านั้น</small>
                        </div>
                        <button type="submit" class="btn btn-primary">พรีวิว</button>
                    </form>

                    <div id="preview-errors" class="alert alert-danger mt-3 d-none" role="alert"></div>

                    <div id="preview-container" class="mt-4">
                        <div class="text-muted">ยังไม่มีข้อมูลพรีวิว กรุณาอัปโหลดไฟล์เพื่อดูตัวอย่าง</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title mb-0">ส่งออกไฟล์</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">ดาวน์โหลดรายงานสต็อกที่สำคัญในรูปแบบ CSV</p>
                    <div class="d-flex flex-column">
                        <a href="{{ route('admin.reports.expiring-batches', ['export' => 'csv']) }}" class="btn btn-outline-success mb-2">
                            <i class="fas fa-download mr-2"></i>ดาวน์โหลด expiring-batches.csv
                        </a>
                        <a href="{{ route('admin.reports.low-stock', ['export' => 'csv']) }}" class="btn btn-outline-success">
                            <i class="fas fa-download mr-2"></i>ดาวน์โหลด low-stock.csv
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('csv-preview-form');
            const previewContainer = document.getElementById('preview-container');
            const errorBox = document.getElementById('preview-errors');

            if (!form) {
                return;
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const formData = new FormData(form);
                const tokenInput = form.querySelector('input[name="_token"]');
                const csrfToken = tokenInput ? tokenInput.value : '';

                previewContainer.innerHTML = '<div class="text-muted">กำลังประมวลผล...</div>';
                errorBox.classList.add('d-none');
                errorBox.textContent = '';

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData,
                })
                    .then(async response => {
                        if (!response.ok) {
                            let message = 'ไม่สามารถพรีวิวไฟล์ได้';
                            try {
                                const data = await response.json();
                                if (data.errors && data.errors.file) {
                                    message = data.errors.file.join('\n');
                                }
                            } catch (error) {
                                // ignore JSON parsing errors
                            }
                            throw new Error(message);
                        }

                        return response.json();
                    })
                    .then(data => {
                        previewContainer.innerHTML = data.html;
                    })
                    .catch(error => {
                        previewContainer.innerHTML = '<div class="text-muted">ยังไม่มีข้อมูลพรีวิว กรุณาอัปโหลดไฟล์เพื่อดูตัวอย่าง</div>';
                        errorBox.textContent = error.message;
                        errorBox.classList.remove('d-none');
                    });
            });
        });
    </script>
@endpush
