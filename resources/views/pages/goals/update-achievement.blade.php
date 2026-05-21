@extends('layouts_.vertical', ['page_title' => 'Update Achievement', 'parentLink' => 'Achievement'])

@section('css')
<style>
:root {
    --kpn-primary: #AB2F2B;
    --kpn-primary-hover: #8f2623;
    --kpn-primary-soft: #fdf2f2;
}

.text-primary { color: var(--kpn-primary) !important; }
.bg-primary { background-color: var(--kpn-primary) !important; color: white !important; }
.bg-primary-soft { background-color: var(--kpn-primary-soft) !important; }
.bg-primary-subtle { background-color: #f8d7d6 !important; }

input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
}
input[type=number] {
    -moz-appearance: textfield;
}

.kpi-label {
    color: #9e2a2b;
    font-size: 0.7rem;
    letter-spacing: 0.5px;
}

.month-box {
    border-radius: 6px;
    transition: all 0.3s ease;
}

.month-box.readonly-mode {
    background-color: #e9ecef;
    border: 1px solid #dee2e6;
    opacity: 0.8;
}

.month-box.edit-mode-active {
    border: 1px solid #dc3545 !important;
    background-color: #fffcfc !important;
    box-shadow: 0 0 5px rgba(220, 53, 69, 0.15);
}

.input-compact {
    background: transparent;
    border: none;
    width: 100%;
    text-align: center;
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    padding: 0.2rem 0;
    outline: none;
}
.input-compact:read-only {
    cursor: not-allowed;
    color: #6c757d;
}

.input-compact::placeholder {
    color: #adb5bd;
    font-weight: 400;
}

.btn-attach-mini {
    font-size: 0.65rem;
    padding: 2px 0;
    margin: 0;
    border-radius: 3px;
    cursor: pointer;
    background-color: #fff;
    border: 1px solid #dee2e6;
    color: #6c757d;
    transition: all 0.2s;
}

.btn-attach-mini.disabled-attach {
    cursor: not-allowed;
    background-color: #f8f9fa;
    opacity: 0.6;
}

.btn-attach-mini.has-file {
    background-color: #198754;
    border-color: #198754;
    color: #fff;
    opacity: 1 !important;
}

.trigger-edit {
    cursor: pointer;
    transition: opacity 0.2s;
}
.trigger-edit:hover {
    opacity: 0.7;
}

.btn-back .arrow-icon {
    display: inline-block;
    transition: transform 0.25s ease;
}

.btn-back:hover .arrow-icon {
    transform: translateX(-4px);
}

.edit-mode-active {
    border: 1px solid #198754 !important;
    background-color: #f0fff4 !important;
}

.btn-attach-mini.has-file {
    background-color: #198754;
    color: #fff;
}
.mini-progress {
    height: 4px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 4px;
}

