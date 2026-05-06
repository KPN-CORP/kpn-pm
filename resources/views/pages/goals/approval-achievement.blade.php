@extends('layouts_.vertical', ['page_title' => 'Approval Achievement'])

@section('css')
<style>
.version-header {
    padding: 8px 12px;
    border-radius: 4px;
    display: flex;
    align-items: center;
}

.header-before {
    background-color: #f1f3f5;
    border-left: 4px solid #6c757d;
}

.header-after {
    background-color: #e7f1ff;
    border-left: 4px solid #0d6efd;
}

.kpi-label {
    font-size: 0.65rem;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 2px;
    color: #6c757d;
}

.month-box {
    border-radius: 6px;
    padding: 6px;
    text-align: center;
    border: 1px solid #dee2e6;
    background-color: #fff;
}

.month-box-old {
    background-color: #f8f9fa;
    border-style: dashed;
    opacity: 0.8;
}

.month-label {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #6c757d;
    display: block;
    margin-bottom: 2px;
}

.month-input {
    width: 100%;
    border: none;
    background: transparent;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
    color: #212529;
    outline: none;
}

.month-input-old {
    color: #6c757d;
}

.btn-evid {
    font-size: 10px !important;
    padding: 2px !important;
    margin-top: 4px;
    display: block;
    width: 100%;
    line-height: 1.2;
}

.mini-progress {
    height: 4px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 4px;
}

