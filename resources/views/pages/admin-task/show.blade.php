@extends('layouts_.vertical', ['page_title' => 'Approval Details'])

@section('content')
<div class="container-fluid">
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif
  @php
    if (ctype_digit($req->current_approval_id)) {
          $roleLabel = "Specific User";
          $waitingList = $req->current_approval_id;

      } else {
          // CASE 2: approval_by_flow (flow_name)
          // resolved_roles = array role dari ApprovalFlowSteps
          $roles = $req->resolved_roles ?? [];

          // Jika ada banyak role → join
          $roleLabel = !empty($roles) ? implode(', ', $roles) : $req->current_approval_id;

          // Kandidat approver
          $candidateMap = $req->approval_candidates ?? [];

          $waitingList = [];
          foreach ($candidateMap as $r => $cands) {
              foreach ($cands as $c) {
                  $waitingList[] = $c; // string: "Nama (id)"
              }
          }

          $waitingList = !empty($waitingList) ? implode(', ', $waitingList) : $roleLabel;
      }
  @endphp
  <div class="card">
    <div class="card-body">
      <h5 class="mb-3">Approval Details</h5>

      <div class="row mb-2">
        <div class="col-md-3 text-muted">Category</div>
        <div class="col">{{ $req->category }}</div>
      </div>
      <div class="row mb-2">
        <div class="col-md-3 text-muted">Period</div>
        <div class="col">{{ $req->period }}</div>
      </div>
      <div class="row mb-2">
        <div class="col-md-3 text-muted">Current Approver</div>
        <div class="col">
          @if(empty($candidates))
          <span class="badge bg-warning">
              {{ $req->manager->fullname.' ('.$req->manager->employee_id.')' }}
            </span>
          @else
            @foreach($req->resolved_roles ?? [] as $r)
              <span class="badge bg-warning me-1">{{ $r }}</span>
            @endforeach
          @endif
        </div>
      </div>
      <div class="row mb-2">
        <div class="col-md-3 text-muted">Target Employee</div>
        <div class="col">
          @if($employee)
            {{ $employee->fullname }} ({{ $employee->employee_id }}) — {{ $employee->designation_name }}
          @else
            {{ $req->employee_id }}
          @endif
        </div>
      </div>
      <div class="row mb-2">
        <div class="col-md-3 text-muted">Initiator</div>
        <div class="col">
          @if($initiator)
            {{ $initiator->fullname }} ({{ $initiator->employee_id }})
          @else
            {{ $req->created_by }}
          @endif
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-3 text-muted">Initiated on</div>
        <div class="col">{{ \Illuminate\Support\Carbon::parse($req->created_at)->format('d M Y H:i') }}</div>
      </div>

      @if(!empty($candidates))
        <div class="mb-3">
          <div class="text-muted mb-1">Approver</div>
          <ul class="mb-0">
            @foreach($candidates as $role => $users)
              @foreach($users as $u)
                <li>{{ $u }}</li>
              @endforeach
            @endforeach
          </ul>
        </div>
      @endif


      @if($formDetail)
        <hr>
        <h6 class="mb-2">Form Data</h6>
        <div class="row">
          <div class="col-md-3 text-muted">Scope</div>
          <div class="col text-capitalize">{{ $formDetail->scope }}</div>
        </div>
        <div class="row">
          <div class="col-md-3 text-muted">Appraisal Year</div>
          <div class="col">{{ $formDetail->appraisal_year }}</div>
        </div>
        @if(!empty($formDetail->notes))
        <div class="row">
          <div class="col-md-3 text-muted">Notes</div>
          <div class="col">{{ $formDetail->notes }}</div>
        </div>
        @endif
        <div class="row mt-2">
          <div class="col-md-3 text-muted">Peers</div>
          <div class="col">
            @if(!empty($formDetail->peers))
              <ul class="mb-0">
                @foreach($formDetail->peers as $pid)
                  @php $p = \App\Models\EmployeeAppraisal::select('employee_id','fullname')->where('employee_id',$pid)->first(); @endphp
                  <li>{{ $p?->fullname ?? $pid }} ({{ $pid }})</li>
                @endforeach
              </ul>
            @else
              -
            @endif
          </div>
        </div>
        <div class="row mt-2">
          <div class="col-md-3 text-muted">Subordinates</div>
          <div class="col">
            @if(!empty($formDetail->subordinates))
              <ul class="mb-0">
                @foreach($formDetail->subordinates as $sid)
                  @php $s = \App\Models\EmployeeAppraisal::select('employee_id','fullname')->where('employee_id',$sid)->first(); @endphp
                  <li>{{ $s?->fullname ?? $sid }} ({{ $sid }})</li>
                @endforeach
              </ul>
            @else
              -
            @endif
          </div>
        </div>
      @endif

      <hr>
      @if ($req->sendback_messages)
      <div class="card bg-warning bg-opacity-10 border border-warning mb-2">
        <div class="row p-2">
            <div class="col-lg col-sm-12 px-2">
                <div class="form-group">
                    <p class="fw-bold mb-0">Revision Notes :</p>
                    <p class="mt-1 mb-0">{{ $req->sendback_messages }}</p>
                </div>
            </div>
        </div>
      </div>
      @endif
      <form class="d-inline" method="POST" action="{{ route('admin-tasks.action', $req->id) }}">
        @csrf
        <input type="hidden" name="action" value="REJECT">
        <div class="mb-2">
          <textarea name="message" class="form-control" rows="2" placeholder="Sendback messages..." required></textarea>
        </div>
        <button class="btn btn-outline-warning">Sendback</button>
      </form>
      <form class="d-inline ms-2" method="POST" action="{{ route('admin-tasks.action', $req->id) }}">
        @csrf
        <input type="hidden" name="action" value="APPROVE">
        <button class="btn btn-primary">Approve</button>
      </form>
    </div>
  </div>
</div>
@endsection
