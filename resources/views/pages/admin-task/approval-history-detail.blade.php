@extends('layouts_.vertical', ['page_title' => 'History Approval Detail'])

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">History Approval Details</h4>
    <a href="{{ route('approval-history') }}" class="btn btn-outline-secondary btn-sm">
      <i class="ri-arrow-left-line me-1"></i> Back
    </a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-3"><div class="text-muted small">Category</div><div class="fw-semibold">{{ $req->category }}</div></div>
        <div class="col-md-3"><div class="text-muted small">Period</div><div class="fw-semibold">{{ $req->period }}</div></div>
        <div class="col-md-3"><div class="text-muted small">Status</div><span class="badge bg-success px-2 py-1">Approved</span></div>
        <div class="col-md-3"><div class="text-muted small">Step</div><div class="fw-semibold">{{ $req->current_step }} / {{ $req->total_steps }}</div></div>
      </div>

      {{-- Approved info --}}
      <div class="row g-2 mt-2">
        <div class="col-md-6">
          <div class="text-muted small">Approved By</div>
          <div class="fw-semibold d-flex align-items-center flex-wrap gap-2">
            @if($approvedByEmpId)
              <span>{{ $approvedByName ?? '-' }} ({{ $approvedByEmpId }})</span>
              @if(!empty($approvedIsOverride) && $approvedIsOverride && !empty($approvedByRole))
                <span class="badge bg-warning">{{ $approvedByRole }}</span>
              @endif
            @else
              <span>-</span>
            @endif
          </div>
        </div>
        <div class="col-md-6">
          <div class="text-muted small">Approved On</div>
          <div class="fw-semibold">
            {{ $approvedOn ?? '-' }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-4"><div class="text-muted small">Employee</div><div class="fw-semibold">{{ $employee?->fullname }} ({{ $employee?->employee_id }})</div></div>
        <div class="col-md-4"><div class="text-muted small">Designation</div><div class="fw-semibold">{{ $employee?->designation_name }}</div></div>
        <div class="col-md-4"><div class="text-muted small">Initiator</div><div class="fw-semibold">{{ $initiator?->fullname ?? '-' }}</div></div>
      </div>
    </div>
  </div>

  @if($req->category === 'Proposed360' && $formDetail)
    <div class="card mb-3">
      <div class="card-header fw-semibold">Proposed 360</div>
      <div class="card-body">
        <div class="mb-2"><span class="text-muted small">Scope:</span> <span class="fw-semibold">{{ $formDetail->scope }}</span></div>
        <div class="row">
          <div class="col-md-4">
            <div class="text-muted small mb-1">Peers</div>
            @if(!empty($peersList))
              <ul class="mb-0 small ps-3">
                @foreach($peersList as $line) <li>{{ $line }}</li> @endforeach
              </ul>
            @else <div class="small text-muted">-</div> @endif
          </div>
          <div class="col-md-4">
            <div class="text-muted small mb-1">Subordinates</div>
            @if(!empty($subsList))
              <ul class="mb-0 small ps-3">
                @foreach($subsList as $line) <li>{{ $line }}</li> @endforeach
              </ul>
            @else <div class="small text-muted">-</div> @endif
          </div>
          <div class="col-md-4 d-none">
            <div class="text-muted small mb-1">Managers</div>
            @if(!empty($mgrsList))
              <ul class="mb-0 small ps-3">
                @foreach($mgrsList as $line) <li>{{ $line }}</li> @endforeach
              </ul>
            @else <div class="small text-muted">-</div> @endif
          </div>
        </div>
        @if(!empty($formDetail->notes))
          <div class="mt-2"><span class="text-muted small">Notes:</span> <span>{{ $formDetail->notes }}</span></div>
        @endif
      </div>
    </div>
  @endif

  <div class="alert alert-light border small">
    Tampilan ini <strong>read-only</strong>. Tidak ada aksi approve/reject.
  </div>
</div>
@endsection
