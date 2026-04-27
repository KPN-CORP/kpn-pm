@extends('layouts_.vertical', ['page_title' => 'Goals'])

@section('css')
<style>
.goal-card {
    overflow: hidden;
    transition:
        opacity 0.25s ease-in-out,
        transform 0.25s ease-in-out,
        max-height 0.75s cubic-bezier(0.4, 0, 0.2, 1),
        margin 0.25s,
        padding 0.25s;
    will-change: opacity, transform, max-height;
    opacity: 1;
    transform: translateY(0);
    max-height: 5000px; /* fallback for large content, can be overridden inline */
}

.goal-card.is-hiding {
    opacity: 0;
    transform: translateY(16px);
    max-height: 0 !important;
    margin: 0 !important;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    pointer-events: none;
}

.goal-card.is-showing {
    opacity: 1;
    transform: translateY(0);
    max-height: 5000px; /* or set via JS for dynamic content */
    padding-top: 1rem;
    padding-bottom: 1rem;
    pointer-events: auto;
}

.goal-card.is-gone {
    display: none !important;
}
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
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="mandatory-field">
            <div id="alertField" class="alert alert-danger alert-dismissible {{ Session::has('error') ? '':'fade' }}" role="alert" {{ Session::has('error') ? '':'hidden' }}>
                <strong>{{ Session::get('error')['message'] ?? null }}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <form id="formYearGoal" action="{{ route('goals') }}" method="GET">
            @php
                $filterYear = request('filterYear');
            @endphp
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
                <div>
                    <label class="form-label mb-1 fw-bold text-muted" style="font-size: 0.85rem;" for="filterYear">{{ __('Year') }}</label>
                    <select name="filterYear" id="filterYear" onchange="filterGoals(this.value)" class="form-select border-secondary shadow-sm" style="width: 150px; cursor: pointer;">
                        <option value="">{{ __('Select all') }}</option>
                        @foreach ($selectYear as $year)
                            <option value="{{ $year->year }}" {{ $year->year == $filterYear ? 'selected' : '' }}>
                                {{ $year->year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <a href="{{ $access ? route('goals.form', encrypt(Auth::user()->employee_id)) : '#' }}" onclick="showLoader()" class="btn {{ $access ? 'btn-primary shadow-sm' : 'btn-secondary-subtle disabled' }} fw-medium">
                        <i class="ri-add-line me-1"></i> {{ __('Create Goal') }}
                    </a>
                </div>
            </div>
        </form>
        @forelse ($data as $goalIndex => $row)
            @php
                $formData = json_decode($row->request->goal['form_data'], true);
                $achievement = $row->request->achievement_status ?? [];

                $status = $achievement['approval_status'] ?? null;
                $approver = $achievement['current_approver_employee'] ?? '-';
                $date = isset($achievement['approval_date'])
                ? \Carbon\Carbon::parse($achievement['approval_date'])->format('d M Y H:i')
                : '-';
                $achievementCreatedBy = $achievement['created_by'] ?? null;
            @endphp
            <div class="card shadow-sm mb-4 py-0 goal-card border-0" data-year="{{ $row->request->period }}">
            <div class="card-header bg-white py-2 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
                <h5 class="m-0 font-weight-bold text-primary">{{ __('Goal') }} {{ $row->request->period }}</h5>
                @if ($period == $row->request->goal->period && !$row->request->appraisalCheck && $access)
                    @if (Auth::user()->employee_id == $row->request->initiated->employee_id)
                        <div class="d-flex flex-wrap gap-2">
                            @if (!$achievement || $status === 'Approved' || $achievementCreatedBy ?? $achievementCreatedBy === Auth::id())
                                <a class="btn btn-outline-{{ $row->request->goal->form_status === 'Approved' ? 'success fw-semibold' : 'secondary' }} btn-sm" href="{{ route('goals.update-achievement', $row->request->goal->id) }}">
                                    {{ __('Update Achievement') }}
                                </a>
                            @endif
                                @if (
                                    $row->request->goal->form_status != 'Draft' &&
                                    $row->request->created_by == Auth::user()->id
                                )
                                    <a class="btn btn-outline-warning btn-sm fw-semibold"
                                    href="{{ route('goals.edit', $row->request->goal->id) }}"
                                    onclick="showLoader()">
                                    {{ __('Revise Goals') }}
                                    </a>
                                @elseif (
                                    $row->request->goal->form_status == 'Draft' ||
                                    ($row->request->status == 'Pending' && count($row->request->approval) == 0) ||
                                    $row->request->sendback_to == $row->request->employee_id
                                )
                                    </a>
                                    <a class="btn btn-outline-warning btn-sm fw-semibold"
                                    href="{{ route('goals.edit', $row->request->goal->id) }}"
                                    onclick="showLoader()">
                                    {{ $row->request->status === 'Sendback' ? __('Revise Goals') : __('Edit') }}
                                    </a>
                                @endif
                            </div>
                            @else
                                <!-- Hide the button if the current user is not the initiated employee -->
                                <span class="d-none"></span>
                            @endif
                        @endif
                    </div>
                    <div class="card-body p-3">
                        <div id="alertDraft" class="alert alert-danger alert-dismissible {{ $row->request->goal->form_status == 'Draft' ? '':'fade d-none' }}" role="alert">
                            <div class="d-flex align-items-center gap-2 text-primary">
                                <i class="ri-error-warning-line fs-4"></i>
                                <strong class="mb-0">{{ $period == $row->request->goal->period && !$row->request->appraisalCheck && $access ? __('Draft Goal Alert Message Open') : __('Draft Goal Alert Message Closed') }}</strong>
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-lg col-md-4 col-6">
                                 <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">{{ __('Initiated By') }}</small>
                                <span class="text-dark fw-medium">{{ $row->request->initiated->name.' ('.$row->request->initiated->employee_id.')' }}</span>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                 <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">{{ __('Initiated Date') }}</small>
                                <span class="text-dark fw-medium">{{ $row->request->formatted_created_at }}</span>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                 <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">{{ __('Last Updated On') }}</small>
                                <span class="text-dark fw-medium">{{ $row->request->formatted_updated_at }}</span>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">{{ __('Adjusted By') }}</small>
                                <span class="text-dark fw-medium">{{ $row->request->updatedBy ? $row->request->updatedBy->name.' '.$row->request->updatedBy->employee_id : '-' }}{{ $row->request->updated_by != auth()->user()->id && empty($adjustByManager) && auth()->check() && auth()->user()->roles->isNotEmpty() && $period == $row->request->goal->period && $row->request->initiated->employee_id != $row->request->employee_id ? ' (Admin)': '' }}</span>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.7rem;">Goal Status</small>
                                <div>
                                    <a href="javascript:void(0)" data-bs-id="{{ $row->request->employee_id }}" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                        data-bs-content="{{
                                            $row->request->goal->form_status == 'Draft'
                                                ? 'Draft'
                                                : ($row->request->appraisalCheck
                                                    ? '(Goals were auto-approved after you submitted PA '.$row->request->period .')'
                                                    : ($row->approvalLayer && $row->request->status != 'Approved'
                                                        ? 'Manager L'.$row->approvalLayer.' : '.$row->name
                                                        : ($row->request->status === 'Sendback' ? $row->name : 'Approved')
                                                    )
                                                )
                                        }}"
                                        class="badge {{ $row->request->goal->form_status == 'Draft' || $row->request->sendback_to == $row->request->employee_id ? 'bg-secondary' : ($row->request->appraisalCheck || $row->request->status == 'Pending' ? 'bg-warning' : ($row->request->status == 'Approved' ? 'bg-success' : 'text-bg-light'))}} rounded-pill py-1 px-2 d-inline-block text-truncate" style="max-width: 100%;">
                                        {{
                                            $row->request->goal->form_status == 'Draft'
                                                ? 'Draft'
                                                : ($row->request->appraisalCheck
                                                    ? 'Auto Approved'
                                                    : ($row->request->status == 'Approved'
                                                        ? __('Approved')
                                                        : ($row->request->sendback_to == $row->request->employee_id
                                                            ? 'Waiting Your Revision'
                                                            : __($row->request->status)
                                                        )
                                                    )
                                                )
                                        }}
                                    </a>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.7rem;">Achievement Status</small>
                                <div>
                                    @php
                                        $label = $status ?? 'No Data';

                                        $badgeClass = match ($status) {
                                            'Approved' => 'bg-success',
                                            'Pending' => 'bg-warning',
                                            'Rejected' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };

                                        $userId = Auth::user()->id;

                                        $popover = match (true) {
                                            $status === 'Approved' => "
                                                <strong>Approver:</strong> {$approver}<br>
                                                <strong>Status:</strong> {$label}<br>
                                                <strong>Approval Date:</strong> {$date}
                                            ",

                                            $status === 'Pending' => "
                                                <strong>Approver:</strong> {$approver}<br>
                                                <strong>Status:</strong> {$label}<br>
                                            ",

                                            $status === 'Draft' && $achievementCreatedBy !== $userId => "
                                                <strong>Created By:</strong> {$approver}<br>
                                                <strong>Status:</strong> {$label}<br>
                                            ",

                                            default => ""
                                        };

                                    @endphp

                                    <a href="javascript:void(0)"
                                    data-bs-id="{{ $row->request->employee_id }}"
                                    class="badge {{ $badgeClass }} rounded-pill py-1 px-2 d-inline-block text-truncate"
                                    style="max-width: 100%;"

                                    data-bs-toggle="popover"
                                    data-bs-trigger="hover focus"
                                    data-bs-html="true"
                                    data-bs-content="{!! $popover !!}"
                                    >
                                        {{ __($label) }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        @if ($row->request->sendback_messages && $row->request->sendback_to == $row->request->employee_id && !$row->request->appraisalCheck)
                            <div class="alert alert-warning border-0 mt-3 p-3">
                                <strong class="d-block mb-1"><i class="ri-feedback-line me-1"></i> Revision Notes:</strong>
                                <span class="text-dark">{{ $row->request->sendback_messages }}</span>
                            </div>
                        @endif
                        <div class="card-footer bg-light border-top d-flex p-2">
                            <a data-bs-toggle="collapse" href="#collapse{{ $goalIndex }}" aria-expanded="false" aria-controls="collapse{{ $goalIndex }}" class="btn btn-link text-primary text-decoration-none fw-semibold btn-sm">
                                Details <i class="ri-arrow-down-s-line align-middle"></i>
                            </a>
                        </div>
                    </div>
                    <div class="collapse" id="collapse{{ $goalIndex }}">
                        <div class="card-body p-0">
                            @if ($row->formData)
                                @php
                                    $months = [
                                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                                        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                                        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                                    ];
                                @endphp

                                @foreach ($row->formData as $index => $data)

                                    <div class="p-4 pt-0 {{ $loop->even ? 'bg-light-subtle' : 'bg-white' }} {{ $loop->last ? '' : 'border-bottom' }}">
                                       <div class="row g-3 mb-2">
                                            <div class="col-md-5 col-lg-5 mb-md-0">
                                                <small class="fw-bold text-uppercase d-block kpi-label mb-1">KPI {{ $index + 1 }}</small>
                                                <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">{{ $data['kpi'] }}</h6>
                                                <p class="text-secondary mb-0" style="white-space: pre-line; font-size: 0.75rem; line-height: 1.5;">{{ $data['description'] ?? '-' }}</p>
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
                                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['weightage'] }}%</span>
                                                    </div>
                                                    @if(isset($data['review_period']))
                                                    <div class="col-3 col-sm-3">
                                                        <small class="fw-bold text-uppercase d-block kpi-label mb-1">Achievement</small>

                                                        <span class="fw-bold text-dark d-block" style="font-size: 0.95rem;">
                                                            {{ $data['achievement'] ?? '0' }}%
                                                        </span>

                                                        @php
                                                            $percent = (int) ($data['achievement'] ?? 0);
                                                        @endphp

                                                        <div class="mini-progress">
                                                            <div class="mini-progress-bar bg-success"
                                                                data-width="{{ $percent }}%"></div>
                                                        </div>
                                                    </div>
                                                    @else
                                                    <div class="col-3 col-sm-3">
                                                        <small class="fw-bold text-uppercase d-block kpi-label mb-1">Type</small>
                                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['type'] }}</span>
                                                    </div>
                                                    @endif
                                                </div>

                                                @if(isset($data['review_period']))
                                                <div class="row g-3 mb-3">
                                                    <div class="col-4 col-sm-4">
                                                        <small class="fw-bold text-uppercase d-block kpi-label mb-1">Type</small>
                                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $data['type'] }}</span>
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
                                                @endif
                                            </div>
                                        </div>
                                        @if(isset($data['review_period']))
                                        <div class="mb-2">
                                            <h6 class="fw-bold text-uppercase kpi-label">{{ __('Achievement Tracking') }}</h6>
                                            <div class="row g-2">
                                                @foreach($months as $monthNum => $monthLabel)

                                                @php
                                                    $value = $data['ach'][$monthNum] ?? null;

                                                    $formatted = is_null($value) || $value === ''
                                                        ? '-'
                                                        : rtrim(rtrim($value, '0'), '.');
                                                @endphp

                                                <div class="col-4 col-sm-3 col-md-2 col-lg-1 ">
                                                    <div class="read-only-month pb-1">

                                                        <span class="text-uppercase fw-bold text-secondary d-block mb-1" style="font-size: 0.65rem;">
                                                            {{ $monthLabel }}
                                                        </span>

                                                        <span class="fw-bold text-dark" style="font-size: 1.1rem;">
                                                            {{ $formatted }}
                                                        </span>
                                                        {{-- VIEW ATTACHMENT --}}
                                                        @if(!empty($data['attachment'][$monthNum]))
                                                            <a href="{{ asset('storage/'.$data['attachment'][$monthNum]) }}"
                                                                target="_blank"
                                                                class="btn-attach-mini w-100 d-block mt-2 border border-info text-info"
                                                                title="View Attachment">
                                                                <i class="ri-file-line"></i> VIEW
                                                            </a>
                                                        @endif

                                                    </div>
                                                </div>
                                            @endforeach
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="p-4 text-center text-muted">No form data available.</div>
                            @endif
                        </div>
                    </div>
                </div>

        @empty
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body text-center py-5">
                    <i class="ri-file-list-3-line text-muted fs-1 d-block mb-2"></i>
                    <h5 class="text-dark fw-medium">{{ __('No Goals Found') }}</h5>
                    <p class="text-muted mb-0">{{ __('Please Create Your Goals to start tracking performance.') }}</p>
                </div>
            </div>
        @endforelse
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
  function hideCard(card) {
    if (card.classList.contains('is-gone')) return;

    card.classList.remove('is-showing');
    card.classList.add('is-hiding');

    const onEnd = (e) => {
      if (e.propertyName !== 'max-height') return;
      card.classList.add('is-gone');
      card.removeEventListener('transitionend', onEnd);
    };
    card.addEventListener('transitionend', onEnd);
  }

  function showCard(card) {
    card.classList.remove('is-gone');

    // force reflow biar transisi jalan
    card.offsetHeight;

    card.classList.remove('is-hiding');
    card.classList.add('is-showing');
  }

  function filterGoals(year) {
    const cards = document.querySelectorAll('.goal-card');
    const selected = (year || '').toString().trim();

    cards.forEach(card => {
      const cardYear = (card.dataset.year || '').toString().trim();
      const shouldShow = !selected || cardYear === selected;

      shouldShow ? showCard(card) : hideCard(card);
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('filterYear');
    if (sel) filterGoals(sel.value);
  });

  window.filterGoals = filterGoals;
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
