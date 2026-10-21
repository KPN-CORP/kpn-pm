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
                <h6 class="fw-bold text-uppercase kpi-label">
                    {{ __('Achievement Tracking') }}
                    @if ($editableAchievement)
                        <span class="text-secondary fw-normal ms-1" style="letter-spacing: 0; text-transform: none;">({{ __('default from goal achievement, editable') }})</span>
                    @endif
                </h6>
                <div class="kpi-month-grid">
                    @foreach ($months as $monthNum => $monthLabel)
                        @php
                            $value = $data['ach'][$monthNum] ?? null;
                            $isTracked = \App\Services\KPIService::isTrackedMonth($monthNum, $data['review_period'] ?? null);
                        @endphp
                        <div>
                            @if ($editableAchievement && $isTracked)
                                <div class="edit-month pb-1">
                                    <label for="ach-{{ $formIndex }}-{{ $index }}-{{ $monthNum }}" class="text-uppercase fw-bold text-secondary d-block mb-1" style="font-size: 0.65rem;">{{ $monthLabel }}</label>
                                    {{-- Angka diformat 1,500.25; koma dibuang lagi di server (KPIService::cleanMonthValue) --}}
                                    <input type="text" inputmode="decimal" autocomplete="off"
                                        id="ach-{{ $formIndex }}-{{ $index }}-{{ $monthNum }}"
                                        name="formData[{{ $formIndex }}][{{ $index }}][ach][{{ $monthNum }}]"
                                        class="ach-month-input"
                                        placeholder="-"
                                        data-month="{{ $monthNum }}"
                                        data-original="{{ is_numeric($value) ? (float) $value : '' }}"
                                        value="{{ is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 2), '0'), '.') : '' }}">
                                </div>
                            @else
                                <div class="read-only-month pb-1" data-month="{{ $monthNum }}" data-value="{{ is_numeric($value) ? (float) $value : '' }}">
                                    <span class="text-uppercase fw-bold text-secondary d-block mb-1" style="font-size: 0.65rem;">{{ $monthLabel }}</span>
                                    <span class="fw-bold text-dark" style="font-size: 1.1rem;">{{ $formatNumber($value) }}</span>
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
            })();
        </script>
        @endpush
    @endonce
@endif
