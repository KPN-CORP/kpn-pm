<<<<<<< Updated upstream
=======
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
    /* Bulan yang masuk review period: border tebal supaya jelas kotak mana yang harus diisi */
    .kpi-review-card .month-eligible {
        border: 2px solid #AB2F2B;
        box-shadow: 0 0 0 2px rgba(171, 47, 43, 0.08);
    }
    .kpi-review-card .month-eligible .month-name { color: #AB2F2B !important; }
    .kpi-review-card .ach-file { padding-top: 6px; }
    .kpi-review-card .ach-file-btn {
        display: block;
        width: 100%;
        font-size: 0.6rem;
        line-height: 1.5;
        padding: 1px 2px;
        border: 1px solid #dee2e6;
        border-radius: 3px;
        background: #fff;
        color: #6c757d;
        cursor: pointer;
        text-decoration: none;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .kpi-review-card .ach-file-btn:hover { border-color: #AB2F2B; color: #AB2F2B; }
    .kpi-review-card .ach-file-btn.has-file { background: #198754; border-color: #198754; color: #fff; }
    .kpi-review-card .ach-file-view { border-color: #0dcaf0; color: #0a97b0; }
    .kpi-review-card .ach-file-view:hover { border-color: #0a97b0; color: #0a97b0; }
    .kpi-review-card .ach-file-remove {
        position: absolute;
        top: -7px;
        right: -6px;
        width: 17px;
        height: 17px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: #dc3545;
        color: #fff;
        font-size: 11px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5;
    }
    /* 12 bulan satu baris di layar lebar, otomatis turun baris kalau kotak terlalu sempit untuk angka */
    .kpi-review-card .kpi-month-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(84px, 1fr)); gap: 0.5rem; }
    /* kotak bulan tetap sama tinggi walau ada yang punya tombol attachment */
    .kpi-review-card .kpi-month-grid > div { display: flex; }
    .kpi-review-card .kpi-month-grid > div > div { width: 100%; display: flex; flex-direction: column; }
    .kpi-review-card .ach-file { margin-top: auto; }
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
>>>>>>> Stashed changes
<div class="form-group mb-2">
    <h4 class="mb-3">
        Objektif Kerja
    </h4>
    <input type="hidden" name="formData[{{ $formIndex }}][formName]" value="{{ $name }}">
    @forelse ($goalData as $index => $data)
    <div class="row">
        <div class="card col-md-12 mb-2 p-0 border border-primary bg-light-subtle">
            <div class="card-header">
                <h4>{{ __('Goal') }} {{ $index + 1 }}</h4>
            </div>
<<<<<<< Updated upstream
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-4 mb-3">
                        <div class="form-group">
                            <label class="form-label" for="kpi">KPI</label>
                            <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['kpi'] }}</p>
=======
            <div>
                <h6 class="fw-bold text-uppercase kpi-label mb-1">
                    {{ __('Achievement Tracking') }}
                    @if ($editableAchievement)
                        <span class="text-secondary fw-normal ms-1" style="letter-spacing: 0; text-transform: none;">({{ __('default from goal achievement, editable') }})</span>
                    @endif
                </h6>
                <span class="text-muted d-block mb-2" style="font-size: 0.7rem;">
                    <i class="ri-information-line align-middle me-1"></i>
                    {{ __('Months with a bold border follow this KPI review period and are the ones that need to be filled.') }}
                    @if ($editableAchievement)
                        {{ __('Each month can have one supporting file, max 2MB.') }}
                    @endif
                </span>
                <div class="kpi-month-grid">
                    @foreach ($months as $monthNum => $monthLabel)
                        @php
                            $value = $data['ach'][$monthNum] ?? null;
                            $isTracked = \App\Services\KPIService::isTrackedMonth($monthNum, $data['review_period'] ?? null);

                            // Attachment bisa berasal dari goal achievement (folder kpi-achievements)
                            // atau dari form appraisal (folder appraisal-achievements); keduanya disk 'public'.
                            $attachment = $data['ach_attachment'][$monthNum] ?? null;
                            $attachmentUrl = $attachment
                                ? (str_starts_with($attachment, 'storage/') ? asset($attachment) : asset('storage/' . $attachment))
                                : null;
                            $canAttach = $editableAchievement && $isTracked;
                            $fileId = "ach-file-{$formIndex}-{$index}-{$monthNum}";
                        @endphp
                        <div>
                            @if ($editableAchievement && $isTracked)
                                <div class="edit-month month-eligible pb-1">
                                    <label for="ach-{{ $formIndex }}-{{ $index }}-{{ $monthNum }}" class="month-name text-uppercase fw-bold text-secondary d-block mb-1" style="font-size: 0.65rem;">{{ $monthLabel }}</label>
                                    {{-- Angka diformat 1,500.25; koma dibuang lagi di server (KPIService::cleanMonthValue) --}}
                                    <input type="text" inputmode="decimal" autocomplete="off"
                                        id="ach-{{ $formIndex }}-{{ $index }}-{{ $monthNum }}"
                                        name="formData[{{ $formIndex }}][{{ $index }}][ach][{{ $monthNum }}]"
                                        class="ach-month-input"
                                        placeholder="-"
                                        data-month="{{ $monthNum }}"
                                        data-original="{{ is_numeric($value) ? (float) $value : '' }}"
                                        value="{{ is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 2), '0'), '.') : '' }}">
                                    @include('components.partials.achievement-attachment', [
                                        'attachmentUrl' => $attachmentUrl,
                                        'canAttach' => $canAttach,
                                        'fileId' => $fileId,
                                        'kpiIndex' => $index,
                                        'monthNum' => $monthNum,
                                        'monthLabel' => $monthLabel,
                                    ])
                                </div>
                            @else
                                <div class="read-only-month {{ $isTracked ? 'month-eligible' : '' }} pb-1" data-month="{{ $monthNum }}" data-value="{{ is_numeric($value) ? (float) $value : '' }}">
                                    <span class="month-name text-uppercase fw-bold text-secondary d-block mb-1" style="font-size: 0.65rem;">{{ $monthLabel }}</span>
                                    <span class="fw-bold text-dark" style="font-size: 1.1rem;">{{ $formatNumber($value) }}</span>
                                    @include('components.partials.achievement-attachment', [
                                        'attachmentUrl' => $attachmentUrl,
                                        'canAttach' => false,
                                        'fileId' => $fileId,
                                        'kpiIndex' => $index,
                                        'monthNum' => $monthNum,
                                        'monthLabel' => $monthLabel,
                                    ])
                                </div>
                            @endif
>>>>>>> Stashed changes
                        </div>
                    </div>
                    <div class="col-lg-2 mb-3">
                        <div class="form-group">
                            <label class="form-label" for="weightage">{{ __('Weightage') }}</label>
                            <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['weightage'] }}%</p>
                        </div>
                    </div>
                    <div class="col-lg-2 mb-3">
                        <div class="form-group">
                            <label class="form-label" for="type">{{ __('Type') }}</label>
                            <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['type'] }}</p>
                        </div>
                    </div>
                    <div class="col-lg-2 mb-3">
                        <div class="form-group">
                            <label class="form-label" for="target">{{ __('Target In UoM') }} {{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }}</label>
                            <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['target'] }}</p>
                        </div>
                    </div>
                    <div class="col-lg-2 mb-3">
                        <div class="form-group">
                            <label class="form-label" for="target">{{ __('Achievement In') }} {{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }}</label>
                            <input type="number" id="achievement-{{ $index + 1 }}" name="formData[{{ $formIndex }}][{{ $index }}][achievement]" placeholder="{{ __('Enter Achievement') }}.." value="{{ isset($data['actual']) ? $data['actual'] : "" }}" class="form-control achievement mt-1" />
                            <div class="text-danger error-message"></div>
                        </div>
                    </div>
                </div>
                <hr class="mt-0 mb-2">
                <div class="row">
                    <div class="col-md mb-2">
                        <div class="form-group
                        ">
                            <label class="form-label
                            " for="description">Description</label>
                            <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['description'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
        <p>No form data available.</p>
    @endforelse
<<<<<<< Updated upstream
</div>
=======
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
                    card.querySelectorAll('.read-only-month[data-value]').forEach(box => {
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

                    recalc(input.closest('.kpi-review-card'));
                });

                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('.ach-month-input').forEach(fitInput);
                });

                // Attachment per bulan: batas 2MB, sama seperti halaman Update Achievement
                const maxFileSize = {{ \App\Services\AppraisalAchievementAttachmentService::MAX_KILOBYTES }} * 1024;
                const allowedExt = @json(explode(',', \App\Services\AppraisalAchievementAttachmentService::ACCEPTED_MIMES));

                function rejectFile(input, title, text) {
                    input.value = '';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'warning', title: title, text: text });
                    } else {
                        alert(text);
                    }
                }

                document.addEventListener('change', function (e) {
                    const input = e.target;
                    if (!input.classList.contains('ach-file-input')) return;

                    const label = document.getElementById(input.dataset.target);
                    const file = input.files && input.files[0];

                    if (!label) return;

                    if (!file) {
                        label.classList.remove('has-file');
                        label.querySelector('.icon-attach').className = 'ri-attachment-2 icon-attach';
                        label.querySelector('.text-attach').innerText = 'FILE';
                        return;
                    }

                    if (file.size > maxFileSize) {
                        rejectFile(input, 'File too large', 'Maximum file size is 2MB.');
                        return;
                    }

                    const ext = (file.name.split('.').pop() || '').toLowerCase();

                    if (!allowedExt.includes(ext)) {
                        rejectFile(input, 'File type not allowed', 'Allowed file types: ' + allowedExt.join(', ') + '.');
                        return;
                    }

                    label.classList.add('has-file');
                    label.querySelector('.icon-attach').className = 'ri-check-line icon-attach';
                    label.querySelector('.text-attach').innerText = 'OK';
                    label.setAttribute('title', file.name);
                });

                // Hapus attachment yang sudah tersimpan: tandai lewat hidden input, file dibuang di server
                document.addEventListener('click', function (e) {
                    const button = e.target.closest('.ach-remove-attachment');
                    if (!button) return;

                    e.preventDefault();

                    if (!confirm('Remove this attachment?')) return;

                    const form = button.closest('form');
                    const wrapper = button.closest('[data-attachment-wrapper]');

                    if (form) {
                        const flag = document.createElement('input');
                        flag.type = 'hidden';
                        flag.name = '{{ \App\Services\AppraisalAchievementAttachmentService::REMOVE_INPUT }}[' + button.dataset.kpiIndex + '][' + button.dataset.month + ']';
                        flag.value = '1';
                        form.appendChild(flag);
                    }

                    const box = button.closest('.edit-month');
                    const label = box ? box.querySelector('label.ach-file-btn') : null;

                    if (label) {
                        label.classList.remove('mt-1');
                        if (!label.classList.contains('has-file')) {
                            label.querySelector('.text-attach').innerText = 'FILE';
                        }
                    }

                    if (wrapper) wrapper.remove();
                });
            })();
        </script>
        @endpush
    @endonce
@endif
>>>>>>> Stashed changes
