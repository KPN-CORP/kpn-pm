@extends('layouts_.vertical', ['page_title' => 'History Approval'])

@section('content')
<div class="container-fluid">
  @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

  <h4 class="mb-3 d-flex align-items-center justify-content-between">
    <span>History Approvals</span>
    <a href="{{ route('admin-tasks') }}" class="btn btn-outline-secondary btn-sm">
      <i class="ri-arrow-go-back-line me-1"></i> Back to Tasks
    </a>
  </h4>

  @php $q = request('q'); @endphp

  {{-- SEARCH (auto submit, no partial) --}}
  <form id="searchForm" method="GET" class="mb-3" onsubmit="return true;">
    <div class="input-group">
      <input type="text" id="q" name="q" value="{{ $q }}" class="form-control"
             placeholder="Cari: Employee ID / Name / Category / Period">
      @if($q)
        <a href="{{ route('approval-history') }}" class="btn btn-outline-secondary">Reset</a>
      @endif
    </div>
  </form>

  @if($histories->total() > 0)
    <div class="small text-muted mb-2">
      Showing <strong>{{ $histories->firstItem() }}</strong>–<strong>{{ $histories->lastItem() }}</strong>
      from <strong>{{ $histories->total() }}</strong> data
      @if($q) (keyword: “{{ $q }}”) @endif
    </div>
  @endif

  {{-- LIST (card style, sama seperti Approval Tasks, tapi badge Approved & link detail history) --}}
  @forelse ($histories as $h)
    @php
      $emp = $empMap[$h->employee_id] ?? null;
      $label = $emp ? ($emp->fullname.' ('.$h->employee_id.')') : $h->employee_id;
    @endphp

    <a href="{{ route('approval-history.detail', $h->id) }}" class="text-decoration-none text-reset">
      <div class="card mb-2 shadow-sm">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold">{{ $h->category }} — Period {{ $h->period }}</div>
              <div class="small text-muted">Employee: {{ $label }}</div>
              <div class="small text-muted">Step {{ $h->current_step }} / {{ $h->total_steps }}</div>
            </div>
            <div class="text-end">
              <span class="badge bg-success">approved</span>
              <div class="small text-muted">{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d M Y H:i') }}</div>
            </div>
          </div>
        </div>
      </div>
    </a>
  @empty
    <div class="alert alert-info">There is no history approval.</div>
  @endforelse

  {{-- PAGINATION --}}
  <div class="mt-3">
    {{ $histories->withQueryString()->links() }}
  </div>
</div>

@push('scripts')
<script>
(function(){
  const form = document.getElementById('searchForm');
  const input = document.getElementById('q');
  let t = null;
  input.addEventListener('input', function(){
    if (t) clearTimeout(t);
    t = setTimeout(() => form.requestSubmit(), 350);
  });
  input.addEventListener('keydown', function(e){
    if (e.key === 'Escape') { input.value=''; form.requestSubmit(); }
  });
})();
</script>
@endpush
@endsection
