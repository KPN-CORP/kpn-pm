@extends('layouts_.vertical', ['page_title' => 'Team Goals'])

@section('css')
<style>
.kpi-label {
    color: #9e2a2b;
    font-size: 0.7rem;
    letter-spacing: 0.5px;
}
.read-only-month {
    background-color: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 10px 4px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    @if (session('success'))
        <div class="alert alert-success mt-3">
            {!! session('success') !!}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mt-3">
            {!! session('error')['message'] !!}
        </div>
    @endif
    <div class="row my-3">
        <div class="col-md">
            <ul class="nav nav-pills justify-content-md-start justify-content-center" id="myTab" role="tablist">
                <li class="nav-item">
                    <button class="btn btn-outline-primary position-relative active me-2 mb-3" id="initiated-tab" data-bs-toggle="tab" data-bs-target="#initiated" type="button" role="tab" aria-controls="initiated" aria-selected="true">
                    {{ __('Approval') }}
                    <span class="position-absolute top-0 start-100 translate-middle badge bg-danger {{ $notificationGoal ? '' : 'd-none' }}">
                        {{ $notificationGoal }}
                    </span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="btn btn-outline-secondary position-relative mb-3" id="not-initiated-tab" data-bs-toggle="tab" data-bs-target="#not-initiated" type="button" role="tab" aria-controls="not-initiated" aria-selected="false">
                    {{ __('Not Initiated') }}
                    <span class="position-absolute top-0 start-100 translate-middle badge bg-danger {{ count($notasks) ? '' : 'd-none' }}">
                        {{ count($notasks) }}
                    </span>
                    </button>
                </li>
            </ul>
        </div>
        <div class="col-md-auto">
            <button type="button" class="btn btn-primary shadow" data-bs-toggle="modal" data-bs-target="#importModal">Import Goals</button>
        </div>
    </div>
    <div class="tab-content">
        <div class="tab-pane active show" id="initiated" role="tabpanel">
            <div class="row rounded mb-2">
                <div class="col-lg-auto text-center">
                    <div class="align-items-center">
                        <button class="btn btn-outline-primary btn-sm my-1 me-1 filter-btn" data-id="All Task">{{ __('All Task') }}</button>
                        <button class="btn btn-outline-primary btn-sm my-1 me-1 filter-btn" data-id="draft">Draft</button>
                        <button class="btn btn-outline-primary btn-sm my-1 me-1 filter-btn" data-id="{{ __('Waiting For Revision') }}">{{ __('Waiting For Revision') }}</button>
                        <button class="btn btn-outline-primary btn-sm my-1 me-1 filter-btn" data-id="{{ __('Pending') }}">{{ __('Pending') }}</button>
                        <button class="btn btn-outline-primary btn-sm my-1 me-1 filter-btn" data-id="{{ __('Approved') }}">{{ __('Approved') }}</button>
                    </div>
                </div>
            </div>
            <form id="formYearGoal" action="{{ route('team-goals') }}" method="GET">
                @php
                    $filterYear = request('filterYear');
                @endphp
                <div class="row align-items-end justify-content-between">
                    <div class="col-md-3">
                        <div class="mb-2">
                            <label class="form-label" for="filterYear">{{ __('Year') }}</label>
                            <select name="filterYear" id="filterYear" onchange="yearGoal(this)" class="form-select">
                                @if ($period)
                                    <option value="{{ $period }}" {{ $period == $filterYear ? 'selected' : '' }}>{{ $period }}</option>  
                                @endif
                                @foreach ($selectYear as $year)
                                    <option value="{{ $year->period }}" {{ $year->period == $filterYear ? 'selected' : '' }}>{{ $year->period }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-2">
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                    <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                                    </div>
                                    <input type="text" name="customsearch" id="customsearch" class="form-control border-left-0" placeholder="search.." aria-label="search" aria-describedby="search">
                                    <div class="d-sm-none input-group-append"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="row px-2">
                <div class="col-lg-12 p-0">
                    <div class="mt-3 p-2 bg-primary-subtle rounded shadow">
                        <div class="row">
                            <div class="col d-flex align-items-center">
                                <h5 class="m-0 w-100">
                                    <a class="text-dark d-block" data-bs-toggle="collapse" href="#dataTasks" role="button" aria-expanded="false" aria-controls="dataTasks">
                                        <i class="ri-arrow-down-s-line fs-18"></i>Goals {{ $filterYear ?? $period }} <span class="text-muted">({{ count($tasks) }})</span>
                                    </a>
                                </h5>
                            </div>
                            <div class="col-auto">
                                <form id="exportInitiatedForm" action="{{ route('team-goals.initiated') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="employee_id" id="employee_id" value="{{ Auth()->user()->employee_id }}">
                                    <input type="hidden" name="filterYear" id="filterYear" value="{{ $filterYear ?? $period }}">
                                    @if (count($tasks))
                                        <button id="report-button" type="submit" class="btn btn-sm btn-success float-end"><i class="ri-download-cloud-2-line me-1"></i><span>{{ __('Download') }}</span></button>
                                    @endif
                                </form>
                            </div>
                        </div>
                        <div class="collapse show" id="dataTasks">
                            <div class="card mb-0 mt-2">
                                <div class="card-body py-1" id="task-container-1">
                                    @forelse ($tasks as $index => $task)
                                    @php
                                        $subordinates = $task->subordinates;
                                        $firstSubordinate = $subordinates->isNotEmpty() ? $subordinates->first() : null;
                                        $formStatus = $firstSubordinate ? $firstSubordinate->goal->form_status : null;
                                        $goalId = $firstSubordinate ? $firstSubordinate->goal->id : null;
                                        $appraisalCheck = $firstSubordinate ? $firstSubordinate->appraisalCheck : null;
                                        $goalPeriod = $firstSubordinate ? $firstSubordinate->goal->period : null;
                                        $goalData = $firstSubordinate ? $firstSubordinate->goal['form_data'] : null;
                                        $createdAt = $firstSubordinate ? $firstSubordinate->formatted_created_at : null;
                                        $updatedAt = $firstSubordinate ? $firstSubordinate->formatted_updated_at : null;
                                        $updatedBy = $firstSubordinate ? $firstSubordinate->updatedBy : null;
                                        $status = $firstSubordinate ? $firstSubordinate->status : null;
                                        $approverId = $firstSubordinate ? $firstSubordinate->current_approval_id : null;
                                        $sendbackTo = $firstSubordinate ? $firstSubordinate->sendback_to : null;
                                        $employeeId = $firstSubordinate ? $firstSubordinate->employee_id : null;
                                        $sendbackMessages = $firstSubordinate ? $firstSubordinate->sendback_messages : null;
                                        $employeeName = $firstSubordinate ? $firstSubordinate->name : null;
                                        $approvalLayer = $firstSubordinate ? $firstSubordinate->approvalLayer : null;
                                        $accessMenu = json_decode($firstSubordinate->employee->access_menu, true);
                                        $goals = $accessMenu['goals'] ?? null;
                                        $doj = $accessMenu['doj'] ?? null;
                                        
                                        $formDataArr = json_decode($goalData, true) ?? [];
                                        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                    @endphp
                                    <div class="row mt-2 mb-2 task-card" data-status="{{ $formStatus == 'Draft' ? 'draft' : ($status == 'Pending' ? __('Pending') : ($subordinates->isNotEmpty() ? ($status == 'Sendback' ? __('Waiting For Revision') : __($status)) : 'no data')) }}">
                                        <div class="col-12">
                                            <div class="row">
                                                <div class="col-md mb-sm-0 p-2">
                                                    <div id="tooltip-container">
                                                        <img src="{{ asset('storage/app/public/img/profiles/user.png') }}" alt="image" class="avatar-xs rounded-circle me-1" data-bs-container="#tooltip-container" data-bs-toggle="tooltip" data-bs-placement="bottom"  data-bs-original-title="{{ __('Initiated By') }} {{ $task->employee->fullname.' ('.$task->employee->employee_id.')' }}">
                                                        {{ $task->employee->fullname }} <span class="text-muted">{{ $task->employee->employee_id }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-auto p-2 d-none d-md-block text-end">
                                                    <div class="mb-2">
                                                        @if ($period == $goalPeriod && $formStatus != 'Draft' && $status != 'Sendback' && !$appraisalCheck && $goals)
                                                            <a class="btn btn-sm btn-outline-warning me-1 fw-semibold {{ Auth::user()->employee_id == $firstSubordinate->initiated->employee_id ? '' : 'd-none' }}" href="{{ route('goals.edit', $goalId) }}" onclick="showLoader()">{{ __('Revise Goals') }}</a>
                                                        @endif

                                                        @if ($period == $goalPeriod && $task->employee->employee_id == Auth::user()->employee_id || !$subordinates->isNotEmpty() || $formStatus == 'Draft')
                                                            @if ($formStatus == 'submitted' || $formStatus == 'Approved' || $appraisalCheck)
                                                            <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                                            @endif
                                                            <a class="btn btn-sm me-1 btn-outline-warning fw-semibold {{ Auth::user()->employee_id == $firstSubordinate->initiated->employee_id ? '' : 'd-none' }}" href="{{ route('team-goals.edit', $goalId) }}" onclick="showLoader()">{{ __('Edit') }}</a>
                                                        @else
                                                            @if ($period == $goalPeriod && $approverId == Auth::user()->employee_id && $status === 'Pending' || $sendbackTo == Auth::user()->employee_id && $status === 'Sendback' || !$subordinates->isNotEmpty() || Auth::user()->employee_id == $firstSubordinate->initiated->employee_id && $status === 'Sendback' && $task->employee->employee_id != Auth::user()->employee_id)
                                                                <a class="btn btn-sm me-1 btn-outline-warning fw-semibold {{ Auth::user()->employee_id == $firstSubordinate->initiated->employee_id ? '' : 'd-none' }}" href="{{ route('team-goals.edit', $goalId) }}" onclick="showLoader()">{{ $status === 'Sendback' ? __('Revise Goals') : __('Edit') }}</a>
                                                                @if ($status != 'Sendback' && Auth::user()->employee_id != $firstSubordinate->initiated->employee_id && !$appraisalCheck)
                                                                    <a href="{{ route('team-goals.approval', $goalId) }}" class="btn btn-sm btn-primary fw-medium me-1" onclick="showLoader()">Approve Goal</a>
                                                                    <button type="button" class="btn btn-sm btn-secondary fw-medium me-1" disabled style="opacity: 0.6; cursor: not-allowed;">Approve Achievement</button>
                                                                    <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                                                @endif
                                                            @elseif ($period == $goalPeriod && $status === 'Approved' && !$appraisalCheck)
                                                                <a href="{{ route('goals.approval-achievement', $goalId) }}" class="btn btn-sm btn-success fw-medium me-1">Approve Achievement</a>
                                                                <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                                            @else
                                                                <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-6 col-md-4 col-xl-2">
                                                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">{{ __('Initiated By') }}</small>
                                                    @if($subordinates->isNotEmpty() && $firstSubordinate->initiated)
                                                        <span class="text-dark fw-medium d-block text-truncate" title="{{ $firstSubordinate->initiated->name .' ('.$firstSubordinate->initiated->employee_id.')' }}">
                                                            {{ $firstSubordinate->initiated->name }}
                                                        </span>
                                                        <small class="text-muted fw-medium d-block text-truncate" title="{{ $firstSubordinate->initiated->name .' ('.$firstSubordinate->initiated->employee_id.')' }}">
                                                            {{ $firstSubordinate->initiated->employee_id }}
                                                        </small>
                                                    @else
                                                        <span class="text-dark fw-medium d-block">-</span>
                                                    @endif
                                                </div>
                                                <div class="col-6 col-md-4 col-xl-2">
                                                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">{{ __('Initiated Date') }}</small>
                                                    <span class="text-dark fw-medium d-block text-truncate">{{ $createdAt ? $createdAt : '-' }}</span>
                                                </div>
                                                <div class="col-6 col-md-4 col-xl-2">
                                                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">{{ __('Last Updated') }}</small>
                                                    <span class="text-dark fw-medium d-block text-truncate">{{ $updatedAt ? $updatedAt : '-' }}</span>
                                                </div>
                                                <div class="col-6 col-md-4 col-xl-2">
                                                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">Updated By</small>
                                                    @if($updatedBy)
                                                        <span class="text-dark fw-medium d-block text-truncate" title="{{ $updatedBy->name.' ('.$updatedBy->employee_id.')' }}">{{ $updatedBy->name }}</span>
                                                        <small class="text-muted fw-medium d-block text-truncate" title="{{ $updatedBy->name.' ('.$updatedBy->employee_id.')' }}">{{ $updatedBy->employee_id }}</small>
                                                    @else
                                                        <span class="text-dark fw-medium d-block">-</span>
                                                    @endif
                                                </div>
                                                <div class="col-6 col-md-4 col-xl-2">
                                                    <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.7rem;">Goal Status</small>
                                                    <span class="badge {{ $subordinates->isNotEmpty() ? ($formStatus == 'Draft' || $status == 'Sendback' ? 'bg-dark-subtle text-dark' : ($status === 'Approved' || $appraisalCheck ? 'bg-success' : 'bg-warning')) : 'bg-dark-subtle text-secondary'}} rounded-pill py-1 px-2 d-inline-block text-truncate" style="max-width: 100%;">
                                                        {{ $appraisalCheck ? __('Approved') : ($formStatus == 'Draft' ? 'Draft': ($status == 'Pending' ? __('Pending') : ($subordinates->isNotEmpty() ? ($status == 'Sendback' ? __('Waiting For Revision') : __($status)) : 'No Data'))) }}
                                                    </span>
                                                </div>
                                                <div class="col-6 col-md-4 col-xl-2">
                                                    <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.7rem;">Achieve Status</small>
                                                    <span class="badge bg-warning rounded-pill py-1 px-2 d-inline-block text-truncate" style="max-width: 100%;">
                                                        Pending
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 d-md-none d-block mt-3">
                                            <div class="align-items-center text-start py-2">
                                                @if ($period == $goalPeriod && $formStatus != 'Draft' && $status != 'Sendback' && !$appraisalCheck && $goals)
                                                    <a class="btn btn-sm btn-outline-warning me-1 mb-1 fw-semibold {{ Auth::user()->employee_id == $firstSubordinate->initiated->employee_id ? '' : 'd-none' }}" href="{{ route('goals.edit', $goalId) }}" onclick="showLoader()">{{ __('Revise Goals') }}</a>
                                                @endif
                                                @if ($task->employee->employee_id == Auth::user()->employee_id || !$subordinates->isNotEmpty() || $formStatus == 'Draft')
                                                    @if ($formStatus == 'submitted' || $formStatus == 'Approved' || $appraisalCheck)
                                                    <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm mb-1 me-1" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                                    @endif
                                                    <a class="btn btn-sm me-1 mb-1 btn-outline-warning fw-semibold {{ Auth::user()->employee_id == $firstSubordinate->initiated->employee_id ? '' : 'd-none' }}" href="{{ route('team-goals.edit', $goalId) }}" onclick="showLoader()">{{ __('Edit') }}</a>
                                                @else
                                                    @if ($period == $goalPeriod && $approverId == Auth::user()->employee_id && $status === 'Pending' || $sendbackTo == Auth::user()->employee_id && $status === 'Sendback' || !$subordinates->isNotEmpty() || Auth::user()->employee_id == $firstSubordinate->initiated->employee_id && $status === 'Sendback' && $task->employee->employee_id != Auth::user()->employee_id)
                                                        <a class="btn btn-sm me-1 mb-1 btn-outline-warning fw-semibold {{ Auth::user()->employee_id == $firstSubordinate->initiated->employee_id ? '' : 'd-none' }}" href="{{ route('team-goals.edit', $goalId) }}" onclick="showLoader()">{{ $status === 'Sendback' ? __('Revise Goals') : __('Edit') }}</a>
                                                        @if ($status != 'Sendback' && Auth::user()->employee_id != $firstSubordinate->initiated->employee_id && !$appraisalCheck)
                                                            <a href="{{ route('team-goals.approval', $goalId) }}" class="btn btn-sm btn-primary mb-1 fw-medium me-1" onclick="showLoader()">Approve Goal</a>
                                                            <button type="button" class="btn btn-sm btn-secondary mb-1 fw-medium me-1" disabled style="opacity: 0.6; cursor: not-allowed;">Approve Achievement</button>
                                                            <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                                        @endif
                                                    @elseif ($period == $goalPeriod && $status === 'Approved' && !$appraisalCheck)
                                                        <a href="{{ route('goals.approval-achievement', $goalId) }}" class="btn btn-sm btn-success mb-1 fw-medium me-1">Approve Achievement</a>
                                                        <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                                    @else
                                                        <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        @if($index < count($tasks) - 1)
                                            <div class="col-12"><hr class="mb-1 mt-2"></div>
                                        @endif
                                    </div>
                                    
                                    <div class="modal fade" id="modalDetail{{ $goalId }}" tabindex="-1" aria-labelledby="modalDetailLabel{{ $goalId }}" aria-hidden="true">
                                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                            <div class="modal-content border-0 shadow-lg">
                                                <div class="modal-header bg-light border-bottom">
                                                    <h5 class="modal-title text-dark fw-bold" id="modalDetailLabel{{ $goalId }}"><i class="ri-file-list-3-line me-1 text-primary"></i> Goal Details - {{ $task->employee->fullname }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-0 bg-white">
                                                    @if(count($formDataArr) > 0)
                                                        @foreach ($formDataArr as $kpiIndex => $data)
                                                        <div class="p-4 pt-4 {{ $loop->even ? 'bg-light-subtle' : 'bg-white' }} {{ $loop->last ? '' : 'border-bottom' }}">
                                                            <div class="row g-3">
                                                                <div class="col-md-5 col-lg-5 mb-md-0">
                                                                    <small class="fw-bold text-uppercase d-block kpi-label mb-1" style="color: #9e2a2b;">KPI {{ $kpiIndex + 1 }}</small>
                                                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">{{ $data['kpi'] ?? '-' }}</h6>
                                                                    <p class="text-secondary mb-0 mt-2" style="white-space: pre-line; font-size: 0.85rem; line-height: 1.5;">{{ $data['description'] ?? '-' }}</p>
                                                                </div>
                                                                <div class="col-md-7 col-lg-7">
                                                                    <div class="row g-3 mb-3">
                                                                        <div class="col-3 col-sm-3">
                                                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Target</small>
                                                                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">{{ $data['target'] ?? '-' }}</span>
                                                                        </div>
                                                                        <div class="col-3 col-sm-3">
                                                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">UoM</small>
                                                                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">{{ (isset($data['uom']) && $data['uom'] !== 'Other') ? $data['uom'] : ($data['custom_uom'] ?? '-') }}</span>
                                                                        </div>
                                                                        <div class="col-3 col-sm-3">
                                                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Weightage</small>
                                                                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">{{ $data['weightage'] ?? '0' }}%</span>
                                                                        </div>
                                                                        <div class="col-3 col-sm-3">
                                                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Achievement</small>
                                                                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">30</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row g-3 mb-3">
                                                                        <div class="col-4 col-sm-4">
                                                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Type</small>
                                                                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['type'] ?? '-' }}</span>
                                                                        </div>
                                                                        <div class="col-4 col-sm-4">
                                                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Review Period</small>
                                                                            @php
                                                                                $rv = $data['review_period'] ?? '';
                                                                                $rvLabel = $rv ?: '-';
                                                                                if (isset($reviewPeriodOption) && is_array($reviewPeriodOption)) {
                                                                                    foreach ($reviewPeriodOption as $group) {
                                                                                        foreach ($group as $opt) {
                                                                                            if ((string)$rv === (string)($opt['value'] ?? '')) {
                                                                                                $rvLabel = $opt['label'];
                                                                                                break 2;
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            @endphp
                                                                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $rvLabel }}</span>
                                                                        </div>
                                                                        <div class="col-4 col-sm-4">
                                                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Calc Method</small>
                                                                            @php
                                                                                $rv = $data['calculation_method'] ?? '';
                                                                                $rvLabel = $rv ?: '-';
                                                                                if (isset($calculationMethodOption) && is_array($calculationMethodOption)) {
                                                                                    foreach ($calculationMethodOption as $group) {
                                                                                        foreach ($group as $opt) {
                                                                                            if ((string)$rv === (string)($opt['value'] ?? '')) {
                                                                                                $rvLabel = $opt['label'];
                                                                                                break 2;
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            @endphp
                                                                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $rvLabel }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="mt-4">
                                                                <h6 class="fw-bold text-uppercase kpi-label mb-2">{{ __('Achievement Tracking') }}</h6>
                                                                <div class="row g-2">
                                                                    @foreach($months as $monthNum => $monthLabel)
                                                                    @php
                                                                        $value = $data['ach'][$monthNum] ?? null;
                                                                        $formatted = is_null($value) || $value === '' ? '-' : rtrim(rtrim($value, '0'), '.');
                                                                    @endphp
                                                                    <div class="col-4 col-sm-3 col-md-2 col-lg-1">
                                                                        <div class="read-only-month border rounded p-2 text-center" style="background-color: #fcfcfc;">
                                                                            <span class="text-uppercase fw-bold text-secondary d-block mb-1" style="font-size: 0.65rem;">{{ $monthLabel }}</span>
                                                                            <span class="fw-bold text-dark" style="font-size: 1.1rem;">{{ $formatted }}</span>
                                                                        </div>
                                                                    </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    @else
                                                        <div class="p-5 text-center text-muted">
                                                            <i class="ri-inbox-2-line fs-1 d-block mb-2"></i>
                                                            No details available.
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer bg-light border-top">
                                                    <button type="button" class="btn btn-secondary fw-medium" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="p-3">
                                        <div id="no-data-1" class="text-center">
                                            <h5 class="text-muted">No Data</h5>
                                        </div>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane" id="not-initiated" role="tabpanel">
           <ul class="nav nav-tabs mb-3 mt-3" id="innerNotInitiatedTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold px-3 py-2" style="font-size: 0.85rem;" id="inner-goal-tab" data-bs-toggle="tab" data-bs-target="#inner-goal" type="button" role="tab" aria-controls="inner-goal" aria-selected="true">
            {{ __('Goal') }}
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold px-3 py-2" style="font-size: 0.85rem;" id="inner-achievement-tab" data-bs-toggle="tab" data-bs-target="#inner-achievement" type="button" role="tab" aria-controls="inner-achievement" aria-selected="false">
            {{ __('Achievement') }}
        </button>
    </li>
</ul>
            <div class="tab-content" id="innerNotInitiatedTabContent">
                <div class="tab-pane fade show active" id="inner-goal" role="tabpanel" aria-labelledby="inner-goal-tab">
                    <form id="formYearGoal" action="{{ route('team-goals') }}" method="GET">
                        @php
                            $filterYear = request('filterYear');
                        @endphp
                        <div class="row align-items-end justify-content-between">
                            <div class="col-md-3">
                                <div class="mb-2">
                                    <label class="form-label" for="filterYear">{{ __('Year') }}</label>
                                    <select name="filterYear" id="filterYear" onchange="yearGoal(this)" class="form-select">
                                        @if ($period)
                                            <option value="{{ $period }}" {{ $period == $filterYear ? 'selected' : '' }}>{{ $period }}</option>  
                                        @endif
                                        @foreach ($selectYear as $year)
                                            <option value="{{ $year->period }}" {{ $year->period == $filterYear ? 'selected' : '' }}>{{ $year->period }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                            <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                                            </div>
                                            <input type="text" name="customsearch" id="customsearch" class="form-control border-left-0" placeholder="search.." aria-label="search" aria-describedby="search">
                                            <div class="d-sm-none input-group-append"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="row px-2">
                        <div class="col-lg-12 p-0">
                            <div class="mt-3 p-2 bg-secondary-subtle rounded shadow">
                                <div class="row">
                                    <div class="col d-flex align-items-center">
                                        <h5 class="m-0 w-100">
                                            <a class="text-dark d-block" data-bs-toggle="collapse" href="#noDataTasks" role="button" aria-expanded="false" aria-controls="noDataTasks">
                                                <i class="ri-arrow-down-s-line fs-18"></i>Not Initiated <span class="text-muted">({{ count($notasks) }})</span>
                                            </a>
                                        </h5>
                                    </div>
                                    <div class="col-auto">
                                        <form id="exportNotInitiatedForm" action="{{ route('team-goals.notInitiated') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="employee_id" id="employee_id" value="{{ Auth()->user()->employee_id }}">
                                            <input type="hidden" name="filterYear" id="filterYear" value="{{ $filterYear ?? $period }}">
                                            @if (count($notasks))
                                                <button id="report-button" type="submit" class="btn btn-sm btn-success float-end"><i class="ri-download-cloud-2-line me-1"></i><span>{{ __('Download Template') }}</span></button>
                                            @endif
                                        </form>
                                    </div>
                                </div>
                            
                                <div class="collapse show" id="noDataTasks">
                                    <div class="card mt-2 mb-0 d-flex border border-secondary">
                                        <div class="card-body py-1 align-items-center" id="task-container-2">
                                            @forelse ($notasks as $index => $notask)
                                            @php
                                                $subordinates = $notask->subordinates;
                                                $firstSubordinate = $subordinates->isNotEmpty() ? $subordinates->first() : null;
                                                $formStatus = $firstSubordinate ? $firstSubordinate->goal->form_status : null;
                                                $goalId = $firstSubordinate ? $firstSubordinate->goal->id : null;
                                                $goalData = $firstSubordinate ? $firstSubordinate->goal['form_data'] : null;
                                                $createdAt = $firstSubordinate ? $firstSubordinate->created_at : null;
                                                $updatedAt = $firstSubordinate ? $firstSubordinate->updated_at : null;
                                                $updatedBy = $firstSubordinate ? $firstSubordinate->updatedBy : null;
                                                $status = $firstSubordinate ? $firstSubordinate->status : null;
                                                $approverId = $firstSubordinate ? $firstSubordinate->current_approval_id : null;
                                                $sendbackTo = $firstSubordinate ? $firstSubordinate->sendback_to : null;
                                                $employeeId = $firstSubordinate ? $firstSubordinate->employee_id : null;
                                            @endphp
                                            <div class="row mt-2 mb-2 task-card d-flex" data-status="no data">
                                                <div class="col-12 col-md-6 p-2 d-flex align-items-center">
                                                    <div id="tooltip-container">
                                                        <img src="{{ asset('storage/app/public/img/profiles/user.png') }}" alt="image" class="avatar-xs rounded-circle me-1" data-bs-container="#tooltip-container" data-bs-toggle="tooltip" data-bs-placement="bottom"  data-bs-original-title="{{ $notask->employee->fullname.' ('.$notask->employee->employee_id.')' }}">
                                                        {{ $notask->employee->fullname }} <span class="text-muted">{{ $notask->employee->employee_id }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md mb-2">
                                                    <label class="form-label text-muted" style="font-size: 0.8rem;">Date of Joining</label>
                                                    <span class="d-flex align-items-center text-dark">{{ $notask->formatted_doj }}</span>
                                                </div>
                                                <div class="col-6 col-md mb-2">
                                                    <label class="form-label text-muted" style="font-size: 0.8rem;">Status</label>
                                                    <div><a href="javascript:void(0)" id="approval{{ $employeeId }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Employee has not set goals yet." data-bs-id="{{ $employeeId }}" class="badge bg-dark-subtle text-dark rounded-pill py-1 px-2">Not Initiated</a></div>
                                                </div>
                                                <div class="col-12 col-md-auto d-flex align-items-center mt-2 mt-md-0 ms-md-auto justify-content-end">
                                                    @php
                                                        $accessMenu = json_decode($notask->employee->access_menu, true);
                                                        $goals = $accessMenu['goals'] ?? null;
                                                        $doj = $accessMenu['doj'] ?? null;
                                                        $managerL1 = $notask?->employee?->managerL1;
                                                    @endphp
                                                    @if ((!$filterYear || $filterYear == $period) && $goals && $notask->isManager)
                                                        <button data-id="{{ encrypt($notask->employee->employee_id) }}" id="initiateBtn{{ $index }}" class="btn btn-outline-primary btn-sm">{{ __('Initiate') }}</button>
                                                    @else
                                                        <div><a href="javascript:void(0)" id="approval{{ $employeeId }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Manager L1 : {{ $managerL1 ? $managerL1->fullname.' ('.$managerL1->employee_id.')' : '-' }}" data-bs-id="{{ $employeeId }}" class="badge bg-warning fw-bold rounded-pill py-1 px-2">view L1</a></div>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($index < count($notasks) - 1)
                                                <div class="col-12"><hr class="mb-1 mt-2"></div>
                                            @endif
                                            @empty
                                            <div class="p-3">
                                                <div id="no-data-1" class="text-center">
                                                    <h5 class="text-muted">No Data</h5>
                                                </div>
                                            </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="inner-achievement" role="tabpanel" aria-labelledby="inner-achievement-tab">
                    <div class="card mt-3 shadow-sm border-0">
                        <div class="card-body text-center p-5">
                            <i class="ri-medal-line text-muted mb-3" style="font-size: 3rem;"></i>
                            <h5 class="text-muted">Achievement Data</h5>
                            <p class="text-secondary mb-0">List of not initiated achievements will appear here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</div>
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Goals</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importGoal" action="{{ route('importgoalsmanager') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col">
                            <div class="alert alert-info">
                                <strong>Notes:</strong>
                                <ul class="mb-0">
                                    <li>{{ __('Note Import Goal Manager') }}<strong><br> > Tab "{{ __('Not Initiated') }}" -> {{ __('Download') }}</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="file">Upload File</label>
                        <input type="file" name="file" id="file" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="importGoalsButton" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection