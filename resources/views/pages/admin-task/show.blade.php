@extends('layouts_.vertical', ['page_title' => 'Approval Detail'])

@section('content')
<div class="container-fluid">
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  <div class="card">
    <div class="card-body">
      <h5 class="mb-3">Detail Approval</h5>

      <div class="row mb-2">
        <div class="col-md-3 text-muted">Kategori</div>
        <div class="col">{{ $req->category }}</div>
      </div>
      <div class="row mb-2">
        <div class="col-md-3 text-muted">Periode</div>
        <div class="col">{{ $req->period }}</div>
      </div>
      <div class="row mb-2">
        <div class="col-md-3 text-muted">Current Role</div>
        <div class="col"><span class="badge bg-warning">{{ $req->current_approval_id }}</span></div>
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
        <div class="col-md-3 text-muted">Pengaju</div>
        <div class="col">
          @if($initiator)
            {{ $initiator->fullname }} ({{ $initiator->employee_id }})
          @else
            {{ $req->created_by }}
          @endif
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-3 text-muted">Diajukan</div>
        <div class="col">{{ \Illuminate\Support\Carbon::parse($req->created_at)->format('d M Y H:i') }}</div>
      </div>

      @if(!empty($candidates))
        <div class="mb-3">
          <div class="text-muted mb-1">Approver</div>
          <ul class="mb-0">
            @foreach($candidates as $c)
              <li>{{ $c }}</li>
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
