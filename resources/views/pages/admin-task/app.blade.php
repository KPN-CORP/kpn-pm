@extends('layouts_.vertical', ['page_title' => 'Admin Tasks'])

@section('content')
<div class="container-fluid">
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  <h4 class="mb-3">Approval Tasks (Role)</h4>

  @forelse ($tasks as $t)
    @php
      $emp = $empMap[$t->employee_id] ?? null;
      $label = $emp ? ($emp->fullname.' ('.$t->employee_id.')') : $t->employee_id;
      $role  = (string) $t->current_approval_id;
      $cands = $roleCandidates[$role] ?? [];
      $waitingList = !empty($cands) ? implode(', ', $cands) : $role;
    @endphp

    <a href="{{ route('admin-tasks.detail', $t->id) }}" class="text-decoration-none text-reset">
      <div class="card mb-2 shadow-sm">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold">{{ $t->category }} — Periode {{ $t->period }}</div>
              <div class="small text-muted">Employee: {{ $label }}</div>
              <div class="small text-muted">Step {{ $t->current_step }} / {{ $t->total_steps }}</div>
              <div class="small">Waiting for (Role): <span class="fw-semibold">{{ $role }}</span></div>
              @if(!empty($cands))
                <div class="small text-muted">Approver: {{ $waitingList }}</div>
              @endif
            </div>
            <div class="text-end">
              <span class="badge bg-warning text-dark">pending</span>
              <div class="small text-muted">{{ \Illuminate\Support\Carbon::parse($t->created_at)->format('d M Y H:i') }}</div>
            </div>
          </div>
        </div>
      </div>
    </a>
  @empty
    <div class="alert alert-info">Tidak ada task approval untuk role Anda.</div>
  @endforelse

  <div class="mt-3">
    {{ $tasks->links() }}
  </div>
</div>
@endsection