.mini-progress-bar {
    height: 100%;
    border-radius: 10px;
    background: linear-gradient(
        90deg,
        #0d6efd 25%,
        #88c6f9 50%,
        #0d6efd 75%
    );
    background-size: 200% 100%;
    animation: progressFlow 1.5s linear infinite;
}
@keyframes progressFlow {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-body p-2 px-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="d-flex mb-1">
                        <div class="text-muted small fw-medium" style="width: 110px;">Employee Name</div>
                        <div class="fw-semibold small text-dark">
                            : {{ $employee->fullname ?? '-' }}
                        </div>
                    </div>
                    <div class="d-flex mb-1">
                        <div class="text-muted small fw-medium" style="width: 110px;">Employee ID</div>
                        <div class="fw-semibold small text-dark">: {{ $employee->employee_id ?? '-' }}</div>
                    </div>
                    <div class="d-flex">
                        <div class="text-muted small fw-medium" style="width: 110px;">Job Level</div>
                        <div class="fw-semibold small text-dark">: {{ $employee->job_level ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex mb-1">
                        <div class="text-muted small fw-medium" style="width: 110px;">Business Unit</div>
                        <div class="fw-semibold small text-dark">: {{ $employee->group_company ?? '-' }}</div>
                    </div>
                    <div class="d-flex mb-1">
                        <div class="text-muted small fw-medium" style="width: 110px;">Division</div>
                        <div class="fw-semibold small text-dark">: {{ $employee->unit ?? '-' }}</div>
                    </div>
                    <div class="d-flex">
                        <div class="text-muted small fw-medium" style="width: 110px;">Designation</div>
                        <div class="fw-semibold small text-dark">: {{ $employee->designation ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="achievementApprovalForm" action="{{ route('goals.approval-achievement-approve') }}" method="post">
        @csrf

        <input type="hidden" name="goal_id" value="{{ $id }}" style="display: none" />
        <h6 class="fw-bold text-dark mb-3 mt-4">{{ __('Achievement Target') }} 2025</h6>

        @php
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        @endphp

        @foreach ($kpis as $i => $data)

        @if ($data['has_old_data'])

        <div class="p-3 mb-4 rounded shadow-sm" style="background-color: #f8f9fa; border: 1px solid #eef0f2;">

            <div class="row g-3">
                <div class="col-md-5 col-lg-5 mb-md-0">
                    <small class="fw-bold text-uppercase d-block kpi-label mb-1">KPI {{ $i + 1 }}</small>
                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">{{ $data['kpi'] }}</h6>
                    <p class="text-secondary mb-0" style="font-size: 0.85rem; line-height: 1.5;">
                        {{ $data['description'] ?? '-' }}</p>
                </div>
                <div class="col-md-7 col-lg-7">
                    <div class="row g-3 mb-3">
                        <div class="col-3 col-sm-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Target</small>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['target'] }}</span>
                        </div>
                        <div class="col-3 col-sm-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">UoM</small>
                            <span class="fw-bold text-dark"
                                style="font-size: 0.9rem;">{{ is_null($data['custom_uom']) ? $data['uom'] : $data['custom_uom'] }}</span>
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
                                <div class="mini-progress-bar bg-success" data-width="{{ $percent }}%"></div>
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
                            <span class="fw-bold text-dark"
                                style="font-size: 0.9rem;">{{ $data['review_period_label'] }}</span>
                        </div>
                        <div class="col-3 col-sm-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Calc Method</small>
                            <span class="fw-bold text-dark"
                                style="font-size: 0.9rem;">{{ $data['calculation_method_label'] }}</span>
                        </div>
                        <div class="col-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1"></small>
                            <span class="fw-bold text-dark"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row align-items-stretch">

                <div class="col-lg-12 mb-3">
                    <div class="card shadow-none border h-100" style="background-color: #fafafa;">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                <h6 class="fw-bold text-secondary mb-0" style="font-size: 0.8rem;"><i
                                        class="ri-history-line me-1"></i>Previous Achievement</h6>
                            </div>

                            @if ($data['has_old_data'])
                            <div class="row g-2">
                                @foreach($data['old_months'] as $month)

                                <div class="col-xl-1 col-lg-2 col-md-3 col-4">
                                    <div class="month-box month-box-old">
                                        <span class="month-label">{{ $month['label'] }}</span>

                                        <input type="text" value="{{ $month['value'] ?? '-' }}"
                                            class="month-input month-input-old" readonly>

                                        @if(!empty($old['file']))
                                        <a href="{{ asset('storage/'.$month['file']) }}" target="_blank"
                                            class="btn btn-outline-secondary btn-evid">
                                            FILE
                                        </a>
                                        @else
                                        <span class="btn btn-outline-secondary btn-evid disabled">
                                            NONE
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card border-primary border-opacity-50 bg-white shadow-sm h-100">
                        <div class="card-body p-2">
                            <div
                                class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom border-primary border-opacity-25">
                                <h6 class="fw-bold text-primary mb-0" style="font-size: 0.8rem;"><i
                                        class="ri-file-edit-line me-1"></i>Current Achievement</h6>
                            </div>

                            <div class="row g-2">
                                @foreach($data['months'] as $monthIdx => $month)
                                <div class="col-xl-1 col-lg-2 col-md-3 col-4">
                                    <div class="month-box border-primary border-opacity-25">
                                        <span class="month-label text-primary">{{ $month['label'] }}</span>

                                        <input type="text" name="ach[{{$data['kpi_id']}}][{{$monthIdx}}]"
                                            value="{{ $month['value'] ?? '' }}" class="month-input" placeholder="-">

                                        @if(!empty($month['file']))
                                        <a href="{{ asset('storage/'.$month['file']) }}" target="_blank"
                                            class="btn btn-success btn-evid border-0">
                                            VIEW
                                        </a>
                                        @else
                                        <span class="btn btn-outline-secondary btn-evid disabled">
                                            NONE
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        @else

        <div class="p-3 mb-4 rounded shadow-sm" style="background-color: #f8f9fa; border: 1px solid #eef0f2;">

            <div class="row g-3">
                <div class="col-md-5 col-lg-5 mb-md-0">
                     <span class="badge bg-success" style="font-size: 0.6rem;">
                            FIRST SUBMISSION
                        </span>
                    <small class="fw-bold text-uppercase d-block kpi-label mb-1">KPI {{ $i + 1 }}</small>
                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">{{ $data['kpi'] }}</h6>
                    <p class="text-secondary mb-0" style="font-size: 0.85rem; line-height: 1.5;">
                        {{ $data['description'] ?? '-' }}</p>
                </div>
                <div class="col-md-7 col-lg-7">
                    <div class="row g-3 mb-3">
                        <div class="col-3 col-sm-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Target</small>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['target'] }}</span>
                        </div>
                        <div class="col-3 col-sm-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">UoM</small>
                            <span class="fw-bold text-dark"
                                style="font-size: 0.9rem;">{{ is_null($data['custom_uom']) ? $data['uom'] : $data['custom_uom'] }}</span>
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
                                <div class="mini-progress-bar bg-success" data-width="{{ $percent }}%"></div>
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
                            <span class="fw-bold text-dark"
                                style="font-size: 0.9rem;">{{ $data['review_period_label'] }}</span>
                        </div>
                        <div class="col-3 col-sm-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Calc Method</small>
                            <span class="fw-bold text-dark"
                                style="font-size: 0.9rem;">{{ $data['calculation_method_label'] }}</span>
                        </div>
                        <div class="col-3">
                            <small class="fw-bold text-uppercase d-block kpi-label mb-1"></small>
                            <span class="fw-bold text-dark"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row align-items-stretch">
                <div class="col-12">
                    <div class="card border-primary border-opacity-50 bg-white shadow-sm h-100">
                        <div class="card-body p-2">
                            <div class="row g-2">
                                @foreach($data['months'] as $monthIdx => $month)
                                <div class="col-xl-1 col-lg-2 col-md-3 col-4">
                                    <div class="month-box border-primary border-opacity-25">
                                        <span class="month-label text-primary">{{ $month['label'] }}</span>

                                        <input type="text" name="ach[{{$data['kpi_id']}}][{{$monthIdx}}]"
                                            value="{{ $month['value'] ?? '' }}" class="month-input" placeholder="-">

                                        @if(!empty($month['file']))
                                        <a href="{{ asset('storage/'.$month['file']) }}" target="_blank"
                                            class="btn btn-success btn-evid border-0">
                                            VIEW
                                        </a>
                                        @else
                                        <span class="btn btn-outline-secondary btn-evid disabled">
                                            NONE
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @endif

        @endforeach


        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3 bg-white rounded">
                <div class="d-flex flex-column flex-md-row justify-content-end align-items-center">
                    <div class="w-100 w-md-auto">
                        <div class="d-flex flex-wrap justify-content-center justify-content-md-end gap-2">
                            {{-- <div class="dropdown">
                                <button class="btn btn-warning btn-sm fw-medium dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ __('Send Back') }}
                            </button>
                            <div class="dropdown-menu shadow-sm" style="font-size: 0.8rem;">
                                <h6 class="dropdown-header text-dark fw-bold">Select person below:</h6>
                                <a class="dropdown-item py-1" href="#">Metta Saputra (12345678)</a>
                            </div>
                        </div> --}}
                        <a href="{{ route('team-goals') }}"
                            class="btn btn-light text-secondary border px-3 btn-sm fw-medium">{{ __('Cancel') }}</a>
                        {{-- <button type="submit" class="btn btn-primary btn-sm fw-medium px-4"> --}}
                        <button type="submit" class="btn btn-primary btn-sm fw-medium px-4">
                            {{ __('Approve') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
</div>
</form>
</div>
@endsection

@push('scripts')
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