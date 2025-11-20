@extends('layouts_.vertical', ['page_title' => 'Admin Tasks'])

@section('content')
<div class="container-fluid">
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  <h4 class="mb-3 d-flex align-items-center justify-content-between">
  <span>Approval Tasks (Admin)</span>
    <a href="{{ route('approval-history') }}" class="btn btn-outline-primary btn-sm">
      <i class="ri-history-line me-1"></i> History Approval
    </a>
  </h4>

  @php $q = request('q'); @endphp

  {{-- SEARCH (auto submit) --}}
  <form id="searchForm" method="GET" class="mb-3">
    <div class="input-group">
      <input type="text" id="q" name="q" value="{{ $q }}" class="form-control"
             placeholder="Cari: Employee ID / Name / Category / Role">
    </div>
  </form>

  {{-- INFO BAR --}}
  @if($tasks->total() > 0)
    <div class="small text-muted mb-2">
      Showing <strong>{{ $tasks->firstItem() }}</strong>–<strong>{{ $tasks->lastItem() }}</strong>
      from <strong>{{ $tasks->total() }}</strong> data
      @if($q) (keyword: “{{ $q }}”) @endif
    </div>
  @endif

  {{-- LIST --}}
  @forelse ($tasks as $t)
    @php
      $emp = $empMap[$t->employee_id] ?? null;
      $label = $emp ? ($emp->fullname.' ('.$t->employee_id.')') : $t->employee_id;
      // CASE 1: approval_by_employee (employee_id)
      if (ctype_digit($t->current_approval_id)) {
          $roleLabel = "Specific User";
          $waitingList = $t->current_approval_id;

      } else {
          // CASE 2: approval_by_flow (flow_name)
          // resolved_roles = array role dari ApprovalFlowSteps
          $roles = $t->resolved_roles ?? [];

          // Jika ada banyak role → join
          $roleLabel = !empty($roles) ? implode(', ', $roles) : $t->current_approval_id;

          // Kandidat approver
          $candidateMap = $t->approval_candidates ?? [];

          $waitingList = [];
          foreach ($candidateMap as $r => $cands) {
              foreach ($cands as $c) {
                  $waitingList[] = $c; // string: "Nama (id)"
              }
          }

          $waitingList = !empty($waitingList) ? implode(', ', $waitingList) : $roleLabel;
      }
    @endphp

    <a href="{{ route('admin-tasks.detail', $t->id) }}" class="text-decoration-none text-reset">
      <div class="card mb-2 shadow-sm">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold">{{ $t->category }} — Period {{ $t->period }}</div>
              <div class="small text-muted">Employee: {{ $label }}</div>
              <div class="small text-muted">Step {{ $t->current_step }} / {{ $t->total_steps }}</div>
              <div class="small">Waiting for (Role): 
                  <span class="fw-semibold">{{ $roleLabel }}</span>
              </div>

              @if(!empty($waitingList))
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
    <div class="alert alert-info">No task.</div>
  @endforelse

  {{-- PAGINATION --}}
  <div class="mt-3">
    {{ $tasks->withQueryString()->links() }}
  </div>
</div>

{{-- Auto search (debounce) --}}
@push('scripts')
<script>
(function(){
  const form = document.getElementById('searchForm');
  const input = document.getElementById('q');
  let timer = null;

  const submitNow = () => form.requestSubmit();

  input.addEventListener('input', function(){
    if (timer) clearTimeout(timer);
    timer = setTimeout(submitNow, 350); // debounce 350ms
  });

  // Enter langsung submit, clear (Esc) untuk kosongkan & submit
  input.addEventListener('keydown', function(e){
    if (e.key === 'Escape') { input.value=''; submitNow(); }
  });
})();
</script>
@endpush
@endsection
