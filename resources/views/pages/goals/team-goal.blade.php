@extends('layouts_.vertical', ['page_title' => 'Team Goals'])

@section('css')
<style>
.kpi-label { color: #9e2a2b; font-size: 0.7rem; letter-spacing: 0.5px; text-transform: uppercase; }
.read-only-month { background-color: #ffffff; border: 1px solid #e9ecef; border-radius: 8px; padding: 10px 4px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); min-width: 80px; flex-shrink: 0; }
.read-only-month.has-value { background-color: #e0f3ff; border-color: #0d6efd; }
.mini-progress { height: 4px; background: #e9ecef; border-radius: 10px; overflow: hidden; margin-top: 4px; }
.mini-progress-bar { height: 100%; border-radius: 10px; background: linear-gradient(90deg, #0d6efd 25%, #88c6f9 50%, #0d6efd 75%); background-size: 200% 100%; animation: progressFlow 1.5s linear infinite; }
@keyframes progressFlow { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.month-tracking-container { display: flex; overflow-x: auto; gap: 10px; padding-bottom: 10px; }
.month-tracking-container::-webkit-scrollbar { height: 6px; }
.month-tracking-container::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
.sub-tab-btn { font-size: 0.85rem; font-weight: 500; border-radius: 50px; padding: 6px 16px; transition: all 0.2s; }
.sub-tab-btn.active { background-color: #0d6efd !important; color: white !important; border-color: #0d6efd !important; }
.sub-tab-btn.active .badge { background-color: white !important; color: #0d6efd !important; }
.task-row { border-bottom: 1px solid #f1f5f9; padding: 1rem 0; }
.task-row:last-child { border-bottom: none; }
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

    <div class="row my-2 align-items-center">
        <div class="col-md">
            <ul class="nav nav-pills gap-2" id="mainTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active px-4 fw-semibold border" id="tab-goal-btn" data-bs-toggle="tab" data-bs-target="#tab-goal" type="button" role="tab">
                        <i class="ri-target-line me-1"></i> Goals
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-4 fw-semibold border bg-white text-secondary" id="tab-achievement-btn" data-bs-toggle="tab" data-bs-target="#tab-achievement" type="button" role="tab">
                        <i class="ri-medal-line me-1"></i> Achievements
                    </button>
                </li>
            </ul>
        </div>
        <div class="col-md-auto mt-3 mt-md-0">
            <button type="button" class="btn btn-primary shadow" data-bs-toggle="modal" data-bs-target="#importModal">Import Goals</button>
        </div>
    </div>

    <div class="bg-white p-2 rounded shadow-sm border mb-2">
        <form id="formYearGoal" action="{{ route('team-goals') }}" method="GET" class="row align-items-end justify-content-between g-2">
            @php $filterYear = request('filterYear'); @endphp
            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold mb-1" for="filterYear">{{ __('Year') }}</label>
                <select name="filterYear" id="filterYear" onchange="yearGoal(this)" class="form-select form-select-sm">
                    @if ($period) <option value="{{ $period }}" {{ $period == $filterYear ? 'selected' : '' }}>{{ $period }}</option> @endif
                    @foreach ($selectYear as $year)
                        <option value="{{ $year->period }}" {{ $year->period == $filterYear ? 'selected' : '' }}>{{ $year->period }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                    <input type="text" name="customsearch" id="customsearch" class="form-control border-start-0" placeholder="Search employee...">
                </div>
            </div>
        </form>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-goal" role="tabpanel">
            <ul class="nav mb-3 gap-2 flex-wrap" id="goal-filters">
                <li><button class="btn btn-outline-secondary btn-sm sub-tab-btn active" data-filter="all">All Task <span class="badge bg-secondary ms-1 count-all">0</span></button></li>
                <li><button class="btn btn-outline-secondary btn-sm sub-tab-btn" data-filter="draft">Draft <span class="badge bg-secondary ms-1 count-draft">0</span></button></li>
                <li><button class="btn btn-outline-secondary btn-sm sub-tab-btn" data-filter="revision">Waiting for Revision <span class="badge bg-danger ms-1 count-revision">0</span></button></li>
                <li><button class="btn btn-outline-secondary btn-sm sub-tab-btn" data-filter="pending">Waiting for Approval <span class="badge bg-warning text-dark ms-1 count-pending">0</span></button></li>
                <li><button class="btn btn-outline-secondary btn-sm sub-tab-btn" data-filter="approved">Approved <span class="badge bg-success ms-1 count-approved">0</span></button></li>
                <li><button class="btn btn-outline-secondary btn-sm sub-tab-btn" data-filter="not-initiated">Not Initiated <span class="badge bg-dark ms-1 count-not-initiated">0</span></button></li>
            </ul>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary-subtle d-flex justify-content-between align-items-center py-2">
                    <h5 class="m-0 text-dark fw-bold"><i class="ri-target-line me-1"></i> Goals {{ $filterYear ?? $period }}</h5>
                    <div class="d-flex gap-2">
                        @if (count($tasks))
                        <form action="{{ route('team-goals.initiated') }}" method="POST">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ Auth()->user()->employee_id }}">
                            <input type="hidden" name="filterYear" value="{{ $filterYear ?? $period }}">
                            <button type="submit" class="btn btn-sm btn-success"><i class="ri-download-cloud-2-line me-1"></i> Data</button>
                        </form>
                        @endif
                        @if (count($notasks))
                        <form action="{{ route('team-goals.notInitiated') }}" method="POST">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ Auth()->user()->employee_id }}">
                            <input type="hidden" name="filterYear" value="{{ $filterYear ?? $period }}">
                            <button type="submit" class="btn btn-sm btn-outline-success"><i class="ri-download-cloud-2-line me-1"></i> Template</button>
                        </form>
                        @endif
                    </div>
                </div>
                <div class="card-body p-2" id="goal-container">
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
                                $badgeClass = 'bg-dark-subtle text-secondary';
                                $label = 'No Data';
                                $popover = 'No Data';
                            } elseif ($isDraft || $isSendbackSelf) {
                                $badgeClass = 'bg-secondary';
                                $label = $isDraft ? 'Draft' : 'Waiting Your Revision';
                                $popover = $isDraft ? 'Draft' : 'Waiting Your Revision';
                            } elseif ($isAutoApproved || $isPending) {
                                $badgeClass = 'bg-warning text-dark';
                                $label = $isAutoApproved ? 'Auto Approved' : __($status);
                                $popover = $isAutoApproved ? '(Goals were auto-approved after you submitted PA '.$goalPeriod.')' : ($approvalLayer ? 'Manager L'.$approvalLayer.' : '.$employeeName : __($status));
                            } elseif ($isApproved) {
                                $badgeClass = 'bg-success';
                                $label = __('Approved');
                                $popover = 'Approved';
                            } else {
                                $badgeClass = 'text-bg-light';
                                $label = __($status);
                                $popover = $status === 'Sendback' ? $employeeName : __($status);
                            }
                        @endphp
                        <div class="task-row goal-item" data-status="{{ $rowStatus }}">
                            <div class="row align-items-center mx-1 w-100">
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <div class="fw-bold text-dark">{{ $task->employee->fullname }}</div>
                                    <div class="text-muted small">{{ $task->employee->employee_id }}</div>
                                </div>
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.65rem;">Initiated Date</small>
                                    <span class="text-dark fw-medium small">{{ $createdAt ? $createdAt : '-' }}</span>
                                </div>
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.65rem;">Last Updated</small>
                                    <span class="text-dark fw-medium small">{{ $updatedAt ? $updatedAt : '-' }}</span>
                                    @if($updatedBy) <div class="text-muted" style="font-size: 0.6rem;">by {{ $updatedBy->name }}</div> @endif
                                </div>
                                <div class="col-6 col-md-2">
                                    <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem;">Status</small>
                                    <a href="javascript:void(0)" data-bs-id="{{ $employeeId }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $popover }}" class="badge {{ $badgeClass }} rounded-pill py-1 px-2 text-decoration-none">{{ $label }}</a>
                                </div>
                                <div class="col-12 col-md-3 d-flex flex-wrap gap-1 justify-content-md-end mt-2 mt-md-0">
                                    @if ($period == $goalPeriod && $formStatus != 'Draft' && $status != 'Sendback' && !$appraisalCheck && $goals)
                                        <a class="btn btn-sm btn-outline-warning fw-semibold {{ (Auth::user()->employee_id == ($firstSubordinate->initiated->employee_id ?? null)) ? '' : 'd-none' }}" href="{{ route('goals.edit', $goalId) }}" onclick="showLoader()">{{ __('Revise Goals') }}</a>
                                    @endif

                                    @if ($period == $goalPeriod && $task->employee->employee_id == Auth::user()->employee_id || !$subordinates->isNotEmpty() || $formStatus == 'Draft')
                                        @if ($formStatus == 'submitted' || $formStatus == 'Approved' || $appraisalCheck)
                                            <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                        @endif
                                        <a class="btn btn-sm btn-outline-warning fw-semibold {{ (Auth::user()->employee_id == ($firstSubordinate->initiated->employee_id ?? null)) ? '' : 'd-none' }}" href="{{ route('team-goals.edit', $goalId) }}" onclick="showLoader()">{{ __('Edit') }}</a>
                                    @else
                                        @if ($period == $goalPeriod && $approverId == Auth::user()->employee_id && $status === 'Pending' || $sendbackTo == Auth::user()->employee_id && $status === 'Sendback' || !$subordinates->isNotEmpty() || (Auth::user()->employee_id == ($firstSubordinate->initiated->employee_id ?? null)) && $status === 'Sendback' && $task->employee->employee_id != Auth::user()->employee_id)
                                            <a class="btn btn-sm btn-outline-warning fw-semibold {{ (Auth::user()->employee_id == ($firstSubordinate->initiated->employee_id ?? null)) ? '' : 'd-none' }}" href="{{ route('team-goals.edit', $goalId) }}" onclick="showLoader()">{{ $status === 'Sendback' ? __('Revise Goals') : __('Edit') }}</a>
                                            
                                            @if ($status != 'Sendback' && Auth::user()->employee_id != ($firstSubordinate->initiated->employee_id ?? null) && !$appraisalCheck)
                                                <a href="{{ route('team-goals.approval', $goalId) }}" class="btn btn-sm btn-primary fw-medium" onclick="showLoader()">Approve Goal</a>
                                                <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                            @endif
                                        @elseif ($period == $goalPeriod && $status === 'Approved' && !$appraisalCheck)
                                            <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                        @else
                                            <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
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
                            <div class="row align-items-center mx-1 w-100">
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <div class="fw-bold text-dark">{{ $notask->employee->fullname }}</div>
                                    <div class="text-muted small">{{ $notask->employee->employee_id }}</div>
                                </div>
                                <div class="col-6 col-md-4 mb-2 mb-md-0">
                                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.65rem;">Date of Joining</small>
                                    <span class="text-dark fw-medium small">{{ $notask->formatted_doj }}</span>
                                </div>
                                <div class="col-6 col-md-2">
                                    <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem;">Status</small>
                                    <a href="javascript:void(0)" data-bs-id="{{ $employeeId }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Employee has not set goals yet." class="badge bg-dark-subtle text-dark rounded-pill py-1 px-2 text-decoration-none">Not Initiated</a>
                                </div>
                                <div class="col-12 col-md-3 d-flex flex-wrap gap-1 justify-content-md-end mt-2 mt-md-0">
                                    @if ((!$filterYear || $filterYear == $period) && $goals && $notask->isManager)
                                        <button data-id="{{ encrypt($notask->employee->employee_id) }}" id="initiateBtn{{ $index }}" class="btn btn-outline-primary btn-sm">{{ __('Initiate') }}</button>
                                    @else
                                        <a href="javascript:void(0)" data-bs-id="{{ $employeeId }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Manager L1 : {{ $managerL1 ? $managerL1->fullname.' ('.$managerL1->employee_id.')' : '-' }}" class="badge bg-warning text-dark fw-bold rounded-pill py-1 px-2 text-decoration-none">view L1</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div id="goal-empty-state" class="p-5 text-center d-none">
                        <i class="ri-folder-open-line text-muted mb-2" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">No Goals Found</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-achievement" role="tabpanel">
            <ul class="nav mb-3 gap-2 flex-wrap" id="ach-filters">
                <li><button class="btn btn-outline-secondary btn-sm sub-tab-btn active" data-filter="all">All Task <span class="badge bg-secondary ms-1 count-all">0</span></button></li>
                <li><button class="btn btn-outline-secondary btn-sm sub-tab-btn" data-filter="draft">Draft <span class="badge bg-secondary ms-1 count-draft">0</span></button></li>
                <li><button class="btn btn-outline-secondary btn-sm sub-tab-btn" data-filter="revision">Waiting for Revision <span class="badge bg-danger ms-1 count-revision">0</span></button></li>
                <li><button class="btn btn-outline-secondary btn-sm sub-tab-btn" data-filter="pending">Waiting for Approval <span class="badge bg-warning text-dark ms-1 count-pending">0</span></button></li>
                <li><button class="btn btn-outline-secondary btn-sm sub-tab-btn" data-filter="approved">Approved <span class="badge bg-success ms-1 count-approved">0</span></button></li>
                <li><button class="btn btn-outline-secondary btn-sm sub-tab-btn" data-filter="not-initiated">Not Initiated <span class="badge bg-dark ms-1 count-not-initiated">0</span></button></li>
            </ul>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary-subtle py-2">
                    <h6 class="m-0 text-dark fw-bold"><i class="ri-medal-line me-1"></i> Achievements {{ $filterYear ?? $period }}</h6>
                </div>
                <div class="card-body p-2" id="ach-container">
                    @foreach ($tasks as $index => $task)
                        @php
                            $subordinates = $task->subordinates;
                            $firstSubordinate = $task->subordinates
                            ->first(function ($subordinate) {
                                return optional($subordinate->goal)->hasAchievement;
                            });
                            if(!$firstSubordinate) continue;

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

                            if(!$isApproved && !$appraisalCheck) continue;

                            $achRowStatus = 'draft';
                            if ($achievementStatus == 'Approved') $achRowStatus = 'approved';
                            elseif ($achievementStatus == 'Pending') $achRowStatus = 'pending';
                            elseif ($achievementStatus == 'Rejected' || $achievementStatus == 'Sendback') $achRowStatus = 'revision';

                            $achBadgeClass = match ($achievementStatus) {
                                'Approved' => 'bg-success',
                                'Pending' => 'bg-warning text-dark',
                                'Rejected', 'Sendback' => 'bg-danger',
                                default => 'bg-secondary'
                            };

                            $achLabel = $achievementInfo && $achievementStatus == 'Draft' ? 'Waiting for revision' : ($achievementStatus == 'Pending' ? 'Waiting for approval' : $achievementStatus);
                            $achPopover = $achievementStatus != 'Approved' ? "
                                <strong>Approver:</strong> {$approver}<br>
                                <strong>Status:</strong> {$achLabel}<br>
                            " : "
                                <strong>Approver:</strong> {$approver}<br>
                                <strong>Status:</strong> {$achievementStatus}<br>
                                <strong>Approval Date:</strong> {$date}
                            ";
                            $showAchPopover = !in_array($achievementStatus, [null, 'Draft']);

                            $goalBadgeClass = $appraisalCheck ? 'bg-warning text-dark' : 'bg-success';
                            $goalLabel = $appraisalCheck ? 'Auto Approved' : 'Approved';
                        @endphp
                        <div class="task-row ach-item" data-status="{{ $achRowStatus }}">
                            <div class="row align-items-center w-100">
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <div class="fw-bold text-dark">{{ $task->employee->fullname }}</div>
                                    <div class="text-muted small">{{ $task->employee->employee_id }}</div>
                                </div>
                                <div class="col-12 col-md-5 d-flex gap-4 mb-2 mb-md-0">
                                    <div>
                                        <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem;">Goal Status</small>
                                        <span class="badge {{ $goalBadgeClass }} rounded-pill py-1 px-2">{{ $goalLabel }}</span>
                                    </div>
                                    <div>
                                        <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem;">Achievement Status</small>
                                        <a href="javascript:void(0)" data-bs-id="{{ $employeeId }}" class="badge {{ $achBadgeClass }} rounded-pill py-1 px-2 text-decoration-none" @if($showAchPopover) data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-html="true" data-bs-content="{!! $achPopover !!}" @endif>{{ $achLabel }}</a>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 d-flex flex-wrap gap-1 justify-content-md-end mt-2 mt-md-0">
                                    @if ($firstSubordinate->goal->hasAchievement && $firstSubordinate->isFirstLayer && ($achievementStatus == 'Pending' || !$achievementInfo))
                                        <a href="{{ ($achievementStatus === 'Approved' || $achievementCreatedBy === Auth::user()->id) ? route('goals.update-achievement', $goalId) : route('goals.approval-achievement', $goalId) }}" class="btn btn-sm btn-success fw-medium">
                                            {{ ($achievementStatus === 'Approved' || $achievementCreatedBy === Auth::user()->id) ? 'Update Achievement' : 'Approve Achievement' }}
                                        </a>
                                    @endif
                                    <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $goalId }}"><i class="ri-file-text-line"></i></a>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @foreach ($noAchievements ?? [] as $index => $achievementList)
                        @php
                            $hasData = $achievementList;
                            $isDraft = $achievementList->goal->form_status == 'Draft';
                            $isSendbackSelf = $achievementList->sendback_to == $achievementList->employee_id;
                            $isAutoApproved = $achievementList->appraisalCheck;
                            $isApproved = $achievementList->status == 'Approved';
                            $isPending = $achievementList->status == 'Pending';
                            $employeeId = $achievementList->employee_id;

                            if (!$hasData) {
                                $badgeClass = 'bg-dark-subtle text-secondary';
                            } elseif ($isDraft || $isSendbackSelf) {
                                $badgeClass = 'bg-secondary';
                            } elseif ($isAutoApproved || $isPending) {
                                $badgeClass = 'bg-warning text-dark';
                            } elseif ($isApproved) {
                                $badgeClass = 'bg-success';
                            } else {
                                $badgeClass = 'bg-light text-dark';
                            }

                            $label = !$hasData ? 'No Data' : ($isDraft ? 'Draft' : ($isAutoApproved ? 'Auto Approved' : ($isApproved ? __('Approved') : ($isSendbackSelf ? 'Waiting Your Revision' : __($achievementList->status)))));

                            $popover = '';
                            if (!$hasData) {
                                $popover = 'No Data';
                            } elseif ($isDraft) {
                                $popover = 'Draft';
                            } elseif ($isAutoApproved) {
                                $popover = '(Goals were auto-approved after you submitted PA '.$achievementList->goal->period.')';
                            } elseif ($achievementList->approvalLayer && !$isApproved) {
                                $popover = 'Manager L'.$achievementList->approvalLayer.' : '.$achievementList->name;
                            } elseif ($achievementList->status === 'Sendback') {
                                $popover = $achievementList->name;
                            } else {
                                $popover = 'Approved';
                            }
                        @endphp
                        <div class="task-row ach-item" data-status="not-initiated">
                            <div class="row align-items-center w-100">
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <div class="fw-bold text-dark">{{ $achievementList->employee->fullname }}</div>
                                    <div class="text-muted small">{{ $achievementList->employee->employee_id }}</div>
                                </div>
                                <div class="col-12 col-md-5 d-flex gap-4 mb-2 mb-md-0">
                                    <div>
                                        <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem;">Goal Status</small>
                                        <a href="javascript:void(0)" data-bs-id="{{ $employeeId }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $popover }}" class="badge {{ $badgeClass }} rounded-pill py-1 px-2 text-decoration-none">{{ $label }}</a>
                                    </div>
                                    <div>
                                        <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem;">Achievement Status</small>
                                        <span class="badge bg-light text-secondary border rounded-pill py-1 px-2 text-decoration-none">Not Initiated</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 d-flex flex-wrap gap-1 justify-content-md-end mt-2 mt-md-0">
                                    @if($isApproved || $isAutoApproved)
                                        <a href="{{ route('goals.update-achievement', $achievementList->form_id) }}" class="btn btn-sm btn-success fw-medium">Update Achievement</a>
                                    @else
                                        <span class="d-inline-block" tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Goal must be approved before updating achievement.">
                                            <button class="btn btn-sm btn-secondary fw-medium" type="button" disabled style="pointer-events: none;">Update Achievement</button>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div id="ach-empty-state" class="p-5 text-center d-none">
                        <i class="ri-medal-line text-muted mb-2" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">No Achievements Found</h5>
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
                <div class="modal-header bg-light border-bottom px-4">
                    <h5 class="modal-title text-dark fw-bold mb-0"><i class="ri-file-list-3-line me-2 text-primary"></i>Achievement Details - {{ $task->employee->fullname }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-white">
                    @if ($firstSubordinate->goal->achievement_status)
                        <div class="alert alert-warning border-0 p-3">
                            <strong class="d-block mb-1"><i class="ri-feedback-line me-1"></i> Revision Notes:</strong>
                            <span class="text-dark">{{ $firstSubordinate->goal->achievement_status['approval_info'] }}</span>
                        </div>
                    @endif
                    @if(!empty($formDataArr) && is_array($formDataArr))
                        @foreach ($formDataArr as $kpiIndex => $row)
                        <div class="p-4 {{ $loop->even ? 'bg-light-subtle' : 'bg-white' }} {{ $loop->last ? '' : 'border-bottom' }}">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <small class="fw-bold text-uppercase d-block kpi-label mb-1">KPI {{ $kpiIndex + 1 }}</small>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">{{ $row['kpi'] ?? '-' }}</h6>
                                    <p class="text-secondary mb-0 mt-2" style="white-space: pre-line; font-size: 0.85rem; line-height: 1.5;">{{ $row['description'] ?? '-' }}</p>
                                </div>
                                <div class="col-md-7">
                                    <div class="row g-3 mb-3">
                                        <div class="col-3">
                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Target</small>
                                            <span class="fw-bold text-dark">{{ $row['target'] ?? '-' }}</span>
                                        </div>
                                        <div class="col-3">
                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">UoM</small>
                                            <span class="fw-bold text-dark">{{ (isset($row['uom']) && $row['uom'] !== 'Other') ? $row['uom'] : ($row['custom_uom'] ?? '-') }}</span>
                                        </div>
                                        <div class="col-3">
                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Weightage</small>
                                            <span class="fw-bold text-dark">{{ $row['weightage'] ?? '0' }}%</span>
                                        </div>
                                        <div class="col-3">
                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Achievement</small>
                                            <span class="fw-bold text-dark d-block">{{ $row['achievement'] ?? '0' }}%</span>
                                            @php $percent = (int) ($row['achievement'] ?? 0); @endphp
                                            <div class="mini-progress">
                                                <div class="mini-progress-bar bg-success" data-width="{{ $percent }}%"></div>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Type</small>
                                            <span class="fw-bold text-dark">{{ $row['type'] ?? '-' }}</span>
                                        </div>
                                        <div class="col-3">
                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Review Period</small>
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
                                            <span class="fw-bold text-dark">{{ $rvLabel }}</span>
                                        </div>
                                        <div class="col-3">
                                            <small class="fw-bold text-uppercase d-block kpi-label mb-1">Calc Method</small>
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
                                            <span class="fw-bold text-dark">{{ $rvCalcLabel }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <h6 class="fw-bold text-uppercase kpi-label mb-2">{{ __('Achievement Tracking') }}</h6>
                                <div class="month-tracking-container">
                                    @foreach($months as $monthNum => $monthLabel)
                                        @php
                                            $value = $row['ach'][$monthNum] ?? null;
                                            $formatted = is_null($value) || $value === '' ? '-' : rtrim(rtrim($value, '0'), '.');
                                            $file = $row['attachment'][$monthNum] ?? null;
                                        @endphp
                                        <div class="read-only-month {{ $value ? 'has-value' : '' }}">
                                            <span class="text-uppercase fw-bold text-secondary d-block mb-1" style="font-size: 0.65rem;">{{ $monthLabel }}</span>
                                            <span class="fw-bold text-dark d-block" style="font-size: 1.1rem;">{{ $formatted }}</span>
                                            @if($file)
                                                <a href="{{ asset('storage/'.$file) }}" target="_blank" class="d-block mt-1 text-info small fw-semibold border border-info rounded p-1 text-decoration-none" style="font-size: 0.6rem;">VIEW</a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="p-5 text-center text-muted">
                            <i class="ri-inbox-2-line fs-1 d-block mb-2"></i> No details available.
                        </div>
                    @endif
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary fw-medium" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

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
                    <div class="alert alert-info">
                        <strong>Notes:</strong>
                        <ul class="mb-0">
                            <li>{{ __('Note Import Goal Manager') }}<strong><br> > Tab "{{ __('Not Initiated') }}" -> {{ __('Download') }}</strong></li>
                        </ul>
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

@push('scripts')
@if(Session::has('error'))
<script>
  document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
      icon: "error",
      title: "{{ Session::get('error')['title'] }}",
      text: "{{ Session::get('error')['message'] }}",
      confirmButtonText: "OK",
    });
  });
</script>
@endif

<script>
document.addEventListener("DOMContentLoaded", function () {
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
                filterBtns.forEach(b => b.classList.remove('active', 'btn-primary'));
                filterBtns.forEach(b => b.classList.add('btn-outline-secondary'));
                
                this.classList.remove('btn-outline-secondary');
                this.classList.add('active', 'btn-primary');

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