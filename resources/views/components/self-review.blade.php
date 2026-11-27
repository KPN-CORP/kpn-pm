{{--
    Form KPI appraisal. Tampilan mengikuti kartu KPI di halaman My Goal.

    Achievement tracking per bulan default dari goal achievement (lihat KPIService::achievementTracking),
    bisa diedit kalau $editableAchievement = true (My Appraisal create/edit). Nilai Achievement dihitung
    ulang di server dari tracking (KPIService::applyAchievementTracking); hitungan JS di bawah hanya preview.
--}}
@once
<style>
    .kpi-review-card { border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden; }
    .kpi-review-card .kpi-label { color: #9e2a2b; font-size: 0.7rem; letter-spacing: 0.5px; }
    .kpi-review-card .read-only-month,
    .kpi-review-card .edit-month {
        background-color: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 10px 4px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .kpi-review-card .edit-month { border-color: #e4a9a7; }
    /* Kotak bulan editable dengan upload file, tampilan sama dengan halaman Update Achievement goal */
    .kpi-review-card .month-box { border-radius: 6px; padding: 4px; text-align: center; height: 100%; }
    .kpi-review-card .month-box.edit-mode-active { border: 1px solid #dc3545; background-color: #fffcfc; box-shadow: 0 0 5px rgba(220, 53, 69, 0.15); }
    .kpi-review-card .month-box.readonly-mode { border: 1px solid #dee2e6; background-color: #e9ecef; opacity: 0.8; }
    .kpi-review-card .month-box .month-label { font-size: 0.65rem; padding: 2px 0; }
    .kpi-review-card .month-box .ach-month-input { border-bottom: 0; padding: 0.2rem 0; }
    .kpi-review-card .month-box .ach-month-input.is-invalid { border-bottom: 1px solid #dc3545; }
    .kpi-review-card .month-box .read-value { display: block; font-weight: 600; font-size: 1.1rem; color: #6c757d; padding: 0.2rem 0; }
    .kpi-review-card .btn-attach-mini {
        display: block; width: 100%; margin: 4px 0 0; padding: 2px 0; font-size: 0.65rem; border-radius: 3px;
        cursor: pointer; background-color: #fff; border: 1px solid #dee2e6; color: #6c757d; text-decoration: none; transition: all 0.2s;
    }
    .kpi-review-card .btn-attach-mini.disabled-attach { cursor: not-allowed; background-color: #f8f9fa; opacity: 0.6; }
    .kpi-review-card .btn-attach-mini.has-file { background-color: #198754; border-color: #198754; color: #fff; }
    .kpi-review-card .btn-attach-mini.view-attach { border-color: #0d6efd; color: #0d6efd; }
    .kpi-review-card .btn-remove-attach {
        position: absolute; top: -8px; right: -5px; width: 18px; height: 18px; padding: 0; border-radius: 50%;
        font-size: 12px; line-height: 1; display: flex; align-items: center; justify-content: center; z-index: 5;
    }
    /* 12 bulan satu baris di layar lebar, otomatis turun baris kalau kotak terlalu sempit untuk angka */
    .kpi-review-card .kpi-month-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(84px, 1fr)); gap: 0.5rem; }
    .kpi-review-card .ach-month-input {
        width: 100%;
        border: 0;
        border-bottom: 1px dashed #ced4da;
        background: transparent;
        text-align: center;
        font-weight: 700;
        font-size: 1.1rem;
        color: #212529;
        padding: 0;
    }
    .kpi-review-card .ach-month-input:focus { outline: none; border-bottom-color: #AB2F2B; }
    .kpi-review-card .ach-month-input.is-changed { color: #AB2F2B; }
    .kpi-review-card .mini-progress { width: 100%; height: 18px; background: #e9ecef; border-radius: 10px; overflow: hidden; }
    .kpi-review-card .mini-progress-bar { height: 100%; border-radius: 10px; background: linear-gradient(90deg, #AB2F2B 25%, #d96865 50%, #AB2F2B 75%); background-size: 200% 100%; animation: kpiProgressFlow 1.5s linear infinite; }
    @keyframes kpiProgressFlow { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    .kpi-review-card .mini-progress-text {
        position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        font-size: .65rem; color: #fff; z-index: 2;
        text-shadow: -1px -1px 0 #9e2a2b, 1px -1px 0 #9e2a2b, -1px 1px 0 #9e2a2b, 1px 1px 0 #9e2a2b, 0 0 3px rgba(0,0,0,.35);
    }
</style>
@endonce
<div class="form-group mb-2">
    <h4 class="mb-3">
        Objektif Kerja
    </h4>
    <input type="hidden" name="formData[{{ $formIndex }}][formName]" value="{{ $name }}">
    @php
        $editableAchievement = $editableAchievement ?? false;
        $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];

        // Label Review Period / Calc Method dari resources/goal.json, sama seperti halaman My Goal
        $goalOptions = json_decode(file_get_contents(resource_path('goal.json')), true);
        $optionLabel = function ($options, $value) {
            foreach ($options ?? [] as $group) {
                foreach ($group as $opt) {
                    if ((string) $value === (string) ($opt['value'] ?? '')) {
                        return $opt['label'];
                    }
                }
            }
            return $value ?: '-';
        };

        // Format angka sama seperti halaman My Goal: 1,000 dan 0.50
        $formatNumber = function ($value) {
            if (!is_numeric($value)) {
                return $value ?? '-';
            }
            return number_format((float) $value, fmod((float) $value, 1) == 0 ? 0 : 2);
        };
    @endphp
    @forelse ($goalData as $index => $data)
        @php
            $uom = is_null($data['custom_uom'] ?? null) ? ($data['uom'] ?? '-') : $data['custom_uom'];
            $actual = is_scalar($data['actual'] ?? null) ? $data['actual'] : 0;
            $percent = (float) ($data['achievement_percent'] ?? 0);
        @endphp
        <div class="kpi-review-card mb-3 p-3 {{ $loop->even ? 'bg-light-subtle' : 'bg-white' }}"
            data-kpi-index="{{ $index }}"
            data-target="{{ (float) ($data['target'] ?? 0) }}"
            data-type="{{ $data['type'] ?? 'Higher Better' }}"
            data-calc="{{ $data['calculation_method'] ?? 'last' }}"
            data-review-period="{{ $data['review_period'] ?? '' }}">
            <div class="row g-3 mb-2">
                <div class="col-md-5">
                    <small class="fw-bold text-uppercase d-block kpi-label mb-1">KPI {{ $index + 1 }}</small>
                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">{{ $data['kpi'] }}</h6>
                    <p class="text-secondary mb-0" style="white-space: pre-line; font-size: 0.75rem; line-height: 1.5;">{{ $data['description'] ?? '-' }}</p>
                </div>
                <div class="col-md-7">
                    <div class="row g-3 mb-3">
                        <div class="col-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Target</small>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $formatNumber($data['target'] ?? null) }}</span>
                        </div>
                        <div class="col-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">UoM</small>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $uom }}</span>
                        </div>
                        <div class="col-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">{{ __('Weightage') }}</small>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['weightage'] }}%</span>
                        </div>
                        <div class="col-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">{{ __('Achievement') }}</small>
                            <span class="fw-bold text-dark d-block mb-2 kpi-actual" style="font-size: 0.95rem;">{{ $formatNumber($actual) }}</span>
                            <div class="mini-progress position-relative">
                                <div class="mini-progress-bar kpi-progress-bar" style="width: {{ max(min($percent, 100), 0) }}%"></div>
                                <small class="mini-progress-text fw-semibold kpi-progress-text">{{ number_format($percent, 0) }}%</small>
                            </div>
                            <input type="hidden" id="achievement-{{ $index + 1 }}" name="formData[{{ $formIndex }}][{{ $index }}][achievement]" value="{{ $actual }}" />
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">{{ __('Type') }}</small>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['type'] ?? '-' }}</span>
                        </div>
                        <div class="col-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Review Period</small>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $optionLabel($goalOptions['Review Period'] ?? [], $data['review_period'] ?? '') }}</span>
                        </div>
                        <div class="col-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Calc Method</small>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $optionLabel($goalOptions['Calculation Method'] ?? [], $data['calculation_method'] ?? '') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                @if ($editableAchievement)
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-1 fs-6">{{ __('Achievement Tracking') }}</h6>
                            <span class="text-muted d-block" style="font-size: 0.7rem;"><i class="ri-information-line align-middle me-1"></i>Max upload file: 2MB</span>
                        </div>
                        <span class="text-success fw-bold mb-1"><i class="ri-check-line"></i> {{ __('Editable') }}</span>
                    </div>
                @else
                    <h6 class="fw-bold text-uppercase kpi-label">{{ __('Achievement Tracking') }}</h6>
                @endif
                <div class="kpi-month-grid">
                    @foreach ($months as $monthNum => $monthLabel)
                        @php
                            $value = $data['ach'][$monthNum] ?? null;
                            $file = $data['ach_file'][$monthNum] ?? null;
                            $isTracked = \App\Services\KPIService::isTrackedMonth($monthNum, $data['review_period'] ?? null);
                            $fileId = "ach-file-{$formIndex}-{$index}-{$monthNum}";
                        @endphp
                        <div>
                            @if ($editableAchievement)
                                <div class="month-box position-relative {{ $isTracked ? 'edit-mode-active' : 'readonly-mode' }}"
                                    @unless ($isTracked) data-month="{{ $monthNum }}" data-value="{{ is_numeric($value) ? (float) $value : '' }}" @endunless>
                                    <label @if ($isTracked) for="ach-{{ $formIndex }}-{{ $index }}-{{ $monthNum }}" @endif
                                        class="month-label text-uppercase fw-bold text-secondary d-block mb-1 rounded bg-white border">{{ $monthLabel }}</label>
                                    @if ($isTracked)
                                        {{-- Angka diformat 1,500.25; koma dibuang lagi di server (KPIService::cleanMonthValue) --}}
                                        <input type="text" inputmode="decimal" autocomplete="off"
                                            id="ach-{{ $formIndex }}-{{ $index }}-{{ $monthNum }}"
                                            name="formData[{{ $formIndex }}][{{ $index }}][ach][{{ $monthNum }}]"
                                            class="ach-month-input"
                                            placeholder="-"
                                            data-month="{{ $monthNum }}"
                                            data-original="{{ is_numeric($value) ? (float) $value : '' }}"
                                            value="{{ is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 2), '0'), '.') : '' }}">

                                        <input type="file" id="{{ $fileId }}" name="kpi_attachment[{{ $index }}][{{ $monthNum }}]"
                                            class="d-none ach-file-input" data-month-label="KPI {{ $index + 1 }} - {{ $monthLabel }}"
                                            accept=".pdf,.png,.jpg,.jpeg,.xlsx,.xls,.csv,.docx,.pptx">
                                        <label for="{{ $fileId }}" class="btn-attach-mini" title="{{ __('Upload file') }}">
                                            <i class="ri-attachment-2 icon-attach"></i> <span class="text-attach">FILE</span>
                                        </label>
                                    @else
                                        <span class="read-value">{{ $formatNumber($value) }}</span>
                                        <span class="btn-attach-mini disabled-attach">FILE</span>
                                    @endif

                                    @if ($file)
                                        <div class="position-relative ach-file-view">
                                            <a href="{{ asset('storage/'.$file) }}" target="_blank" class="btn-attach-mini view-attach" title="{{ __('View Attachment') }}">
                                                <i class="ri-file-line"></i> VIEW
                                            </a>
                                            @if ($isTracked)
                                                <button type="button" class="btn btn-sm btn-danger btn-remove-attach"
                                                    data-remove-name="kpi_attachment_remove[{{ $index }}][{{ $monthNum }}]" title="{{ __('Remove Attachment') }}">
                                                    <i class="ri-delete-bin-fill"></i>
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="read-only-month pb-1" data-month="{{ $monthNum }}" data-value="{{ is_numeric($value) ? (float) $value : '' }}">
                                    <span class="text-uppercase fw-bold text-secondary d-block mb-1" style="font-size: 0.65rem;">{{ $monthLabel }}</span>
                                    <span class="fw-bold text-dark" style="font-size: 1.1rem;">{{ $formatNumber($value) }}</span>
                                    @if ($file)
                                        <a href="{{ asset('storage/'.$file) }}" target="_blank" class="btn-attach-mini view-attach mt-1"><i class="ri-file-line"></i> VIEW</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <p>No form data available.</p>
    @endforelse
</div>
@if ($editableAchievement ?? false)
    @once
        @push('scripts')
        <script>
            // Preview Achievement saat tracking bulanan diubah. Mirror KPIService::aggregate/achievement.
            (function () {
                const averageDivisor = { 1: 12, 2: 6, 3: 4, 6: 2, 12: 1 };

                function aggregate(method, values, reviewPeriod) {
                    if (!values.length) return 0;
                    switch (method) {
                        case 'average': return values.reduce((a, b) => a + b, 0) / (averageDivisor[reviewPeriod] || values.length);
                        case 'sum': return values.reduce((a, b) => a + b, 0);
                        case 'last': return values[values.length - 1];
                        case 'max': return Math.max(...values);
                        case 'min': return Math.min(...values);
                        default: return 0;
                    }
                }

                function achievement(actual, target, type) {
                    if (!target) return 0;
                    if (type === 'Higher Better') return actual / target * 100;
                    if (type === 'Lower Better') return target / Math.max(actual, 1) * 100;
                    if (type === 'Exact Value') return actual === target ? 100 : 0;
                    return 0;
                }

                function format(value) {
                    return value.toLocaleString('en-US', { maximumFractionDigits: 2, minimumFractionDigits: value % 1 === 0 ? 0 : 2 });
                }

                // Hanya angka dan satu titik desimal (maks 2 digit), ribuan diberi koma: 1,500.25
                function formatInput(value) {
                    const clean = String(value).replace(/[^0-9.]/g, '');
                    if (clean === '') return '';
                    const [int, ...rest] = clean.split('.');
                    const intPart = int === '' ? '0' : parseInt(int, 10).toLocaleString('en-US');
                    return rest.length ? intPart + '.' + rest.join('').slice(0, 2) : intPart;
                }

                // Kotak bulan sempit: kecilkan font untuk angka panjang supaya tidak terpotong
                function fitInput(input) {
                    const len = input.value.length;
                    input.style.fontSize = len > 10 ? '0.75rem' : (len > 7 ? '0.9rem' : '');
                }

                function parseInput(value) {
                    const clean = String(value).replace(/,/g, '');
                    return clean === '' || isNaN(clean) ? null : parseFloat(clean);
                }

                function recalc(card) {
                    const inputs = card.querySelectorAll('.ach-month-input');
                    const tracked = {};
                    inputs.forEach(input => {
                        const value = parseInput(input.value);
                        const original = input.dataset.original === '' ? null : parseFloat(input.dataset.original);
                        input.classList.toggle('is-changed', value !== original);
                        if (value !== null) tracked[+input.dataset.month] = value;
                    });
                    // bulan read-only (di luar review period) tetap ikut dihitung, sama seperti server
                    card.querySelectorAll('.read-only-month[data-value], .month-box.readonly-mode[data-value]').forEach(box => {
                        if (box.dataset.value !== '') tracked[+box.dataset.month] = parseFloat(box.dataset.value);
                    });
                    const values = Object.keys(tracked).sort((a, b) => a - b).map(m => tracked[m]);
                    const actual = Math.round(aggregate(card.dataset.calc, values, parseInt(card.dataset.reviewPeriod)) * 100) / 100;
                    const percent = achievement(actual, parseFloat(card.dataset.target) || 0, card.dataset.type);

                    card.querySelector('.kpi-actual').textContent = format(actual);
                    card.querySelector('.kpi-progress-bar').style.width = Math.max(Math.min(percent, 100), 0) + '%';
                    card.querySelector('.kpi-progress-text').textContent = Math.round(percent) + '%';
                    card.querySelector('input[id^="achievement-"]').value = actual;
                }

                document.addEventListener('input', function (e) {
                    const input = e.target;
                    if (!input.classList.contains('ach-month-input')) return;

                    // format ulang sambil menjaga posisi kursor relatif dari kanan
                    const fromEnd = input.value.length - input.selectionStart;
                    input.value = formatInput(input.value);
                    const pos = Math.max(input.value.length - fromEnd, 0);
                    input.setSelectionRange(pos, pos);
                    fitInput(input);
                    input.classList.remove('is-invalid');

                    recalc(input.closest('.kpi-review-card'));
                });

                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('.ach-month-input').forEach(fitInput);
                });

                // ---------- File achievement bulanan ----------
                const MAX_FILE = 2 * 1024 * 1024;
                const ALLOWED_EXT = ['pdf', 'png', 'jpg', 'jpeg', 'xlsx', 'xls', 'csv', 'docx', 'pptx'];

                function setFileLabel(input, file) {
                    const label = input.parentElement.querySelector('label[for="' + input.id + '"]');
                    if (!label) return;
                    label.classList.toggle('has-file', !!file);
                    label.querySelector('.icon-attach').className = (file ? 'ri-check-line' : 'ri-attachment-2') + ' icon-attach';
                    label.querySelector('.text-attach').textContent = file ? 'OK' : 'FILE';
                    label.title = file ? file.name : '';
                }

                document.addEventListener('change', function (e) {
                    const input = e.target;
                    if (!input.classList.contains('ach-file-input')) return;

                    const file = input.files[0] || null;
                    if (file) {
                        const ext = file.name.split('.').pop().toLowerCase();
                        let error = null;
                        if (file.size > MAX_FILE) error = 'Max file size is 2MB.';
                        else if (!ALLOWED_EXT.includes(ext)) error = 'File must be PDF/JPG/PNG/XLSX/XLS/CSV/DOCX/PPTX.';
                        if (error) {
                            input.value = '';
                            setFileLabel(input, null);
                            showLocalPreview(input, null);
                            Swal.fire({ icon: 'error', title: input.dataset.monthLabel, text: error });
                            return;
                        }
                    }
                    setFileLabel(input, file);
                    showLocalPreview(input, file);
                });

                // File baru belum tersimpan (diupload saat Save Draft / Submit): tampilkan VIEW lokal supaya
                // user bisa cek filenya. File tersimpan sebelumnya disembunyikan karena akan diganti.
                function showLocalPreview(input, file) {
                    const box = input.closest('.month-box');
                    const old = box.querySelector('.ach-file-new');
                    if (old) {
                        URL.revokeObjectURL(old.querySelector('a').href);
                        old.remove();
                    }
                    const saved = box.querySelector('.ach-file-view');
                    if (saved) saved.classList.toggle('d-none', !!file);
                    if (!file) return;

                    const wrap = document.createElement('div');
                    wrap.className = 'position-relative ach-file-new';
                    wrap.innerHTML =
                        '<a target="_blank" class="btn-attach-mini view-attach"><i class="ri-file-line"></i> VIEW</a>' +
                        '<button type="button" class="btn btn-sm btn-danger btn-remove-attach btn-clear-new" title="Cancel this file"><i class="ri-close-line"></i></button>' +
                        '<small class="d-block text-muted mt-1" style="font-size: 0.6rem;">Not saved yet</small>';
                    const link = wrap.querySelector('a');
                    link.href = URL.createObjectURL(file);
                    link.title = file.name + ' (saved on Save Draft / Submit)';
                    box.appendChild(wrap);
                }

                // Batalkan file baru yang dipilih (belum tersimpan)
                document.addEventListener('click', function (e) {
                    const btn = e.target.closest('.btn-clear-new');
                    if (!btn) return;
                    e.stopPropagation();
                    const input = btn.closest('.month-box').querySelector('.ach-file-input');
                    input.value = '';
                    setFileLabel(input, null);
                    showLocalPreview(input, null);
                }, true);

                // Hapus file tersimpan: kirim kpi_attachment_remove[i][m]=1 saat submit
                document.addEventListener('click', function (e) {
                    const btn = e.target.closest('.btn-remove-attach');
                    if (!btn) return;
                    Swal.fire({
                        title: 'Remove this attachment?',
                        icon: 'warning',
                        showCancelButton: true,
                        reverseButtons: true,
                        confirmButtonColor: '#AB2F2B',
                        confirmButtonText: 'Remove',
                    }).then(result => {
                        if (!result.isConfirmed) return;
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = btn.dataset.removeName;
                        hidden.value = '1';
                        const box = btn.closest('.month-box');
                        box.appendChild(hidden);
                        btn.closest('.ach-file-view').remove();
                    });
                });

                // File tanpa nilai achievement ditolak server; cek dulu sebelum tombol submit/draft (capture phase)
                document.addEventListener('click', function (e) {
                    if (!e.target.closest('.submit-user, .submit-draft')) return;

                    const missing = [];
                    document.querySelectorAll('.ach-file-input').forEach(input => {
                        if (!input.files || !input.files.length) return;
                        const valueInput = input.closest('.month-box').querySelector('.ach-month-input');
                        if (valueInput && valueInput.value.trim() === '') {
                            valueInput.classList.add('is-invalid');
                            missing.push(input.dataset.monthLabel);
                        }
                    });

                    if (missing.length) {
                        e.preventDefault();
                        e.stopPropagation();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Achievement value is required',
                            text: 'Please fill in the achievement value for: ' + missing.join(', '),
                        });
                    }
                }, true);
            })();
        </script>
        @endpush
    @endonce
@endif
