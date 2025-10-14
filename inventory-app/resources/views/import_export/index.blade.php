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
                            <label for="import-file">เลือกไฟล์นำเข้า</label>
                            <input type="file" class="form-control-file" id="import-file" name="file" accept=".csv,.xlsx" required>
                            <small class="form-text text-muted">รองรับไฟล์ .csv และ .xlsx (ถ้ามีการติดตั้งไลบรารี)</small>
                        </div>
                        <button type="submit" class="btn btn-primary">พรีวิว</button>
                    </form>

                    <div id="preview-errors" class="alert alert-danger mt-3 d-none" role="alert"></div>

                    <div id="preview-container" class="mt-4">
                        <div class="text-muted">ยังไม่มีข้อมูลพรีวิว กรุณาอัปโหลดไฟล์เพื่อดูตัวอย่าง</div>
                    </div>

                    <hr class="my-4">

                    <div id="process-settings">
                        <h5 class="mb-3">ตั้งค่าโหมดนำเข้า</h5>
                        <form id="import-process-form" action="{{ route('import_export.process') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="import-mode">โหมดนำเข้า</label>
                                <select id="import-mode" name="mode" class="form-control">
                                    <option value="upsert_replace">UPSERT - REPLACE (ปรับยอดเป็นค่าที่นำเข้า)</option>
                                    <option value="upsert_delta">UPSERT - DELTA (เพิ่มตามจำนวนที่นำเข้า)</option>
                                    <option value="skip">SKIP (ข้ามล็อตที่มีอยู่)</option>
                                </select>
                            </div>
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="import-strict" name="strict" value="1" checked>
                                <label class="custom-control-label" for="import-strict">STRICT (หากพบข้อผิดพลาดจะยกเลิกทั้งไฟล์)</label>
                            </div>
                            <button type="button" class="btn btn-success" id="import-process-button">
                                <i class="fas fa-play mr-2"></i>เริ่มนำเข้า
                            </button>
                        </form>
                        <div id="process-errors" class="alert alert-danger mt-3 d-none" role="alert"></div>
                        <div id="process-result" class="mt-4 d-none">
                            <h5 class="mb-3">สรุปผลการนำเข้า</h5>
                            <div id="process-message" class="mb-3 text-muted"></div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-3">
                                    <tbody id="process-summary-table"></tbody>
                                </table>
                            </div>
                            <div id="process-ignored" class="mb-2 text-muted d-none"></div>
                            <a href="#" id="error-csv-link" class="btn btn-outline-secondary d-none" target="_blank" rel="noopener">ดาวน์โหลด error.csv</a>
                        </div>
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
            const fileInput = document.getElementById('import-file');
            const processForm = document.getElementById('import-process-form');
            const processButton = document.getElementById('import-process-button');
            const processErrorBox = document.getElementById('process-errors');
            const processResult = document.getElementById('process-result');
            const processMessage = document.getElementById('process-message');
            const processSummaryTable = document.getElementById('process-summary-table');
            const processIgnored = document.getElementById('process-ignored');
            const errorCsvLink = document.getElementById('error-csv-link');

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

            if (processButton && processForm) {
                processButton.addEventListener('click', function () {
                    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                        if (processErrorBox) {
                            processErrorBox.textContent = 'กรุณาเลือกไฟล์ก่อนเริ่มนำเข้า';
                            processErrorBox.classList.remove('d-none');
                        }
                        return;
                    }

                    const formData = new FormData();
                    formData.append('file', fileInput.files[0]);

                    const modeInput = document.getElementById('import-mode');
                    const strictInput = document.getElementById('import-strict');

                    formData.append('mode', modeInput ? modeInput.value : 'upsert_replace');
                    formData.append('strict', strictInput && strictInput.checked ? '1' : '0');

                    const tokenInput = processForm.querySelector('input[name="_token"]');
                    const csrfToken = tokenInput ? tokenInput.value : '';

                    if (processErrorBox) {
                        processErrorBox.classList.add('d-none');
                        processErrorBox.textContent = '';
                    }

                    if (processResult) {
                        processResult.classList.add('d-none');
                    }

                    if (errorCsvLink) {
                        errorCsvLink.classList.add('d-none');
                    }

                    if (processIgnored) {
                        processIgnored.classList.add('d-none');
                        processIgnored.textContent = '';
                    }

                    processButton.disabled = true;
                    const originalLabel = processButton.innerHTML;
                    processButton.innerHTML = '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>กำลังนำเข้า...';

                    fetch(processForm.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData,
                    })
                        .then(async response => {
                            const data = await response.json();
                            if (!response.ok) {
                                throw data;
                            }

                            return data;
                        })
                        .then(data => {
                            renderProcessSummary(data);
                        })
                        .catch(error => {
                            let message = 'ไม่สามารถนำเข้าไฟล์ได้';
                            if (error && typeof error === 'object' && error.message) {
                                message = error.message;
                            }

                            if (processErrorBox) {
                                processErrorBox.textContent = message;
                                processErrorBox.classList.remove('d-none');
                            }

                            if (error && typeof error === 'object') {
                                renderProcessSummary(error);
                            }
                        })
                        .finally(() => {
                            processButton.disabled = false;
                            processButton.innerHTML = originalLabel;
                        });
                });
            }

            function renderProcessSummary(data) {
                if (!data || !data.summary || !processResult || !processSummaryTable) {
                    return;
                }

                const summary = data.summary;
                const rows = [
                    ['สินค้า (สร้างใหม่)', summary.products_created ?? 0],
                    ['สินค้า (ปรับปรุง)', summary.products_updated ?? 0],
                    ['ล็อต (สร้างใหม่)', summary.batches_created ?? 0],
                    ['ล็อต (ปรับปรุง)', summary.batches_updated ?? 0],
                    ['movement ที่สร้าง', summary.movements_created ?? 0],
                    ['แถวที่สำเร็จ', summary.rows_ok ?? 0],
                    ['แถวที่ผิดพลาด', summary.rows_error ?? 0],
                    ['STRICT rollback', summary.strict_rolled_back ? 'ใช่' : 'ไม่'],
                ];

                processSummaryTable.innerHTML = rows
                    .map(([label, value]) => `<tr><th class="w-50">${label}</th><td>${value}</td></tr>`)
                    .join('');

                if (processMessage) {
                    processMessage.textContent = data.message || '';
                    processMessage.classList.toggle('d-none', !data.message);
                }

                if (Array.isArray(data.ignored_columns) && data.ignored_columns.length > 0 && processIgnored) {
                    processIgnored.textContent = 'คอลัมน์ที่ถูกเพิกเฉย: ' + data.ignored_columns.join(', ');
                    processIgnored.classList.remove('d-none');
                }

                if (errorCsvLink) {
                    if (data.error_csv_url) {
                        errorCsvLink.href = data.error_csv_url;
                        errorCsvLink.classList.remove('d-none');
                    } else {
                        errorCsvLink.classList.add('d-none');
                    }
                }

                processResult.classList.remove('d-none');
            }
        });
    </script>
@endpush