.mini-progress-bar.bg-primary { height: 100%; border-radius: 10px; background: linear-gradient(90deg, var(--kpn-primary) 25%, #d96865 50%, var(--kpn-primary) 75%); background-size: 200% 100%; animation: progressFlow 1.5s linear infinite; }
@keyframes progressFlow { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ $selfUpdate ? route('goals') : route('team-goals') }}" class="btn border border-dark-subtle btn-sm text-secondary fw-medium btn-back">
            <i class="ri-arrow-left-line me-1 arrow-icon"></i> {{ __('Back') }}
        </a>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="m-0 font-weight-bold text-primary">{{ __('Update Achievement') }}</h4>
    </div>

    @if ($approvalInfo && $approvalInfo->approval_info)
        <div class="alert alert-warning border-0 mt-3 p-3">
            <strong class="d-block mb-1"><i class="ri-feedback-line me-1"></i> Achievement Revision Notes:</strong>
            <span class="text-dark">{{ $approvalInfo->approval_info }}</span>
        </div>
    @endif

    <form id="achievementForm" action="{{ route('achievement.bulk-store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @php
            $months = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
            ];
        @endphp

        @foreach ($formData as $index => $data)
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body p-3 p-md-4">
                <input type="hidden" name="kpi_id[]" value="{{ $data['kpi_id'] ?? '' }}">
                <input type="hidden" name="goal_id" value="{{ $id }}">
                <input type="hidden" name="review_period[]" value="{{ $data['review_period'] }}">
                <input type="hidden" name="calculation_method[]" value="{{ $data['calculation_method'] }}">
                <div class="row g-3">
                    <div class="col-md-5 col-lg-5 mb-md-0">
                                                <small class="fw-bold text-uppercase d-block kpi-label mb-1">KPI {{ $index + 1 }}</small>
                                                <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">{{ $data['kpi'] }}</h6>
                                                <p class="text-secondary mb-0" style="font-size: 0.85rem; line-height: 1.5;">{{ $data['description'] ?? '-' }}</p>
                                            </div>
                                            <div class="col-md-7 col-lg-7">
                                                <div class="row g-3 mb-3">
                                                    <div class="col-3 col-sm-3">
                                                        <small class="fw-bold text-uppercase d-block kpi-label mb-1">Target</small>
                                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['target'] }}</span>
                                                    </div>
                                                    <div class="col-3 col-sm-3">
                                                        <small class="fw-bold text-uppercase d-block kpi-label mb-1">UoM</small>
                                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ is_null($data['custom_uom']) ? $data['uom'] : $data['custom_uom'] }}</span>
                                                    </div>
                                                    <div class="col-3 col-sm-3">
                                                        <small class="fw-bold text-uppercase d-block kpi-label mb-1">Weightage</small>
                                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['weightage'] }}</span>
                                                    </div>
                                                    <div class="col-3 col-sm-3">
                                                        <small class="fw-bold text-uppercase d-block kpi-label mb-1">Achievement</small>

                                                        <span class="fw-bold text-dark d-block" style="font-size: 0.95rem;">
                                                            {{ $data['achievement'] ?? '0' }}%
                                                        </span>

                                                        @php
                                                            $percent = (int) ($data['achievement'] ?? 0);
                                                        @endphp

                                                        <div class="mini-progress">
                                                            <div class="mini-progress-bar bg-primary"
                                                                data-width="{{ $percent }}%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                        <div class="row g-3 mb-3">
                                                    <div class="col-3 col-sm-3">
                                                        <small class="fw-bold text-uppercase d-block kpi-label mb-1">Type</small>
                                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['type'] }}</span>
                                                    </div>
                                                    <div class="col-3 col-sm-3">
                                                        <small class="fw-bold text-uppercase d-block kpi-label mb-1">Review Period</small>
                                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['review_period_label'] }}</span>
                                                    </div>
                                                    <div class="col-3 col-sm-3">
                                                        <small class="fw-bold text-uppercase d-block kpi-label mb-1">Calc Method</small>
                                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['calculation_method_label'] }}</span>
                                                    </div>
                                                    <div class="col-3">
                                                        <small class="fw-bold text-uppercase d-block kpi-label mb-1"></small>
                                                        <span class="fw-bold text-dark"></span>
                                                    </div>
                                                </div>
                                            </div>
                </div>

                <div>
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <h6 class="fw-bold text-dark mb-0 fs-6">{{ __('Monthly Achievement') }}</h6>
                        <span class="text-danger fw-bold trigger-edit" id="trigger_{{ $index }}" onclick="enableEditMode('kpi_grid_{{ $index }}', 'input_{{ $index }}_jan', 'trigger_{{ $index }}')">
                            <i class="ri-edit-2-line"></i> Click to Edit
                        </span>
                    </div>
                    
                    <div class="row g-2 kpi-grid" 
                        id="kpi_grid_{{ $index }}" 
                        data-review-period="{{ $data['review_period'] ?? 1 }}">

                        @foreach($months as $monthNum => $monthLabel)
                            @php 
                                $elementId = "file_{$index}_{$monthNum}"; 
                                $inputId = "input_{$index}_{$monthNum}";
                            @endphp

                            <div class="col-xl-1 col-lg-2 col-md-3 col-4">
                                <div class="month-box readonly-mode p-1 text-center position-relative">

                                    {{-- Month Label --}}
                                    <div class="text-uppercase fw-bold text-secondary mb-1 rounded bg-white border" style="font-size: 0.65rem; padding: 2px 0;">
                                        {{ $monthLabel }}
                                    </div>

                                    {{-- INPUT --}}
                                    <input type="number" 
                                        step="any"
                                        id="{{ $inputId }}"
                                        name="ach[{{ $index }}][{{ $monthNum }}]" 
                                        class="input-compact" 
                                        placeholder="-"
                                        value="{{ isset($data['ach'][$monthNum]) ? rtrim(rtrim($data['ach'][$monthNum], '0'), '.') : '' }}"
                                        readonly
                                        data-month="{{ $monthNum }}"
                                        onkeydown="return !['e', 'E', '+'].includes(event.key);"> 

                                    {{-- FILE INPUT --}}
                                    <input type="file" 
                                        id="{{ $elementId }}" 
                                        name="attachment[{{ $index }}][{{ $monthNum }}]" 
                                        class="d-none file-input-trigger" 
                                        data-target="label_{{ $elementId }}"
                                        accept=".pdf,.png,.jpg,.jpeg" disabled>

                                    {{-- UPLOAD BUTTON --}}
                                    <label for="{{ $elementId }}" 
                                        id="label_{{ $elementId }}" 
                                        class="btn-attach-mini disabled-attach w-100 d-block mt-1">
                                        <i class="ri-attachment-2 icon-attach"></i> 
                                        <span class="text-attach">FILE</span>
                                    </label>

                                    {{-- VIEW ATTACHMENT --}}
                                    @if(!empty($data['attachment'][$monthNum]))
                                        <a href="{{ asset('storage/'.$data['attachment'][$monthNum]) }}" 
                                            target="_blank"
                                            class="btn-attach-mini w-100 d-block mt-1 border border-info text-info"
                                            title="View Attachment">
                                            <i class="ri-file-line"></i> VIEW
                                        </a>
                                    @endif

                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
        @endforeach
        <input type="hidden" name="submit_type" id="submitType">
        <div class="card shadow mt-4 sticky-bottom border-top-0" style="bottom: 20px; z-index: 100;">
            <div class="card-body py-2 d-flex justify-content-end align-items-center bg-white rounded border border-light">
                <input type="hidden" name="employee_id" value="{{ $goal->employee_id }}">
                <button data-id="draft" type="button" class="btn btn-secondary me-2 px-3 shadow-sm fw-bold btn-sm">
                    <i class="ri-save-line me-1"></i> {{ __('Save as Draft') }}
                </button>
                <a href="{{ $selfUpdate ? route('goals') : route('team-goals') }}" class="btn btn-light text-secondary border me-2 px-3 btn-sm fw-medium">{{ __('Cancel') }}</a>
                <button data-id="submit" type="button" class="btn btn-success px-3 shadow-sm fw-bold btn-sm">
                    <i class="ri-save-line me-1"></i> {{ __('Submit') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        document.querySelectorAll('[id^="kpi_grid_"]').forEach(grid => {
            const index = grid.id.split('_').pop();

            updateEditTriggerState(`kpi_grid_${index}`, `trigger_${index}`);
        });

        const buttons = document.querySelectorAll('button[data-id]');
        const submitInput = document.getElementById('submitType');
        const form = document.getElementById('achievementForm');

        buttons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const type = this.getAttribute('data-id');
                submitInput.value = type;

                const isSubmit = type === 'submit';

                Swal.fire({
                    title: 'Are you sure?',
                    text: isSubmit 
                        ? "This data will be submitted!" 
                        : "This data will be saved as draft!",
                    icon: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: isSubmit 
                        ? 'Yes, submit it!' 
                        : 'Yes, save it!'
                }).then((result) => {
                    if (result.isConfirmed) {

                        // disable semua tombol (prevent double submit)
                        buttons.forEach(btn => btn.disabled = true);

                        // optional loading
                        Swal.fire({
                            title: 'Processing...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        form.submit();
                    }
                });
            });
        });

    });

    function isEditAllowed(grid) {
        const reviewPeriod = parseInt(grid.dataset.reviewPeriod);
        const currentMonth = new Date().getMonth() + 1;

        // cek apakah ada minimal 1 bulan yang valid
        let allowed = false;

        grid.querySelectorAll('.month-box').forEach(box => {
            const input = box.querySelector('.input-compact');
            if (!input) return;

            const month = parseInt(input.dataset.month);

            let isActive = false;

            if (reviewPeriod === 1) {
                isActive = true;
            } else if (reviewPeriod === 2) {
                isActive = (month % 2 === 0);
            } else if (reviewPeriod === 3) {
                isActive = (month % 3 === 0);
            } else if (reviewPeriod === 6) {
                isActive = (month % 6 === 0);
            } else if (reviewPeriod === 12) {
                isActive = (month === 12);
            }

            const isPastOrCurrent = month <= currentMonth;

            if (isActive && isPastOrCurrent) {
                allowed = true;
            }
        });

        return allowed;
    }

    function updateEditTriggerState(gridId, triggerId) {
        const grid = document.getElementById(gridId);
        const trigger = document.getElementById(triggerId);

        if (!grid || !trigger) return;

        const allowed = isEditAllowed(grid);

        if (!allowed) {
            // ❌ disable trigger
            trigger.classList.remove('text-danger');
            trigger.classList.add('text-secondary');
            trigger.style.pointerEvents = 'none';
            trigger.style.opacity = '0.6';
            trigger.innerHTML = '<i class="ri-lock-line"></i> Not Available Yet';
        } else {
            // ✅ aktif
            trigger.classList.remove('text-secondary');
            trigger.classList.add('text-danger');
            trigger.style.pointerEvents = 'auto';
            trigger.style.opacity = '1';
            trigger.innerHTML = '<i class="ri-edit-2-line"></i> Click to Edit';

            trigger.onclick = function () {
                enableEditMode(gridId, null, triggerId);
            };
        }
    }

    function enableEditMode(gridId, inputId, triggerId) {
        const grid = document.getElementById(gridId);
        const reviewPeriod = parseInt(grid.dataset.reviewPeriod);

        // 🔥 current month
        const currentMonth = new Date().getMonth() + 1;

        grid.querySelectorAll('.month-box').forEach(box => {
            const input = box.querySelector('.input-compact');
            const fileInput = box.querySelector('.file-input-trigger');
            const label = box.querySelector('.btn-attach-mini');
            const month = parseInt(input.dataset.month);

            let isActive = false;

            // 🔹 RULE review period
            if (reviewPeriod === 1) {
                isActive = true;
            } else if (reviewPeriod === 2) {
                isActive = (month % 2 === 0);
            } else if (reviewPeriod === 3) {
                isActive = (month % 3 === 0);
            } else if (reviewPeriod === 6) {
                isActive = (month % 6 === 0);
            } else if (reviewPeriod === 12) {
                isActive = (month === 12);
            }

            // 🔥 RULE tambahan: hanya current & sebelumnya
            const isPastOrCurrent = month <= currentMonth;

            if (isActive && isPastOrCurrent) {
                // ✅ ACTIVE
                box.classList.remove('readonly-mode');
                box.classList.add('edit-mode-active');

                input.removeAttribute('readonly');
                fileInput.removeAttribute('disabled');

                label.style.display = 'block';
                label.classList.remove('disabled-attach');

            } else {
                // ❌ NON ACTIVE
                box.classList.remove('edit-mode-active');
                box.classList.add('readonly-mode');

                input.setAttribute('readonly', true);
                fileInput.setAttribute('disabled', true);

                label.style.display = 'none';
            }
        });

        // trigger UI
        const trigger = document.getElementById(triggerId);
        trigger.innerHTML = '<i class="ri-check-line"></i> Editable';
        trigger.classList.replace('text-danger', 'text-success');
        trigger.onclick = null;
    }

    window.addEventListener('load', function() {
        if (typeof hideLoader === 'function') { hideLoader(); }
        let loaders = document.querySelectorAll('.preloader, #preloader, .loader, #loader, #status, .loading');
        loaders.forEach(function(el) { el.style.display = 'none'; });
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.file-input-trigger').forEach(input => {
            input.addEventListener('change', function () {

                const label = document.getElementById(this.dataset.target);
                if (!label) return;

                const file = this.files[0];
                if (!file) return;

                const maxSize = 2 * 1024 * 1024; // 2MB

                if (file.size > maxSize) {
                    alert('File maksimal 2MB');

                    this.value = ''; // reset input
                    return;
                }

                const icon = label.querySelector('.icon-attach');
                const text = label.querySelector('.text-attach');

                if (this.files && this.files.length > 0) {
                    label.classList.remove('disabled-attach');
                    label.classList.add('has-file');

                    icon.className = 'ri-check-line icon-attach';
                    text.innerText = 'OK';

                    label.setAttribute('title', this.files[0].name);
                } else {
                    label.classList.remove('has-file');
                    label.classList.add('disabled-attach');

                    icon.className = 'ri-attachment-2 icon-attach';
                    text.innerText = 'FILE';
                }
            });
        });
    });

    function applyReviewPeriod(grid) {
        const reviewPeriod = parseInt(grid.dataset.reviewPeriod);

        grid.querySelectorAll('.month-box').forEach(box => {
            const input = box.querySelector('.input-compact');
            const fileInput = box.querySelector('.file-input-trigger');
            const month = parseInt(input.dataset.month);

            let isActive = false;

            // 🎯 RULE
            if (reviewPeriod === 1) {
                isActive = true;
            } else if (reviewPeriod === 2) {
                isActive = (month % 2 === 0);
            } else if (reviewPeriod === 3) {
                isActive = (month % 3 === 0);
            } else if (reviewPeriod === 6) {
                isActive = (month % 6 === 0);
            }

            if (isActive) {
                // ✅ AKTIF
                box.classList.remove('readonly-mode');
                box.classList.add('edit-mode-active');

                input.removeAttribute('readonly');
                fileInput.removeAttribute('disabled');
            } else {
                // ❌ NON AKTIF
                box.classList.remove('edit-mode-active');
                box.classList.add('readonly-mode');

                input.setAttribute('readonly', true);
                fileInput.setAttribute('disabled', true);

                // ⚠️ JANGAN HAPUS VALUE (biar history tetap tampil)
                // input.value = '';
            }
        });
    }

</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.mini-progress-bar').forEach(function (el) {
        setTimeout(() => {
            el.style.width = el.dataset.width;
        }, 100);
    });
});
</script>
@endpush