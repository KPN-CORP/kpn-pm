{{--
    Kartu KPI read-only untuk modal detail Appraisal (On Behalf).
    Tampilan sama dengan kartu KPI di halaman Review Appraisal (components/self-review.blade.php),
    tanpa input form.

    Variabel:
      $form      KPI hasil AppService::combineFormData (index numerik => KPI goal + achievement/percentage)
      $tracking  KPIService::achievementTracking(): [index KPI => [1..12 => nilai bulanan]]

    Achievement & % diambil dari $form (nilai yang dipakai untuk score), bukan dihitung ulang,
    supaya kartu selalu sama dengan "KPI | Score" di atasnya.
--}}
<style>
    .kpi-review-card { border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden; }
    .kpi-review-card .kpi-label { color: #9e2a2b; font-size: 0.7rem; letter-spacing: 0.5px; }
    .kpi-review-card .read-only-month {
        background-color: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 10px 4px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    /* Modal lebih sempit dari halaman review: 12 bulan tetap satu baris di desktop, 6 per baris di layar kecil */
    .kpi-review-card .kpi-month-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 0.5rem; }
    @media (min-width: 992px) {
        .kpi-review-card .kpi-month-grid { grid-template-columns: repeat(12, minmax(0, 1fr)); }
    }
    .kpi-review-card .mini-progress { width: 100%; height: 18px; background: #e9ecef; border-radius: 10px; overflow: hidden; }
    .kpi-review-card .mini-progress-bar { height: 100%; border-radius: 10px; background: #AB2F2B; }
    .kpi-review-card .mini-progress-text {
        position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        font-size: .65rem; color: #fff; z-index: 2;
        text-shadow: -1px -1px 0 #9e2a2b, 1px -1px 0 #9e2a2b, -1px 1px 0 #9e2a2b, 1px 1px 0 #9e2a2b, 0 0 3px rgba(0,0,0,.35);
    }
</style>
@php
    $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];

    // Label Review Period / Calc Method dari resources/goal.json, sama seperti halaman Review Appraisal
    $goalOptions = json_decode(@file_get_contents(resource_path('goal.json')) ?: '[]', true) ?: [];
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

    // Format angka: 1,000 dan 0.50
    $formatNumber = function ($value) {
        if (!is_numeric($value)) {
            return $value ?? '-';
        }
        return number_format((float) $value, fmod((float) $value, 1) == 0 ? 0 : 2);
    };

    $kpis = collect($form)->filter(fn ($data, $key) => is_numeric($key) && is_array($data));
@endphp
@forelse ($kpis as $index => $data)
    @php
        $uom = is_null($data['custom_uom'] ?? null) ? ($data['uom'] ?? '-') : $data['custom_uom'];
        $percent = (float) ($data['percentage'] ?? 0);
        $monthly = $tracking[$index] ?? ($data['ach'] ?? []);
    @endphp
    <div class="kpi-review-card mb-3 p-3 {{ $loop->even ? 'bg-light-subtle' : 'bg-white' }}">
        <div class="row g-3 mb-2">
            <div class="col-md-5">
                <small class="fw-bold text-uppercase d-block kpi-label mb-1">KPI {{ $index + 1 }}</small>
                <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem; white-space: pre-line;">{{ $data['kpi'] ?? '-' }}</h6>
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
                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['weightage'] ?? 0 }}%</span>
                    </div>
                    <div class="col-3">
                        <small class="fw-bold text-uppercase d-block kpi-label mb-1">{{ __('Achievement') }}</small>
                        <span class="fw-bold text-dark d-block mb-2" style="font-size: 0.95rem;">{{ $formatNumber($data['achievement'] ?? 0) }}</span>
                        <div class="mini-progress position-relative">
                            <div class="mini-progress-bar" style="width: {{ max(min($percent, 100), 0) }}%"></div>
                            <small class="mini-progress-text fw-semibold">{{ number_format($percent, 0) }}%</small>
                        </div>
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
            <h6 class="fw-bold text-uppercase kpi-label">{{ __('Achievement Tracking') }}</h6>
            <div class="kpi-month-grid">
                @foreach ($months as $monthNum => $monthLabel)
                    @php($value = $monthly[$monthNum] ?? null)
                    <div class="read-only-month pb-1">
                        <span class="text-uppercase fw-bold text-secondary d-block mb-1" style="font-size: 0.65rem;">{{ $monthLabel }}</span>
                        <span class="fw-bold text-dark" style="font-size: 1.1rem;">{{ $formatNumber($value) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@empty
    <p class="mb-0">No form data available.</p>
@endforelse
