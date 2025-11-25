@extends('layouts_.vertical', ['page_title' => 'Propose 360'])

@section('css')
<style>
.dataTables_scrollHeadInner {
    width: 100% !important;
}
.table-responsive, .dataTables_scroll {
    width: 100%;
}
</style>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid mb-3">
    @if(session('success'))
        <div class="alert alert-success mt-3">
            {{ session('success') }}
        </div>
    @endif
    <div class="mandatory-field">
        <div id="alertField" class="alert alert-danger alert-dismissible {{ Session::has('error') ? '':'fade' }}" role="alert" {{ Session::has('error') ? '':'hidden' }}>
            <strong>{{ Session::get('error') }}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @foreach ($self as $id => $row)
    @php
        $approval = $row->approval_request;
        $appraisalYear = $period ?? now()->year;

        $submittedAt = $approval ? \Illuminate\Support\Carbon::parse($approval->created_at)->format('d M Y') : '-';
        $statusClass = $approval ? ($approval->status==='APPROVED'?'success':($approval->status==='REJECTED'?'danger':'warning')) : 'secondary';
        $statusText = $approval ? strtolower($approval->status) : 'draft';

        $hasApproval = !empty($approval);
        $status      = $hasApproval ? strtoupper($approval->status) : 'DRAFT';
        $statusText  = strtolower($status);
        $statusClass = match ($status) {
            'APPROVED' => 'success',
            'REJECTED' => 'danger',
            'PENDING'  => 'warning',
            default    => 'secondary',
        };

        $submittedAt = $hasApproval ? \Illuminate\Support\Carbon::parse($approval->created_at)->format('d M Y') : null;

        // current approver: bisa employee_id (numeric) atau role name (non-numeric)
        $cur     = (string) ($approval->current_approval_id ?? '');
        $isRole  = $hasApproval && $cur !== '' && !ctype_digit($cur);

        if ($isRole) {
            // Jika role: tampilkan semua kandidat "fullname (employee_id)" atau fallback role name
            $cands = $approval->current_approval_candidates ?? [];

            $list = [];

            foreach ($cands as $role => $names) {
                $list[] = $role . ': ' . implode(', ', $names);
            }

            $waitingFor = !empty($list) ? implode(' | ', $list) : $cur;

        } else {
            // Jika employee_id: pakai relasi manager; fallback ke nilai mentah current_approval_id
            $mgr = $approval->manager ?? null;
            $waitingFor = $mgr?->employee_id ? ($mgr->fullname.' ('.$mgr->employee_id.')') : ($cur ?: null);
        }

        $initiator = '';
        $initiatorClass = 'select360';

        $tooltip = null;
        if ($hasApproval) {
            $tooltip = match ($statusText) {
                'pending'  => $waitingFor,
                'approved' => "Approved — diajukan {$submittedAt}",
                'rejected' => "Ditolak — diajukan {$submittedAt}",
                'sendback' => "Waiting for Revision - {$row->approval_request->initiated->name} ({$row->approval_request->initiated->employee_id})",
                default    => $submittedAt ? "Diajukan {$submittedAt} by {$row->approval_request->initiated->name} ({$row->approval_request->initiated->employee_id})" : null,
            };
            // Determine if current user is the initiator
            $isInitiator = Auth::id() == $row->approval_request?->created_by;
            // Enable inputs for the initiator only when status is 'draft' or 'sendback'
            $initiator = ($isInitiator && $statusText == 'sendback') || $statusText == 'draft' ? '' : 'disabled';
            $initiatorClass = ($isInitiator && $statusText == 'sendback') || $statusText == 'draft' ? 'select360' : '';
        }

    @endphp
    @if (!empty($selfEnabled) && $selfEnabled)
        
    <div class="row px-2">
        <div class="col-lg-12 p-0">
            <div class="mt-3 p-2 bg-light-subtle rounded shadow g-3">       
                <div class="row">
                    <div class="col d-flex align-items-center">
                        <h5 class="m-0 mb-3">
                            <a class="text-dark d-block" data-bs-toggle="collapse" href="#dataTasks" role="button" aria-expanded="false" aria-controls="dataTasks">
                                Self<span class="text-muted"></span>
                            </a>
                        </h5>
                    </div>
                </div>
                @if ($approval && $approval->sendback_messages && $row->approval_request->created_by == Auth::id() && $approval->status == 'Sendback')
                <div class="row">
                    <div class="col">
                        <div class="card bg-warning bg-opacity-10 border border-warning mb-2">
                            <div class="row p-2">
                                <div class="col-lg col-sm-12 px-2">
                                    <div class="form-group">
                                        <p class="fw-bold mb-0">Revision Notes :</p>
                                        <p class="mt-1 mb-0">{{ $approval->sendback_messages }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <div class="row">
                    <div class="col">
                        <div class="card mb-0">
                            <div class="card-body p-1 p-md-2 d-flex flex-column gap-2">
                                <input type="hidden" id="employeeId_{{ $row->employee_id }}" value="{{ $row->employee_id }}">
                                <div class="row">
                                    <div class="col-md-4 me-2">
                                        <div class="row">
                                            <div class="col-4">
                                                <p class="m-0">Employee</p>
                                            </div>
                                            <div class="col-auto p-0">
                                                <p class="m-0">:</p>
                                            </div>
                                            <div class="col">
                                                <p class="m-0" data-employee-label>{{ $row->fullname . ' (' . $row->employee_id . ')' }}</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">
                                                <p class="m-0">Designation </p>
                                            </div>
                                            <div class="col-auto p-0">
                                                <p class="m-0">:</p>
                                            </div>
                                            <div class="col">
                                                <p class="m-0">{{ $row->designation_name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 ms-2">
                                        {{-- Belum ditambahkan logic jika ada approval munculkan status --}}
                                        <div class="row">
                                            <div class="col-4">
                                                <p class="m-0">Submitted At</p>
                                            </div>
                                            <div class="col-auto p-0">
                                                <p class="m-0">:</p>
                                            </div>
                                            <div class="col">
                                                <p class="m-0">{{ $approval ? \Illuminate\Support\Carbon::parse($approval->created_at)->format('d M Y') : '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">
                                                <p class="m-0">Status </p>
                                            </div>
                                            <div class="col-auto p-0">
                                                <p class="m-0">:</p>
                                            </div>
                                            <div class="col">
                                                <span class="badge bg-{{ $statusClass }}"
                                                    @if($hasApproval)
                                                        data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $tooltip }}"
                                                    @endif>
                                                    {{ $statusText }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="text-end">
                                        @php 
                                            $isSendback = $approval && strtoupper($approval->status) === 'SENDBACK'; 
                                        @endphp
                                        @if($approval)
                                            @if ($approval->created_by == Auth::id() && strtoupper($approval->status)==='SENDBACK')
                                                <button type="submit"
                                                    form="form-propose-self-{{ $row->employee_id }}"
                                                    class="btn btn-sm btn-warning"
                                                    data-submit>
                                                    <span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>
                                                    {{ __('Revise') }}
                                                </button>                                                    
                                            @endif
                                        @else
                                            {{-- Proposer view: Propose atau Revise --}}
                                            <button type="submit"
                                                    form="form-propose-self-{{ $row->employee_id }}"
                                                    class="btn btn-sm btn-primary"
                                                    data-submit>
                                                <span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>
                                                {{ __('Propose') }}
                                            </button>
                                        @endif
                                        </div>
                                    </div>
                                </div>
                                <form id="form-propose-self-{{ $row->employee_id }}" method="POST" action="{{ ($approval && strtoupper($approval->status)==='SENDBACK')
                    ? route('proposed360.resubmit')
                    : route('proposed360.store') }}">
                                @csrf
                                @if($approval && strtoupper($approval->status)==='SENDBACK')
                                    <input type="hidden" name="form_id" value="{{ $approval->form_id }}">
                                    <input type="hidden" name="mode" value="RESUBMIT"><!-- optional flag -->
                                @endif
                                <input type="hidden" name="scope" value="self">
                                <input type="hidden" id="employeeId_{{ $row->employee_id }}" name="employee_id" value="{{ $row->employee_id }}">
                                <input type="hidden" name="appraisal_year" value="{{ $appraisalYear }}">
                                <div class="row">
                                    <div class="col">
                                        <div class="card bg-light-subtle border border-light shadow-none mb-0">
                                            <div class="card-body p-2">
                                                <div class="row">
                                                    <div class="col-md">
                                                        <div class="">
                                                            <h5>Peers 1</h5>
                                                            @php
                                                                $selectedLayer = collect($row->appraisalLayer)->where('layer', 1)->firstWhere('layer_type', 'peers');
                                                                $pref = old('peers.0')
                                                                    ?? ($approval ? data_get($row, 'selected_peers.0') : data_get($selectedLayer, 'approver_id'));
                                                            @endphp
                                                            <select name="peers[]" id="peer1" class="form-select {{ $initiatorClass }}" required {{ $initiator }}>
                                                                <option value="">- Please Select -</option>
                                                                @foreach ($selfPeers as $item)
                                                                    @continue($item->employee_id == $row->employee_id) {{-- jangan pilih diri sendiri --}}
                                                                    <option value="{{ $item->employee_id }}" @selected($item->employee_id == $pref)>
                                                                        {{ $item->fullname }} ({{ $item->employee_id }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div class="text-danger error-message fs-14"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md">
                                                        <div class="">
                                                            <h5>Peers 2</h5>
                                                            @php
                                                                $selectedLayer = collect($row->appraisalLayer)->where('layer', 2)->firstWhere('layer_type', 'peers');
                                                                $pref = old('peers.1')
                                                                    ?? ($approval ? data_get($row, 'selected_peers.1') : data_get($selectedLayer, 'approver_id'));
                                                            @endphp
                                                            <select name="peers[]" id="peer2" class="form-select {{ $initiatorClass }}" {{ $initiator }}>
                                                                <option value="">- Please Select -</option>
                                                                @foreach ($selfPeers as $item)
                                                                    @continue($item->employee_id == $row->employee_id) {{-- jangan pilih diri sendiri --}}
                                                                    <option value="{{ $item->employee_id }}" @selected($item->employee_id == $pref)>
                                                                        {{ $item->fullname }} ({{ $item->employee_id }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div class="text-danger error-message fs-14"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md">
                                                        <div class="">
                                                            <h5>Peers 3</h5>
                                                            @php
                                                                $selectedLayer = collect($row->appraisalLayer)->where('layer', 3)->firstWhere('layer_type', 'peers');
                                                                $pref = old('peers.2')
                                                                    ?? ($approval ? data_get($row, 'selected_peers.2') : data_get($selectedLayer, 'approver_id'));
                                                            @endphp
                                                            <select name="peers[]" id="peer3" class="form-select {{ $initiatorClass }}" {{ $initiator }}>
                                                                <option value="">- Please Select -</option>
                                                                @foreach ($selfPeers as $item)
                                                                    @continue($item->employee_id == $row->employee_id) {{-- jangan pilih diri sendiri --}}
                                                                    <option value="{{ $item->employee_id }}" @selected($item->employee_id == $pref)>
                                                                        {{ $item->fullname }} ({{ $item->employee_id }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div class="text-danger error-message fs-14"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if ( !empty($subordinates->first()) )
                                <div class="row">
                                    <div class="col">
                                        <div class="card bg-light-subtle border border-light shadow-none mb-0">
                                            <div class="card-body p-2">
                                                <div class="row">
                                                    <div class="col-md">
                                                        <div class="">
                                                            <h5>Subordinate 1</h5>
                                                            @php
                                                                $selectedLayer = collect($row->appraisalLayer)->where('layer', 1)->firstWhere('layer_type', 'subordinate');
                                                                $pref = old('subordinates.0')
                                                                    ?? ($approval ? data_get($row, 'selected_subordinates.0') : data_get($selectedLayer, 'approver_id'));
                                                            @endphp
                                                            <select name="subordinates[]" id="sub1" class="form-select {{ $initiatorClass }}" required {{ $initiator }}>
                                                                <option value="">- Please Select -</option>
                                                                @foreach ($subordinates as $item)
                                                                    <option value="{{ $item->employee_id }}" @selected($item->employee_id == $pref)>
                                                                        {{ $item->fullname }} {{ $item->employee_id }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div class="text-danger error-message fs-14"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md">
                                                        <div class="">
                                                            <h5>Subordinate 2</h5>
                                                            @php
                                                                $selectedLayer = collect($row->appraisalLayer)->where('layer', 2)->firstWhere('layer_type', 'subordinate');
                                                                $pref = old('subordinates.1')
                                                                    ?? ($approval ? data_get($row, 'selected_subordinates.1') : data_get($selectedLayer, 'approver_id'));
                                                            @endphp
                                                            <select name="subordinates[]" id="sub2" class="form-select {{ $initiatorClass }}" {{ $initiator }}>
                                                                <option value="">- Please Select -</option>
                                                                @foreach ($subordinates as $item)
                                                                    <option value="{{ $item->employee_id }}" @selected($item->employee_id == $pref)>
                                                                        {{ $item->fullname }} {{ $item->employee_id }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div class="text-danger error-message fs-14"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md">
                                                        <div class="">
                                                            <h5>Subordinate 3</h5>
                                                            @php
                                                                $selectedLayer = collect($row->appraisalLayer)
                                                                ->where('layer', 3)->firstWhere('layer_type', 'subordinate');
                                                                $pref = old('subordinates.2')
                                                                    ?? ($approval ? data_get($row, 'selected_subordinates.2') : data_get($selectedLayer, 'approver_id'));
                                                            @endphp
                                                            <select name="subordinates[]" id="sub3" class="form-select {{ $initiatorClass }}" {{ $initiator }}>
                                                                <option value="">- Please Select -</option>
                                                                @foreach ($subordinates as $item)
                                                                    <option value="{{ $item->employee_id }}" @selected($item->employee_id == $pref)>
                                                                        {{ $item->fullname }} {{ $item->employee_id }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div class="text-danger error-message fs-14"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endforeach
    @if (!$datas->isEmpty()) {{-- Cek jika ada data peers dan team/subordinate dari user --}}
    <div class="row px-2">
        <div class="col-lg-12 p-0">
            <div class="mt-3 p-2 bg-primary-subtle rounded shadow">       
                <div class="row">
                    <div class="col d-flex align-items-center">
                        <h5 class="m-0">
                            <a class="text-dark d-block" data-bs-toggle="collapse" href="#dataTasks" role="button" aria-expanded="false" aria-controls="dataTasks">
                                Team<span class="text-muted"></span>
                            </a>
                        </h5>
                    </div>
                </div>
                @forelse ($datas as $id => $row)
                @php
                    $approval = $row->approval_request;
                    $appraisalYear = $period;
                    $submittedAt = $approval ? \Illuminate\Support\Carbon::parse($approval->created_at)->format('d M Y') : '-';
                    $statusClass = $approval ? ($approval->status==='APPROVED'?'success':($approval->status==='REJECTED'?'danger':'warning')) : 'secondary';
                    $statusText = $approval ? strtolower($approval->status) : 'draft';

                    $hasApproval = !empty($approval);
                    $status      = $hasApproval ? strtoupper($approval->status) : 'DRAFT';
                    $statusText  = strtolower($status);
                    $statusClass = match ($status) {
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                        'PENDING'  => 'warning',
                        default    => 'secondary',
                    };

                    $submittedAt = $hasApproval ? \Illuminate\Support\Carbon::parse($approval->created_at)->format('d M Y') : null;

                    // current approver: bisa employee_id (numeric) atau role name (non-numeric)
                    $cur     = (string) ($approval->current_approval_id ?? '');
                    $isRole  = $hasApproval && $cur !== '' && !ctype_digit($cur);

                    if ($isRole) {
                        // Jika role: tampilkan semua kandidat "fullname (employee_id)" atau fallback role name
                       $cands = $approval->current_approval_candidates ?? [];

                        $list = [];

                        foreach ($cands as $role => $names) {
                            $list[] = $role . ': ' . implode(', ', $names);
                        }

                        $waitingFor = !empty($list) ? implode(' | ', $list) : $cur;

                    } else {
                        // Jika employee_id: pakai relasi manager; fallback ke nilai mentah current_approval_id
                        $mgr = $approval->manager ?? null;
                        $waitingFor = $mgr?->employee_id ? ($mgr->fullname.' ('.$mgr->employee_id.')') : ($cur ?: null);
                    }

                    $tooltip = null;
                    if ($hasApproval) {
                        $tooltip = match ($statusText) {
                            'pending'  => $waitingFor,
                            'approved' => "Approved — diajukan {$submittedAt}",
                            'rejected' => "Ditolak — diajukan {$submittedAt}",
                            'sendback' => "Waiting for Revision - {$row->approval_request->initiated->name} ({$row->approval_request->initiated->employee_id})",
                            default    => $submittedAt ? "Diajukan {$submittedAt}" : null,
                        };
                    }

                    $isInitiator = Auth::id() == $row->approval_request?->created_by;
                    // Enable inputs for the initiator only when status is 'draft' or 'sendback'
                    $initiator = ($isInitiator && $statusText == 'sendback') || $statusText == 'draft' ? '' : 'disabled';
                    $initiatorClass = ($isInitiator && $statusText == 'sendback') || $statusText == 'draft' ? 'select360' : '';
                @endphp
                <div class="row mt-3">
                    <div class="col">
                        <div class="card mb-0">
                            <div class="card-body p-1 p-md-2 d-flex flex-column gap-2">
                                @if ($approval && $approval->sendback_messages && $row->approval_request->created_by == Auth::id() && $approval->status == 'Sendback')
                                <div class="row">
                                    <div class="col">
                                        <div class="card bg-warning bg-opacity-10 border border-warning mb-2">
                                            <div class="row p-2">
                                                <div class="col-lg col-sm-12 px-2">
                                                    <div class="form-group">
                                                        <p class="fw-bold mb-0">Revision Notes :</p>
                                                        <p class="mt-1 mb-0">{{ $approval->sendback_messages }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                <input type="hidden" id="employeeId_{{ $row->employee_id }}" value="{{ $row->employee_id }}">

                                <div class="row">
                                    <div class="col-md-4 me-2">
                                        <div class="row">
                                            <div class="col-4"><p class="m-0">Employee</p></div>
                                            <div class="col-auto p-0"><p class="m-0">:</p></div>
                                            <div class="col"><p class="m-0" data-employee-label>{{ $row->fullname . ' (' . $row->employee_id . ')' }}</p></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4"><p class="m-0">Designation</p></div>
                                            <div class="col-auto p-0"><p class="m-0">:</p></div>
                                            <div class="col"><p class="m-0">{{ $row->designation_name }}</p></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 ms-2">
                                        <div class="row">
                                            <div class="col-4"><p class="m-0">Submitted At</p></div>
                                            <div class="col-auto p-0"><p class="m-0">:</p></div>
                                            <div class="col"><p class="m-0">{{ $submittedAt }}</p></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4"><p class="m-0">Status</p></div>
                                            <div class="col-auto p-0"><p class="m-0">:</p></div>

                                            <div class="col">
                                                <span class="badge bg-{{ $statusClass }}"
                                                    @if($hasApproval)
                                                        data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $tooltip }}"
                                                    @endif>
                                                    {{ $statusText }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="text-end">
                                            @php $isSendback = $approval && strtoupper($approval->status) === 'SENDBACK'; @endphp

                                            @if($approval)
                                                @if ($approval->manager?->employee_id == Auth::user()->employee_id && strtoupper($approval->status)==='PENDING')
                                                    <form method="POST" action="{{ route('proposed360.action') }}" class="d-inline proposed360-sendback">
                                                        @csrf
                                                        <input type="hidden" name="form_id" value="{{ $approval->form_id }}">
                                                        <input type="hidden" name="action" value="REJECT">
                                                        <input type="hidden" name="sendback_message" value="">
                                                        <button type="button" class="btn btn-sm btn-outline-warning btn-sendback">
                                                            {{ __('Sendback') }}
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('proposed360.action') }}" class="d-inline js-approve-form">
                                                    @csrf
                                                        <input type="hidden" name="form_id" value="{{ $approval->form_id }}">
                                                        <input type="hidden" name="action" value="APPROVE">
                                                        <input type="hidden" name="having_subs" id="havingSubs_{{ $row->employee_id }}" value="{{ !empty($row->subordinates->first()) }}">

                                                        {{-- Hidden holder tempat inject peers[] & subordinates[] sebelum submit --}}
                                                        <span class="js-clone-area"></span>

                                                        <button type="button"
                                                                class="btn btn-sm btn-primary btn-approve"
                                                                data-source-form="form-propose-team-{{ $row->employee_id }}">
                                                            <span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>
                                                            {{ __('Approve') }}
                                                        </button>
                                                    </form>
                                                    
                                                @endif
                                                @if ($approval->created_by == Auth::id() && strtoupper($approval->status)==='SENDBACK')
                                                    <button type="submit"
                                                        form="form-propose-team-{{ $row->employee_id }}"
                                                        class="btn btn-sm btn-warning"
                                                        data-submit>
                                                        <span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>
                                                        {{ __('Revise') }}
                                                    </button>                                                    
                                                @endif
                                            @else
                                                {{-- Proposer view: Propose atau Revise --}}
                                                <button type="submit"
                                                        form="form-propose-team-{{ $row->employee_id }}"
                                                        class="btn btn-sm btn-primary"
                                                        data-submit>
                                                    <span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>
                                                    {{ __('Propose') }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <form id="form-propose-team-{{ $row->employee_id }}" method="POST" action="{{ ($approval && strtoupper($approval->status)==='SENDBACK')
                                    ? route('proposed360.resubmit')
                                    : route('proposed360.store') }}">
                                    @csrf
                                    @if($approval && strtoupper($approval->status)==='SENDBACK')
                                        <input type="hidden" name="form_id" value="{{ $approval->form_id }}">
                                        <input type="hidden" name="mode" value="RESUBMIT"><!-- optional flag -->
                                    @endif
                                    <input type="hidden" name="scope" value="team">
                                    <input type="hidden" name="employee_id" value="{{ $row->employee_id }}">
                                    <input type="hidden" name="appraisal_year" value="{{ $appraisalYear }}">
                                    <div class="row">
                                        <div class="col">
                                            <div class="card bg-light-subtle border border-light shadow-none mb-0">
                                                <div class="card-body p-2">
                                                <div class="row">
                                                    <div class="col-md">
                                                        <h5>Peers 1</h5>
                                                        @php
                                                            $selectedLayer = collect($row->appraisalLayer)->where('layer', 1)->firstWhere('layer_type', 'peers');
                                                            $pref = old('peers.0')
                                                                    ?? ($approval ? data_get($row, 'selected_peers.0') : data_get($selectedLayer, 'approver_id'));
                                                        @endphp
                                                        <select name="peers[]" id="peer1_{{ $id }}" class="form-select {{ $initiatorClass }}" required {{ $initiator }}>
                                                            <option value="">- Please Select -</option>
                                                            @foreach ($row->peer_candidates as $item)
                                                                @continue($item->employee_id == $row->employee_id)
                                                                <option value="{{ $item->employee_id }}" @selected($item->employee_id == $pref)>
                                                                    {{ $item->fullname }} {{ $item->employee_id }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div class="text-danger error-message fs-14"></div>
                                                    </div>
                                                    <div class="col-md">
                                                        <h5>Peers 2</h5>
                                                        @php
                                                            $selectedLayer = collect($row->appraisalLayer)->where('layer', 2)->firstWhere('layer_type', 'peers');
                                                            $pref = old('peers.1')
                                                                    ?? ($approval ? data_get($row, 'selected_peers.1') : data_get($selectedLayer, 'approver_id'));
                                                        @endphp
                                                        <select name="peers[]" id="peer2_{{ $id }}" class="form-select {{ $initiatorClass }}" {{ $initiator }}>
                                                            <option value="">- Please Select -</option>
                                                            @foreach ($row->peer_candidates as $item)
                                                                @continue($item->employee_id == $row->employee_id)
                                                                <option value="{{ $item->employee_id }}" @selected($item->employee_id == $pref)>
                                                                    {{ $item->fullname }} {{ $item->employee_id }}
                                                                </option>
                                                            @endforeach
                                                        </select>

                                                        <div class="text-danger error-message fs-14"></div>
                                                    </div>
                                                    <div class="col-md">
                                                        <h5>Peers 3</h5>
                                                        @php
                                                            $selectedLayer = collect($row->appraisalLayer)->where('layer', 3)->firstWhere('layer_type', 'peers');
                                                            $pref = old('peers.2')
                                                                    ?? ($approval ? data_get($row, 'selected_peers.2') : data_get($selectedLayer, 'approver_id'));
                                                        @endphp
                                                        <select name="peers[]" id="peer3_{{ $id }}" class="form-select {{ $initiatorClass }}" {{ $initiator }}>
                                                            <option value="">- Please Select -</option>
                                                            @foreach ($row->peer_candidates as $item)
                                                                @continue($item->employee_id == $row->employee_id)
                                                                <option value="{{ $item->employee_id }}" @selected($item->employee_id == $pref)>
                                                                    {{ $item->fullname }} {{ $item->employee_id }}
                                                                </option>
                                                            @endforeach
                                                        </select>

                                                        <div class="text-danger error-message fs-14"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if (!empty($row->subordinates->first()))
                                <div class="row">
                                    <div class="col">
                                        <div class="card bg-light-subtle border border-light shadow-none mb-0">
                                            <div class="card-body p-2">
                                                <div class="row">
                                                    <div class="col-md">
                                                        <h5>Subordinate 1</h5>
                                                        @php
                                                            $selectedLayer = collect($row->appraisalLayer)->where('layer', 1)->firstWhere('layer_type','subordinate');
                                                            $pref = old('subordinates.0')
                                                                    ?? ($approval ? data_get($row, 'selected_subordinates.0') : data_get($selectedLayer, 'approver_id'));
                                                        @endphp
                                                        <select name="subordinates[]" id="sub1_{{ $id }}" class="form-select {{ $initiatorClass }}" required {{ $initiator }}>
                                                            <option value="">- Please Select -</option>
                                                            @foreach ($row->subordinates as $item)
                                                                <option value="{{ $item->employee_id }}" @selected($item->employee_id == $pref)>
                                                                    {{ $item->fullname }} {{ $item->employee_id }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div class="text-danger error-message fs-14"></div>
                                                    </div>
                                                    <div class="col-md">
                                                        <h5>Subordinate 2</h5>
                                                        @php
                                                            $selectedLayer = collect($row->appraisalLayer)->where('layer', 2)->firstWhere('layer_type','subordinate');
                                                            $pref = old('subordinates.1')
                                                                    ?? ($approval ? data_get($row, 'selected_subordinates.1') : data_get($selectedLayer, 'approver_id'));
                                                        @endphp
                                                        <select name="subordinates[]" id="sub2_{{ $id }}" class="form-select {{ $initiatorClass }}" {{ $initiator }}>
                                                            <option value="">- Please Select -</option>
                                                            @foreach ($row->subordinates as $item)
                                                                <option value="{{ $item->employee_id }}" @selected($item->employee_id == $pref)>
                                                                    {{ $item->fullname }} {{ $item->employee_id }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div class="text-danger error-message fs-14"></div>
                                                    </div>
                                                    <div class="col-md">
                                                        <h5>Subordinate 3</h5>
                                                        @php
                                                            $selectedLayer = collect($row->appraisalLayer)->where('layer', 3)->firstWhere('layer_type','subordinate');
                                                            $pref = old('subordinates.2')
                                                                    ?? ($approval ? data_get($row, 'selected_subordinates.2') : data_get($selectedLayer, 'approver_id'));
                                                        @endphp
                                                        <select name="subordinates[]" id="sub3_{{ $id }}" class="form-select {{ $initiatorClass }}" {{ $initiator }}>
                                                            <option value="">- Please Select -</option>
                                                            @foreach ($row->subordinates as $item)
                                                                <option value="{{ $item->employee_id }}" @selected($item->employee_id == $pref)>
                                                                    {{ $item->fullname }} {{ $item->employee_id }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div class="text-danger error-message fs-14"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </form>
                            </div>
                        </div>
                    </div>
                </div>
                @empty

                @endforelse
            </div>
        </div>
    </div>
    @endif
    @if (empty($selfEnabled) && !$selfEnabled && $datas->isEmpty())
    <div class="row px-2">
        <div class="col-lg-12 p-0">
            <div class="mt-3 p-4 bg-light-subtle rounded shadow text-center">
                <h5 class="mb-2 text-muted">No Proposed 360 Tasks</h5>
                <p class="mb-0 text-secondary">There are no tasks to propose for 360 appraisal at this time.</p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection