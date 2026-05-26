@extends('layouts_.vertical', ['page_title' => 'Team Goals'])

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

.btn-primary { background-color: var(--kpn-primary) !important; border-color: var(--kpn-primary) !important; color: white !important; }
.btn-primary:hover { background-color: var(--kpn-primary-hover) !important; border-color: var(--kpn-primary-hover) !important; }
.btn-outline-primary { color: var(--kpn-primary) !important; border-color: var(--kpn-primary) !important; background-color: transparent !important;}
.btn-outline-primary:hover { background-color: var(--kpn-primary-soft) !important; color: var(--kpn-primary) !important; }
.btn-soft-primary { background-color: var(--kpn-primary-soft) !important; color: var(--kpn-primary) !important; border: 1px solid transparent !important; }
.btn-soft-primary:hover { background-color: #fae8e8 !important; }

.kpi-label { font-size: 0.65rem; letter-spacing: 0.3px; text-transform: uppercase; color: #64748b; }
.read-only-month { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 4px; text-align: center; min-width: 70px; flex-shrink: 0; }
.read-only-month.has-value { background-color: var(--kpn-primary-soft); border-color: #e5b3b2; }
.mini-progress {
    width:100%;
    height:18px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 0px;
}
.mini-progress-text{
    position:absolute;
    inset:0;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:.65rem;
    color:#fff;
    z-index:2;

    text-shadow:
        -1px -1px 0 #9e2a2b,
         1px -1px 0 #9e2a2b,
        -1px  1px 0 #9e2a2b,
         1px  1px 0 #9e2a2b,

         0 0 3px rgba(0,0,0,.35);
}
.mini-progress-bar.bg-primary { height: 100%; border-radius: 10px; background: linear-gradient(90deg, var(--kpn-primary) 25%, #d96865 50%, var(--kpn-primary) 75%); background-size: 200% 100%; animation: progressFlow 1.5s linear infinite; }
@keyframes progressFlow { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.month-tracking-container { display: flex; overflow-x: auto; gap: 8px; padding-bottom: 8px; }
.month-tracking-container::-webkit-scrollbar { height: 4px; }
.month-tracking-container::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }

.task-row { border-bottom: 1px solid #f1f5f9; padding: 1rem 0; }
.task-row:last-child { border-bottom: none; }
.col-label { font-size: 0.65rem; letter-spacing: 0.3px; color: #64748b; }
.col-value { font-size: 0.85rem; }
.small-text { font-size: 0.7rem; }

.nav-custom-pills { background-color: #f1f5f9; padding: 4px; border-radius: 8px; display: inline-flex; }
.nav-custom-pills .nav-link { color: #64748b; border-radius: 6px; font-weight: 600; padding: 6px 20px; font-size: 0.85rem; transition: all 0.2s ease; }
.nav-custom-pills .nav-link.active { background-color: #ffffff; color: var(--kpn-primary); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }

.sub-tab-btn { font-size: 0.8rem; font-weight: 500; border-radius: 50px; padding: 4px 14px; transition: all 0.2s; border: 1px solid transparent; background-color: #f8fafc; color: #64748b; }
.sub-tab-btn:hover { background-color: #f1f5f9; }
.sub-tab-btn.active { background-color: var(--kpn-primary-soft) !important; color: var(--kpn-primary) !important; border-color: #e5b3b2 !important; font-weight: 600; }
.sub-tab-btn.active .badge { background-color: var(--kpn-primary) !important; color: white !important; }
.sub-tab-btn:not(.active) .badge { background-color: #cbd5e1 !important; color: white !important; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    @if (session('success'))
        <div class="alert alert-success mt-3 shadow-sm border-0 py-2">
            {!! session('success') !!}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mt-3 shadow-sm border-0 py-2">
            {{ is_array(session('error'))
                ? session('error')['message']
                : session('error')
            }}
        </div>
    @endif

    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center my-3 gap-3">
        <ul class="nav nav-pills nav-custom-pills gap-1 flex-shrink-0" id="mainTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-goal-btn" data-bs-toggle="tab" data-bs-target="#tab-goal" type="button" role="tab">
                    <i class="ri-focus-2-line me-1"></i> Goals
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-achievement-btn" data-bs-toggle="tab" data-bs-target="#tab-achievement" type="button" role="tab">
                    <i class="ri-medal-line me-1"></i> Achievements
                </button>
            </li>
        </ul>

        @php $filterYear = request('filterYear'); @endphp
        <form id="formYearGoal" action="{{ route('team-goals') }}" method="GET" class="d-flex flex-column flex-sm-row gap-2 w-100 justify-content-xl-end">
            <select name="filterYear" id="filterYear" onchange="yearGoal(this)" class="form-select form-select-sm shadow-sm flex-shrink-0" style="width: auto; min-width: 110px;">
                @if ($period) <option value="{{ $period }}" {{ $period == $filterYear ? 'selected' : '' }}>Year {{ $period }}</option> @endif
                @foreach ($selectYear as $year)
                    <option value="{{ $year->period }}" {{ $year->period == $filterYear ? 'selected' : '' }}>Year {{ $year->period }}</option>
                @endforeach
            </select>
            <div class="input-group input-group-sm shadow-sm" style="max-width: 300px;">
                <span class="input-group-text bg-white border-end-0"><i class="ri-search-line text-muted"></i></span>
                <input type="text" name="customsearch" id="customsearch" class="form-control border-start-0 shadow-none bg-white" placeholder="Search employee...">
            </div>
        </form>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-goal" role="tabpanel">
            <ul class="nav mb-2 flex-wrap" id="goal-filters">
                <li><button class="btn sub-tab-btn active" data-filter="all">All Task <span class="badge ms-1 count-all">0</span></button></li>
                <li><button class="btn sub-tab-btn" data-filter="draft">Draft <span class="badge ms-1 count-draft">0</span></button></li>
                <li><button class="btn sub-tab-btn" data-filter="not-initiated">Not Initiated <span class="badge ms-1 count-not-initiated">0</span></button></li>
                <li><button class="btn sub-tab-btn" data-filter="revision">Waiting for Revision <span class="badge ms-1 count-revision">0</span></button></li>
                <li><button class="btn sub-tab-btn" data-filter="pending">Waiting for Approval <span class="badge ms-1 count-pending">0</span></button></li>
                <li><button class="btn sub-tab-btn" data-filter="approved">Approved <span class="badge ms-1 count-approved">0</span></button></li>
            </ul>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center py-2 gap-2">
                    <h5 class="m-0 fw-bold text-primary">Goals {{ $filterYear ?? $period }}</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-soft-primary fw-medium" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="ri-upload-cloud-2-line me-1"></i> Import Goal
                        </button>
                        @if (count($tasks))
                        <form action="{{ route('team-goals.initiated') }}" method="POST" class="m-0">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ Auth()->user()->employee_id }}">
                            <input type="hidden" name="filterYear" value="{{ $filterYear ?? $period }}">
                            <button type="submit" class="btn btn-sm btn-outline-secondary fw-medium"><i class="ri-file-text-line me-1"></i> Goal Data</button>
                        </form>
                        @endif
                        @if (count($notasks))
                        <form action="{{ route('team-goals.notInitiated') }}" method="POST" class="m-0">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ Auth()->user()->employee_id }}">
                            <input type="hidden" name="filterYear" value="{{ $filterYear ?? $period }}">
                            <button type="submit" class="btn btn-sm btn-outline-secondary fw-medium"><i class="ri-download-cloud-2-line me-1"></i> Goal Template</button>
                        </form>
                        @endif
                    </div>
                </div>
                <div class="card-body px-3 py-1" id="goal-container">
                    @foreach ($tasks as $index => $task)
                        @php
                            $subordinates = $task->subordinates;
                            $firstSubordinate = $subordinates->isNotEmpty() ? $subordinates->first() : null;
                            
                            $formStatus = $firstSubordinate ? $firstSubordinate->goal->form_status : null;
                            $goalId = $firstSubordinate ? $firstSubordinate->goal->id : null;
                            $appraisalCheck = $firstSubordinate ? $firstSubordinate->appraisalCheck : null;
                            $goalPeriod = $firstSubordinate ? $firstSubordinate->goal->period : null;
                            $createdAt = $firstSubordinate ? $firstSubordinate->formatted_created_at : null;
                            $updatedAt = $firstSubordinate ? $firstSubordinate->formatted_updated_at : null;
                            $updatedBy = $firstSubordinate ? $firstSubordinate->updatedBy : null;
                            $status = $firstSubordinate ? $firstSubordinate->status : null;
                            $approverId = $firstSubordinate ? $firstSubordinate->current_approval_id : null;
                            $sendbackTo = $firstSubordinate ? $firstSubordinate->sendback_to : null;
                            $employeeId = $firstSubordinate ? $firstSubordinate->employee_id : null;
                            $employeeName = $firstSubordinate ? $firstSubordinate->name : null;
                            $approvalLayer = $firstSubordinate ? $firstSubordinate->approvalLayer : null;
                            $accessMenu = json_decode($firstSubordinate->employee->access_menu ?? '{}', true);
                            $goals = $accessMenu['goals'] ?? null;
                            
                            $hasData = $subordinates->isNotEmpty();
                            $isDraft = $formStatus == 'Draft';
                            $isSendbackSelf = $sendbackTo == $employeeId;
                            $isAutoApproved = $appraisalCheck;
                            $isApproved = $status == 'Approved';
                            $isPending = $status == 'Pending';

                            $rowStatus = 'approved';
                            if ($isDraft) $rowStatus = 'draft';
                            elseif ($status == 'Sendback') $rowStatus = 'revision';
                            elseif ($isPending || $isAutoApproved) $rowStatus = 'pending';

                            if (!$hasData) {
                                $badgeClass = 'bg-light text-secondary border';
                                $label = 'No Data';
                                $popover = 'No Data';
                            } elseif ($isDraft || $isSendbackSelf) {
                                $badgeClass = 'bg-secondary text-white';
                                $label = $isDraft ? 'Draft' : 'Waiting Your Revision';
                                $popover = $isDraft ? 'Draft' : 'Waiting Your Revision';
                            } elseif ($isAutoApproved || $isPending) {
                                $badgeClass = 'bg-warning text-dark';
                                $label = $isAutoApproved ? 'Auto Approved' : __($status);
                                $popover = $isAutoApproved ? '(Goals were auto-approved after you submitted PA '.$goalPeriod.')' : ($approvalLayer ? 'Manager L'.$approvalLayer.' : '.$employeeName : __($status));
                            } elseif ($isApproved) {
                                $badgeClass = 'bg-success text-white';
                                $label = __('Approved');
                                $popover = 'Approved';
                            } else {
                                $badgeClass = 'bg-light text-dark border';
                                $label = __($status);
                                $popover = $status === 'Sendback' ? $employeeName : __($status);
                            }
                        @endphp
                        <div class="task-row goal-item" data-status="{{ $rowStatus }}">
                            <div class="row align-items-start mx-0 w-100">
                                <div class="col-md-3 mb-2 mb-md-0 px-1 pt-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Employee</span>
                                    <div class="fw-bold text-dark col-value lh-sm">{{ $task->employee->fullname }}</div>
                                    <div class="text-muted small-text">{{ $task->employee->employee_id }}</div>
                                </div>
                                <div class="col-6 col-md-2 mb-md-0 px-1 pt-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Initiated Date</span>
                                    <span class="text-dark fw-medium d-block col-value">{{ $createdAt ? $createdAt : '-' }}</span>
                                </div>
                                <div class="col-6 col-md-2 mb-md-0 px-1 pt-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Last Updated</span>
                                    <span class="text-dark fw-medium d-block col-value">{{ $updatedAt ? $updatedAt : '-' }}</span>
                                    @if($updatedBy) <span class="text-muted small-text d-block">by {{ $updatedBy->name }}</span> @endif
                                </div>
                                <div class="col-12 col-md-2 mb-md-0 mt-2 mt-md-0 px-1 pt-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Status</span>
                                    <a href="javascript:void(0)" data-bs-id="{{ $employeeId }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $popover }}" class="badge {{ $badgeClass }} rounded-pill py-1 px-3 text-decoration-none fw-medium">{{ $label }}</a>
                                </div>
                                <div class="col-12 col-md-3 d-flex flex-nowrap gap-2 justify-content-md-end align-items-center mt-md-0 px-1 pt-1">
                                    @if ($period == $goalPeriod && $formStatus != 'Draft' && $status != 'Sendback' && !$appraisalCheck && $goals)
                                        <a id="reviseGoalBtn{{ $goalId }}"
                                            class="btn btn-sm btn-outline-warning fw-semibold rounded-pill px-3
                                            {{ (Auth::user()->employee_id == ($firstSubordinate->initiated->employee_id ?? null)) ? '' : 'd-none' }}"

                                            href="{{ route('goals.edit', $goalId) }}"

                                            data-has-achievement="{{
                                                optional(
                                                    $firstSubordinate->goal
                                                )->hasAchievement
                                                    ? 1
                                                    : 0
                                            }}">

                                            {{ __('Revise Goal') }}

                                        </a>
                                    @endif

                                    @if ($period == $goalPeriod && $task->employee->employee_id == Auth::user()->employee_id || !$subordinates->isNotEmpty() || $formStatus == 'Draft')
                                        @if ($formStatus == 'submitted' || $formStatus == 'Approved' || $appraisalCheck)
                                            <a href="javascript:void(0)" class="btn btn-light text-secondary border btn-sm rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                        @endif
                                        <a class="btn btn-sm btn-outline-primary fw-semibold rounded-pill px-3 {{ (Auth::user()->employee_id == ($firstSubordinate->initiated->employee_id ?? null)) ? '' : 'd-none' }}" href="{{ route('team-goals.edit', $goalId) }}" onclick="showLoader()">{{ __('Edit') }}</a>
                                    @else
                                        @if ($period == $goalPeriod && $approverId == Auth::user()->employee_id && $status === 'Pending' || $sendbackTo == Auth::user()->employee_id && $status === 'Sendback' || !$subordinates->isNotEmpty() || (Auth::user()->employee_id == ($firstSubordinate->initiated->employee_id ?? null)) && $status === 'Sendback' && $task->employee->employee_id != Auth::user()->employee_id)
                                            <a class="btn btn-sm btn-outline-primary fw-semibold rounded-pill px-3 {{ (Auth::user()->employee_id == ($firstSubordinate->initiated->employee_id ?? null)) ? '' : 'd-none' }}" href="{{ route('team-goals.edit', $goalId) }}" onclick="showLoader()">{{ $status === 'Sendback' ? __('Revise Goal') : __('Edit') }}</a>
                                            
                                            @if ($status != 'Sendback' && Auth::user()->employee_id != ($firstSubordinate->initiated->employee_id ?? null) && !$appraisalCheck)
                                                <a href="{{ route('team-goals.approval', $goalId) }}" class="btn btn-sm btn-primary fw-medium rounded-pill px-3" onclick="showLoader()">Approve Goal</a>
                                                <a href="javascript:void(0)" class="btn btn-light text-secondary border btn-sm rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                            @endif
                                        @elseif ($period == $goalPeriod && $status === 'Approved' && !$appraisalCheck)
                                            {{-- <a class="btn btn-sm btn-outline-warning fw-semibold rounded-pill px-3" href="{{ route('team-goals.edit', $goalId) }}" onclick="showLoader()">{{ __('Revise Goal') }}</a> --}}
                                            <a href="javascript:void(0)" class="btn btn-light text-secondary border btn-sm rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                        @else
                                            <a href="javascript:void(0)" class="btn btn-light text-secondary border btn-sm rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @foreach ($notasks as $index => $notask)
                        @php
                            $managerL1 = $notask?->employee?->managerL1;
                            $employeeId = $notask->employee_id;
                            $accessMenu = json_decode($notask->employee->access_menu ?? '{}', true);
                            $goals = $accessMenu['goals'] ?? null;
                        @endphp
                        <div class="task-row goal-item" data-status="not-initiated">
                            <div class="row align-items-start mx-0 w-100">
                                <div class="col-md-3 mb-2 mb-md-0 px-1 pt-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Employee</span>
                                    <div class="fw-bold text-dark col-value lh-sm">{{ $notask->employee->fullname }}</div>
                                    <div class="text-muted small-text">{{ $notask->employee->employee_id }}</div>
                                </div>
                                <div class="col-6 col-md-2 mb-md-0 px-1 pt-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Initiated Date</span>
                                    <span class="text-muted fw-medium d-block col-value">-</span>
                                </div>
                                <div class="col-6 col-md-2 mb-md-0 px-1 pt-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Last Updated</span>
                                    <span class="text-muted fw-medium d-block col-value">-</span>
                                </div>
                                <div class="col-12 col-md-2 mb-md-0 mt-2 mt-md-0 px-1 pt-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Status</span>
                                    <a href="javascript:void(0)" data-bs-id="{{ $employeeId }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Employee has not set goals yet." class="badge bg-light text-secondary border rounded-pill py-1 px-3 text-decoration-none fw-medium">Not Initiated</a>
                                </div>
                                <div class="col-12 col-md-3 d-flex flex-nowrap gap-2 justify-content-md-end align-items-center mt-md-0 px-1 pt-1">
                                    @if ((!$filterYear || $filterYear == $period) && $goals && $notask->isManager)
                                        <button data-id="{{ encrypt($notask->employee->employee_id) }}" id="initiateBtn{{ $index }}" class="btn btn-outline-primary fw-medium rounded-pill btn-sm px-3">{{ __('Initiate') }}</button>
                                    @else
                                        <a href="javascript:void(0)" data-bs-id="{{ $employeeId }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Manager L1 : {{ $managerL1 ? $managerL1->fullname.' ('.$managerL1->employee_id.')' : '-' }}" class="badge bg-warning text-dark fw-bold rounded-pill py-2 px-3 text-decoration-none">View L1</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div id="goal-empty-state" class="py-5 text-center d-none">
                        <i class="ri-folder-open-line text-muted d-block mb-2" style="font-size: 3rem;"></i>
                        <h6 class="text-muted fw-bold">No Goals Found</h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-achievement" role="tabpanel">
            <ul class="nav mb-2 flex-wrap" id="ach-filters">
                <li><button class="btn sub-tab-btn active" data-filter="all">All Task <span class="badge ms-1 count-all">0</span></button></li>
                <li><button class="btn sub-tab-btn" data-filter="draft">Draft <span class="badge ms-1 count-draft">0</span></button></li>
                <li><button class="btn sub-tab-btn" data-filter="not-initiated">Not Initiated <span class="badge ms-1 count-not-initiated">0</span></button></li>
                <li><button class="btn sub-tab-btn" data-filter="revision">Waiting for Revision <span class="badge ms-1 count-revision">0</span></button></li>
                <li><button class="btn sub-tab-btn" data-filter="pending">Waiting for Approval <span class="badge ms-1 count-pending">0</span></button></li>
                <li><button class="btn sub-tab-btn" data-filter="approved">Approved <span class="badge ms-1 count-approved">0</span></button></li>
            </ul>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center py-2 gap-2">
                    <h5 class="m-0 fw-bold text-primary">Achievements {{ $filterYear ?? $period }}</h5>
                     <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-soft-primary fw-medium" data-bs-toggle="modal" data-bs-target="#importAchievementModal">
                            <i class="ri-upload-cloud-2-line me-1"></i> Import Achievement
                        </button>
                        {{-- @if (count($tasks))
                        <form action="" method="POST" class="m-0">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ Auth()->user()->employee_id }}">
                            <input type="hidden" name="filterYear" value="{{ $filterYear ?? $period }}">
                            <button type="submit" class="btn btn-sm btn-outline-secondary fw-medium"><i class="ri-file-text-line me-1"></i> Achievement Data</button>
                        </form>
                        @endif --}}
                        {{-- @if (count($notasks)) --}}
                        <form action="{{ route('team-goals.achievement') }}" method="POST" class="m-0">
                            @csrf

                            @foreach($tasks->pluck('employee_id')->unique() as $employeeId)
                                <input
                                    type="hidden"
                                    name="employee_id[]"
                                    value="{{ $employeeId }}"
                                >
                            @endforeach

                            <input
                                type="hidden"
                                name="filterYear"
                                value="{{ $filterYear ?? $period }}"
                            >

                            <button
                                type="submit"
                                class="btn btn-sm btn-outline-secondary fw-medium"
                            >
                                <i class="ri-download-cloud-2-line me-1"></i>
                                Report Achievement
                            </button>
                        </form>
                        {{-- @endif --}}
                    </div>
                </div>
                <div class="card-body px-3 py-1" id="ach-container">
                    @foreach ($tasks as $index => $task)
                        @php
                            $subordinates = $task->subordinates;
                            $firstSubordinate = $task->subordinates
                            ->first(function ($subordinate) {
                                return optional($subordinate->goal)->hasAchievement;
                            });
                            
                            if(!$firstSubordinate) continue;
                            if(!$firstSubordinate->isFirstLayer) continue;

                            $goalId = $firstSubordinate->goal->id;
                            $appraisalCheck = $firstSubordinate->appraisalCheck;
                            $status = $firstSubordinate->status;
                            $isApproved = $status == 'Approved';
                            
                            $achievement = $firstSubordinate->goal->achievement_status ?? [];
                            $achievementStatus = $achievement['approval_status'] ?? null;
                            $achievementInfo = $achievement['approval_info'] ?? null;
                            $approver = $achievement['current_approver_employee'] ?? '-';
                            $date = isset($achievement['approval_date']) ? \Carbon\Carbon::parse($achievement['approval_date'])->format('d M Y H:i') : '-';
                            $achievementCreatedBy = $achievement['created_by'] ?? null;
                            $employeeId = $firstSubordinate->employee_id;

                            $achRowStatus = 'draft';
                            if ($achievementStatus == 'Approved') $achRowStatus = 'approved';
                            elseif ($achievementStatus == 'Pending') $achRowStatus = 'pending';
                            elseif ($achievementStatus == 'Rejected' || $achievementStatus == 'Sendback') $achRowStatus = 'revision';

                            $achBadgeClass = match ($achievementStatus) {
                                'Approved' => 'bg-success text-white',
                                'Pending' => 'bg-warning text-dark',
                                'Rejected', 'Sendback' => 'bg-danger text-white',
                                default => 'bg-light text-secondary border'
                            };

                            $achLabel = $achievementInfo && $achievementStatus == 'Draft' ? 'Waiting for revision' : ($achievementStatus == 'Pending' ? 'Waiting for approval' : ($achievementStatus ?? 'Not Initiated'));
                            $achPopover = $achievementStatus != 'Approved' ? "
                                <strong>Approver:</strong> {$approver}<br>
                                <strong>Status:</strong> {$achLabel}<br>
                            " : "
                                <strong>Approver:</strong> {$approver}<br>
                                <strong>Status:</strong> {$achievementStatus}<br>
                                <strong>Approval Date:</strong> {$date}
                            ";
                            $showAchPopover = !in_array($achievementStatus, [null, 'Draft']);

                            $goalFormStatus = $firstSubordinate->goal->form_status ?? null;
                            $goalSendbackTo = $firstSubordinate->sendback_to ?? null;
                            
                            if ($goalFormStatus == 'Draft' || $goalSendbackTo == $employeeId) {
                                $goalBadgeClass = 'bg-secondary text-white';
                                $goalLabel = ($goalFormStatus == 'Draft') ? 'Draft' : 'Waiting Your Revision';
                            } elseif ($appraisalCheck || $status == 'Pending') {
                                $goalBadgeClass = 'bg-warning text-dark';
                                $goalLabel = $appraisalCheck ? 'Auto Approved' : __($status);
                            } elseif ($isApproved) {
                                $goalBadgeClass = 'bg-success text-white';
                                $goalLabel = __('Approved');
                            } else {
                                $goalBadgeClass = 'bg-light text-dark border';
                                $goalLabel = __($status);
                            }
                        @endphp
                        <div class="task-row ach-item" data-status="{{ $achRowStatus }}">
                            <div class="row align-items-start w-100 mx-0">
                                <div class="col-md-3 mb-2 mb-md-0 px-1 pt-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Employee</span>
                                    <div class="fw-bold text-dark col-value lh-sm">{{ $task->employee->fullname }}</div>
                                    <div class="text-muted small-text">{{ $task->employee->employee_id }}</div>
                                </div>
                                <div class="col-12 col-md-3 mb-2 mb-md-0 px-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Goal Status</span>
                                    <span class="badge {{ $goalBadgeClass }} rounded-pill py-1 px-3 fw-medium">{{ $goalLabel }}</span>
                                </div>
                                <div class="col-12 col-md-2 mb-md-0 px-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Achievement Status</span>
                                    <a href="javascript:void(0)" data-bs-id="{{ $employeeId }}" class="badge {{ $achBadgeClass }} rounded-pill py-1 px-3 text-decoration-none fw-medium" @if($showAchPopover) data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-html="true" data-bs-content="{!! $achPopover !!}" @endif>{{ __($achLabel) }}</a>
                                </div>
                                <div class="col-12 col-md-4 d-flex flex-nowrap gap-2 justify-content-md-end align-items-center mt-2 mt-md-0 px-1 pt-1">
                                    @if($isApproved || $appraisalCheck)
                                        @if ($firstSubordinate->goal->hasAchievement && $firstSubordinate->isFirstLayer && ($achievementStatus == 'Pending' || !$achievementInfo))
                                            <a href="{{ ($achievementStatus === 'Approved' || $achievementCreatedBy === Auth::user()->id) ? route('goals.update-achievement', $goalId) : route('goals.approval-achievement', $goalId) }}" class="btn btn-sm btn-outline-primary fw-medium rounded-pill px-3">
                                                {{ ($achievementStatus === 'Approved' || $achievementCreatedBy === Auth::user()->id) ? 'Update Achievement' : 'Approve Achievement' }}
                                            </a>
                                        @endif
                                    @else
                                        <span class="d-inline-block" tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Goal must be approved before updating achievement.">
                                            <button class="btn btn-sm btn-outline-secondary fw-medium rounded-pill px-3 opacity-50" type="button" disabled style="pointer-events: none;">Update Achievement</button>
                                        </span>
                                    @endif
                                    <a href="javascript:void(0)" class="btn btn-light text-secondary border btn-sm rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @foreach ($noAchievements ?? [] as $index => $achievementList)
                        @php
                            if(!$achievementList->isFirstLayer) continue;

                            $hasData = $achievementList ? true : false;
                            $isDraft = $achievementList?->goal?->form_status == 'Draft';
                            $isSendbackSelf = ($achievementList?->sendback_to != null) && ($achievementList?->sendback_to == $achievementList?->employee_id);
                            $isAutoApproved = $achievementList?->appraisalCheck;
                            $isApproved = $achievementList?->status == 'Approved';
                            $isPending = $achievementList?->status == 'Pending';
                            $employeeId = $achievementList?->employee_id;
                            $goalId = $achievementList?->goal?->id;

                            if (!$hasData) {
                                $badgeClass = 'bg-light text-secondary border';
                            } elseif ($isDraft || $isSendbackSelf) {
                                $badgeClass = 'bg-secondary text-white';
                            } elseif ($isAutoApproved || $isPending) {
                                $badgeClass = 'bg-warning text-dark';
                            } elseif ($isApproved) {
                                $badgeClass = 'bg-success text-white';
                            } else {
                                $badgeClass = 'bg-light text-dark border';
                            }

                            $label = !$hasData ? 'No Data' : ($isDraft ? 'Draft' : ($isAutoApproved ? 'Auto Approved' : ($isApproved ? __('Approved') : ($isSendbackSelf ? 'Waiting Your Revision' : __($achievementList?->status)))));

                            $popover = '';
                            if (!$hasData) {
                                $popover = 'No Data';
                            } elseif ($isDraft) {
                                $popover = 'Draft';
                            } elseif ($isAutoApproved) {
                                $popover = '(Goals were auto-approved after you submitted PA '.$achievementList?->goal?->period.')';
                            } elseif ($achievementList?->approvalLayer && !$isApproved) {
                                $popover = 'Manager L'.$achievementList?->approvalLayer.' : '.$achievementList?->name;
                            } elseif ($achievementList?->status === 'Sendback') {
                                $popover = $achievementList?->name;
                            } else {
                                $popover = 'Approved';
                            }
                        @endphp
                        <div class="task-row ach-item" data-status="not-initiated">
                            <div class="row align-items-start w-100 mx-0">
                                <div class="col-md-3 mb-2 mb-md-0 px-1 pt-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Employee</span>
                                    <div class="fw-bold text-dark col-value lh-sm">{{ $achievementList?->employee?->fullname ?? '-' }}</div>
                                    <div class="text-muted small-text">{{ $achievementList?->employee?->employee_id ?? '-' }}</div>
                                </div>
                                <div class="col-12 col-md-3 mb-2 mb-md-0 px-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Goal Status</span>
                                    <a href="javascript:void(0)" data-bs-id="{{ $employeeId }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $popover }}" class="badge {{ $badgeClass }} rounded-pill py-1 px-3 text-decoration-none fw-medium">{{ $label }}</a>
                                </div>
                                <div class="col-12 col-md-2 mb-md-0 px-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Achievement Status</span>
                                    <span class="badge bg-light text-secondary border rounded-pill py-1 px-3 text-decoration-none fw-medium">Not Initiated</span>
                                </div>
                                <div class="col-12 col-md-4 d-flex flex-nowrap gap-2 justify-content-md-end align-items-center mt-2 mt-md-0 px-1 pt-1">
                                    @php
                                        $formData = json_decode($achievementList->goal->form_data, true);

                                        $hasReviewPeriod = collect($formData)->contains(function ($item) {
                                            return isset($item['review_period']) && !empty(trim($item['review_period']));
                                        });
                                    @endphp

                                    @if($hasReviewPeriod)
                                        @if($isApproved || $isAutoApproved)
                                            <a href="{{ route('goals.update-achievement', $achievementList?->form_id) }}" class="btn btn-sm btn-outline-warning fw-medium rounded-pill px-3">Update Achievement</a>
                                        @else
                                            <span class="d-inline-block" tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Goal must be approved before updating achievement.">
                                                <button class="btn btn-sm btn-outline-secondary fw-medium rounded-pill px-3 opacity-50" type="button" disabled style="pointer-events: none;">Update Achievement</button>
                                            </span>
                                        @endif
                                        @if($goalId)
                                            <a href="javascript:void(0)" class="btn btn-light text-secondary border btn-sm rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @foreach ($notasks as $index => $notask)
                        @php
                            if (!$notask->isManager) continue;
                            $employeeId = $notask->employee_id;
                        @endphp
                        <div class="task-row ach-item" data-status="not-initiated">
                            <div class="row align-items-start w-100 mx-0">
                                <div class="col-md-3 mb-2 mb-md-0 px-1 pt-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Employee</span>
                                    <div class="fw-bold text-dark col-value lh-sm">{{ $notask->employee->fullname }}</div>
                                    <div class="text-muted small-text">{{ $notask->employee->employee_id }}</div>
                                </div>
                                <div class="col-12 col-md-3 mb-2 mb-md-0 px-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Goal Status</span>
                                    <a href="javascript:void(0)" data-bs-id="{{ $employeeId }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Employee has not set goals yet." class="badge bg-light text-secondary border rounded-pill py-1 px-3 text-decoration-none fw-medium">Not Initiated</a>
                                </div>
                                <div class="col-12 col-md-2 mb-md-0 px-1">
                                    <span class="text-uppercase d-block mb-1 col-label fw-semibold">Achievement Status</span>
                                    <span class="badge bg-light text-secondary border rounded-pill py-1 px-3 text-decoration-none fw-medium">Not Initiated</span>
                                </div>
                                <div class="col-12 col-md-4 d-flex flex-nowrap gap-2 justify-content-md-end align-items-center mt-2 mt-md-0 px-1 pt-1">
                                    <span class="d-inline-block" tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Goal must be approved before updating achievement.">
                                        <button class="btn btn-sm btn-outline-secondary fw-medium rounded-pill px-3 opacity-50" type="button" disabled style="pointer-events: none;">Update Achievement</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div id="ach-empty-state" class="py-5 text-center d-none">
                        <i class="ri-medal-line text-muted d-block mb-2" style="font-size: 3rem;"></i>
                        <h6 class="text-muted fw-bold">No Achievements Found</h6>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</div>

@foreach ($tasks as $task)
    @php
        $subordinates = $task->subordinates;
        $firstSubordinate = $subordinates->isNotEmpty() ? $subordinates->first() : null;
        if (!$firstSubordinate) continue;
        $goalId = $firstSubordinate->goal->id;
        $formDataArr = $firstSubordinate->goal->form_data_parsed ?? [];
        $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
        $reviewPeriodOption = $reviewPeriodOption ?? [];
        $calculationMethodOption = $calculationMethodOption ?? [];
    @endphp
    <div class="modal fade" id="modalDetail{{ $goalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h6 class="modal-title text-dark fw-bold mb-0"><i class="ri-file-list-3-line me-2 text-primary"></i>Achievement Details - {{ $task->employee->fullname }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-white">
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
                                    <span class="fw-bold text-dark col-value">{{ number_format(
                                                $row['target'],
                                                0
                                            ) ?? '-' }}</span>
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
                </div>
                <div class="modal-footer bg-light border-top py-2">
                    <button type="button" class="btn btn-sm btn-light border fw-medium px-4 text-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark m-0" id="importModalLabel"><i class="ri-upload-cloud-2-line me-2 text-primary"></i>Import Goal</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importGoal" action="{{ route('importgoalsmanager') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert bg-primary-subtle border-0 shadow-sm mb-4">
                        <strong class="d-block mb-2 text-primary" style="font-size: 0.85rem;"><i class="ri-information-line me-1"></i> Notes:</strong>
                        <ul class="mb-0 ps-3 text-dark" style="font-size: 0.85rem;">
                            <li>{{ __('Note Import Goal Manager') }}<strong><br> > Tab "{{ __('Not Initiated') }}" -> {{ __('Download') }}</strong></li>
                        </ul>
                    </div>
                    <div class="form-group mb-0">
                        <label for="file" class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">Upload File</label>
                        <input type="file" name="file" id="file" class="form-control form-control-sm shadow-sm" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2">
                    <button type="button" class="btn btn-sm btn-light text-secondary border fw-medium px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="importGoalsButton" class="btn btn-sm btn-primary fw-medium px-4">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="importAchievementModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark m-0" id="importModalLabel"><i class="ri-upload-cloud-2-line me-2 text-primary"></i>Import Achievement</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('importAchievement.submit') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert bg-primary-subtle border-0 shadow-sm mb-4">
                        <strong class="d-block mb-2 text-primary" style="font-size: 0.85rem;"><i class="ri-information-line me-1"></i> Notes:</strong>
                        <ul class="mb-0 ps-3 text-dark" style="font-size: 0.85rem;">
                            <li>{{ __('Note Import Achievement Manager') }}<strong><br> "Report Achievement"</strong></li>
                        </ul>
                    </div>
                    <div class="form-group mb-3">
                        <label for="year" class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">Year Period</label>
                        <select name="year" id="year" class="form-control form-control-sm shadow-sm">
                            <option value="{{ now()->subYear()->format('Y') }}"
                                {{ old('period', now()->format('Y')) == now()->subYear()->format('Y') ? 'selected' : '' }}>
                                {{ now()->subYear()->format('Y') }}
                            </option>
    
                            <option value="{{ now()->format('Y') }}"
                                {{ old('period', now()->format('Y')) == now()->format('Y') ? 'selected' : '' }}>
                                {{ now()->format('Y') }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label for="file" class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">Upload File</label>
                        <input type="file" name="file" id="file" class="form-control form-control-sm shadow-sm" required>
                        <input type="hidden" name="type" id="type" value="team">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2">
                    <button type="button" class="btn btn-sm btn-light text-secondary border fw-medium px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="importGoalsButton" class="btn btn-sm btn-primary fw-medium px-4">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if(
    Session::has('error') &&
    is_array(Session::get('error')) &&
    isset(Session::get('error')['message'])
)
<script>
  document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
      icon: "error",
      title: "{{ Session::get('error')['title'] ?? 'Error' }}",
      text: "{{ Session::get('error')['message'] }}",
      confirmButtonText: "OK",
    });
  });
</script>
@endif
<script>

    document.addEventListener(
        'click',
        function(e){

            const button =
                e.target.closest(
                    '[id^="reviseGoalBtn"]'
                );

            if(!button){
                return;
            }

            // STOP href default dulu
            e.preventDefault();

            const hasAchievement =
                Number(
                    button.dataset
                        .hasAchievement
                );

            // langsung redirect jika tidak ada achievement
            if(
                hasAchievement !== 1
            ){

                showLoader();

                window.location =
                    button.href;

                return;
            }

            // popup jika ada achievement
            Swal.fire({

                icon:'warning',

                title:
                    'Revise Goal?',

                html:`
                    Changes to goals /
                    targets will reset
                    the current
                    achievement progress.

                    <br><br>

                    Existing achievements
                    including Draft,
                    Pending Approval
                    and Approved
                    will be returned
                    to Pending.

                    <br><br>

                    Continue?
                `,

                showCancelButton:true,

                confirmButtonText:
                    'Continue',

                cancelButtonText:
                    'Cancel',

                confirmButtonColor:
                    '#AB2F2B',
                reverseButtons: true,

            }).then(result => {

                if(
                    result.isConfirmed
                ){

                    showLoader();

                    window.location =
                        button.href;
                }

            });

        }
    );

</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll('#mainTab button[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (e) {
            localStorage.setItem('activeTeamGoalTab', e.target.id);
        });
    });

    const savedTabId = localStorage.getItem('activeTeamGoalTab');
    if (savedTabId) {
        const savedTab = document.getElementById(savedTabId);
        if (savedTab) {
            const bsTab = new bootstrap.Tab(savedTab);
            bsTab.show();
        }
    }

    function calculateAndRenderCounts(containerId, filterButtonsId, itemClass, emptyStateId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const allItems = container.querySelectorAll('.' + itemClass);
        const counts = { 'all': allItems.length, 'draft': 0, 'revision': 0, 'pending': 0, 'approved': 0, 'not-initiated': 0 };

        allItems.forEach(item => {
            const status = item.getAttribute('data-status');
            if (counts[status] !== undefined) counts[status]++;
        });

        const filterWrapper = document.getElementById(filterButtonsId);
        if (filterWrapper) {
            Object.keys(counts).forEach(key => {
                const badge = filterWrapper.querySelector(`.count-${key}`);
                if (badge) badge.innerText = counts[key];
            });
        }

        const filterBtns = filterWrapper.querySelectorAll('.sub-tab-btn');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                filterBtns.forEach(b => {
                    b.classList.remove('active');
                });
                
                this.classList.add('active');

                const targetFilter = this.getAttribute('data-filter');
                let visibleCount = 0;

                allItems.forEach(item => {
                    if (targetFilter === 'all' || item.getAttribute('data-status') === targetFilter) {
                        item.style.display = 'flex';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });

                const emptyState = document.getElementById(emptyStateId);
                if (emptyState) {
                    emptyState.classList.toggle('d-none', visibleCount > 0);
                }
            });
        });
    }

    calculateAndRenderCounts('goal-container', 'goal-filters', 'goal-item', 'goal-empty-state');
    calculateAndRenderCounts('ach-container', 'ach-filters', 'ach-item', 'ach-empty-state');

    document.querySelectorAll('.mini-progress-bar').forEach(function (el) {
        setTimeout(() => {
            el.style.width = el.dataset.width;
        }, 100);
    });
});
</script>
@endpush