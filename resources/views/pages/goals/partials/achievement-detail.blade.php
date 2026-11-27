{{--
    Isi modal "Achievement Details" untuk SATU goal.

    Dulu blok ini di-render inline untuk setiap baris di team-goal.blade.php:
    43 modal x ~67 KB = 2,9 MB dari total 3,75 MB halaman (77%), padahal user
    paling banyak membuka satu. Sekarang dimuat lewat AJAX saat modal dibuka
    (lihat TeamGoalController::achievementDetail dan route
    team-goals.achievement-detail), mengikuti pola yang sudah dipakai
    partial approval-history.

    Variabel yang diharapkan:
      $firstSubordinate         ApprovalRequest dengan relasi goal
      $formDataArr              goal->form_data_parsed
      $months                   [1 => 'Jan', ...]
      $reviewPeriodOption       opsi dari resources/goal.json
      $calculationMethodOption  opsi dari resources/goal.json
--}}
@php
    $months = $months ?? [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
    $appService = app(\App\Services\AppService::class);
    $reviewPeriodOption = $reviewPeriodOption ?? [];
    $calculationMethodOption = $calculationMethodOption ?? [];
@endphp
@if ($firstSubordinate->goal->achievement_status && $firstSubordinate->goal->achievement_status['approval_info'])
    <div class="alert alert-warning border-0 p-3 rounded-0 mb-0">
        <strong class="d-block mb-1" style="font-size: 0.85rem;"><i class="ri-feedback-line me-1"></i> Revision Notes:</strong>
        <span class="text-dark" style="font-size: 0.85rem;">{{ $firstSubordinate->goal->achievement_status['approval_info'] }}</span>
    </div>
@endif
@if(!empty($formDataArr) && is_array($formDataArr))
    @foreach ($formDataArr as $kpiIndex => $row)
    <div class="p-3 {{ $loop->last ? '' : 'border-bottom' }}">
        <div class="mb-3">
            <span class="badge bg-primary-subtle text-primary mb-2 px-2 py-1 fw-bold" style="font-size: 0.65rem;">KPI {{ $kpiIndex + 1 }}</span>
            <h6 class="fw-bold text-dark mb-1 lh-sm">{{ $row['kpi'] ?? '-' }}</h6>
            <p class="text-secondary mb-0" style="white-space: pre-line; font-size: 0.85rem; line-height: 1.5;">{{ $row['description'] ?? '-' }}</p>
        </div>
        
        <div class="row g-2 mb-3 bg-light p-2 rounded border border-light mx-0">
            <div class="col-6 col-md-2">
                <span class="text-uppercase d-block mb-1 col-label fw-semibold">Target</span>
                @php
                    // Target may be stored pre-formatted ("1,500", "1.000.000"),
                    // so normalise the separators before formatting it.
                    $rawTarget = trim((string) ($row['target'] ?? ''));
                    $targetValue = preg_match('/^\d[\d., ]*$/', $rawTarget)
                        ? $appService->normalizeTarget($rawTarget)
                        : null;
                @endphp
                <span class="fw-bold text-dark col-value">{{ $targetValue !== null
                            ? number_format(
                                $targetValue,
                                fmod($targetValue, 1) == 0 ? 0 : 2
                            )
                            : ($rawTarget !== '' ? $rawTarget : '-') }}</span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-uppercase d-block mb-1 col-label fw-semibold">UoM</span>
                <span class="fw-bold text-dark col-value">{{ (isset($row['uom']) && $row['uom'] !== 'Other') ? $row['uom'] : ($row['custom_uom'] ?? '-') }}</span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-uppercase d-block mb-1 col-label fw-semibold">Weightage</span>
                <span class="fw-bold text-dark col-value">{{ $row['weightage'] ?? '0' }}%</span>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-uppercase d-block mb-1 col-label fw-semibold">Type</span>
                <span class="fw-bold text-dark col-value">{{ $row['type'] ?? '-' }}</span>
            </div>
            <div class="col-3 col-sm-3">

                <small class="fw-bold text-uppercase d-block kpi-label mb-1">
                    Achievement
                </small>

                {{-- Actual Value --}}
                <div class="mb-2">
                    <span class="fw-bold text-dark"
                        style="font-size:1rem;">
                        {{ is_numeric($row['actual'] ?? null)
                            ? number_format(
                                (float)$row['actual'],
                                str_contains((string)$row['actual'], '.')
                                    ? 2
                                    : 0
                            )
                            : ($row['actual'] ?? '-')
                        }}
                    </span>

                </div>

                @php
                    $achievement = (float)($row['achievement'] ?? 0);

                    $percent = max(
                        min($achievement,100),
                        0
                    );

                    $progressClass =
                        $achievement >= 100 ? 'bg-success'
                        : ($achievement >= 80 ? 'bg-primary'
                        : ($achievement >= 50 ? 'bg-warning'
                        : 'bg-danger'));
                @endphp

                <div class="mini-progress position-relative">

                    <div
                        class="mini-progress-bar bg-primary {{ $progressClass }}"
                        data-width="{{ $percent }}%">
                    </div>

                    <small class="mini-progress-text fw-semibold">

                        {{ number_format(
                            $achievement,
                            0
                        ) }}%

                    </small>

                </div>

            </div>
            <div class="col-6 col-md-4 mt-2">
                <span class="text-uppercase d-block mb-1 col-label fw-semibold">Review Period</span>
                @php
                    $rv = $row['review_period'] ?? '';
                    $rvLabel = $rv ?: '-';
                    foreach ($reviewPeriodOption as $group) {
                        foreach ($group as $opt) {
                            if ((string)$rv === (string)($opt['value'] ?? '')) {
                                $rvLabel = $opt['label'];
                                break 2;
                            }
                        }
                    }
                @endphp
                <span class="fw-bold text-dark col-value">{{ $rvLabel }}</span>
            </div>
            <div class="col-12 col-md-8 mt-2">
                <span class="text-uppercase d-block mb-1 col-label fw-semibold">Calc Method</span>
                @php
                    $rvCalc = $row['calculation_method'] ?? '';
                    $rvCalcLabel = $rvCalc ?: '-';
                    foreach ($calculationMethodOption as $group) {
                        foreach ($group as $opt) {
                            if ((string)$rvCalc === (string)($opt['value'] ?? '')) {
                                $rvCalcLabel = $opt['label'];
                                break 2;
                            }
                        }
                    }
                @endphp
                <span class="fw-bold text-dark col-value">{{ $rvCalcLabel }}</span>
            </div>
        </div>

        <div>
            <h6 class="fw-bold text-uppercase mb-2 text-primary" style="font-size: 0.7rem; letter-spacing: 0.5px;"><i class="ri-bar-chart-box-line me-1"></i> Tracking</h6>
            <div class="month-tracking-container">
                @foreach($months as $monthNum => $monthLabel)
                    @php
                        $value = $row['ach'][$monthNum] ?? null;
                        $file = $row['attachment'][$monthNum] ?? null;
                    @endphp
                    <div class="read-only-month {{ $value ? 'has-value' : '' }}">
                        <span class="text-uppercase fw-bold text-secondary d-block mb-1" style="font-size: 0.6rem;">{{ $monthLabel }}</span>
                        <span class="fw-bold text-dark d-block" style="font-size: 0.95rem;">
                            {{
                                is_numeric($value ?? null)
                                    ? number_format(
                                        (float)$value,

                                        (
                                            fmod((float)$value, 1) == 0
                                        )
                                            ? 0
                                            : 2
                                    )
                                    : ($value ?? '-')
                            }}
                        </span>
                        @if($file)
                            <a href="{{ asset('storage/'.$file) }}" target="_blank" class="d-block mt-2 text-primary fw-bold border border-primary rounded text-decoration-none bg-white" style="font-size: 0.55rem; padding: 2px;">FILE</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
@else
    <div class="py-5 text-center text-muted">
        <i class="ri-inbox-2-line text-secondary opacity-50 d-block mb-2" style="font-size: 3rem;"></i>
        <h6 class="fw-bold text-secondary">No details available.</h6>
    </div>
@endif
