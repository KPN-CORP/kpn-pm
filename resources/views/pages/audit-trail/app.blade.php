@extends('layouts_.vertical', ['page_title' => 'Audit Trail'])

@section('css')
<style>
.table thead th { white-space: nowrap; }
pre.meta { white-space: pre-wrap; word-break: break-word; display:none; }
.badge-status { text-transform: lowercase; }
.sticky-top-filter { position: sticky; top: 64px; z-index: 9; }
</style>
@endsection

@section('content')
<div class="container-fluid">

  <div class="card mb-3">
    <div class="card-body fs-6 py-2">
      <form method="GET" action="{{ route('audit-trail') }}" class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label mb-1">Module</label>
          <select name="module" class="form-select form-control-sm">
            <option value="">All</option>
            @foreach($modules as $m)
              <option value="{{ $m }}" @selected(request('module')===$m)>{{ $m }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">Action</label>
          <select name="action" class="form-select form-control-sm">
            <option value="">All</option>
            @foreach($actions as $a)
              <option value="{{ $a }}" @selected(request('action')===$a)>{{ $a }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">From</label>
          <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">To</label>
          <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">Search</label>
          <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="actor, role, status, id, ...">
        </div>
        <div class="col-md-1 d-none">
          <label class="form-label mb-1">Per Page</label>
          <select name="per_page" class="form-select form-control-sm">
            @foreach([25,50,100,200] as $p)
              <option value="{{ $p }}" @selected((int)request('per_page',50)===$p)>{{ $p }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-1 d-flex gap-1">
          <button class="btn btn-primary btn-sm w-100">Filter</button>
          <a href="{{ route('audit-trail') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
        <div class="col-12 d-flex justify-content-between mt-2">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="autoRefresh" />
            <label class="form-check-label" for="autoRefresh">Auto refresh (10s)</label>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 160px;">Acted At</th>
              <th>Module</th>
              <th>Action</th>
              <th>Actor</th>
              <th>Status</th>
              <th>Layer</th>
              <th>Approver</th>
              <th>Form/Loggable</th>
              <th>Comments</th>
              <th>Meta</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($logs as $log)
              @php
                $actorName = $empMap[$log->actor_employee_id] ?? null;
                $actorLbl  = $actorName ? $actorName.' ('.$log->actor_employee_id.')' : $log->actor_employee_id;
                $metaShort = $log->meta_json ? $log->meta_json : null;
                $layer = $log->step_from ?? $log->step_to ?? null;
              @endphp
              <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($log->acted_at)->format('d M Y H:i:s') }}</td>
                <td>
                  <div class="fw-semibold">{{ $log->module }}</div>
                  @if($log->actor_role)
                    <div class="small text-muted">role: {{ $log->actor_role }}</div>
                  @endif
                </td>
                <td><span class="badge bg-light text-dark">{{ strtolower($log->action) }}</span></td>
                <td>{{ $actorLbl }}</td>
                <td>
                  <span class="badge bg-warning-subtle text-warning badge-status">{{ strtolower($log->status_from ?? '-') }}</span>
                  <span class="mx-1">→</span>
                  <span class="badge bg-success-subtle text-success badge-status">{{ strtolower($log->status_to ?? '-') }}</span>
                </td>
                <td>{{ $layer !== null ? $layer : '-' }}</td>
                <td>
                  <div class="small text-muted">{{ $log->approver_from ?? '-' }}</div>
                  <div class="small">{{ $log->approver_to ?? '-' }}</div>
                </td>
                <td>
                  @if($log->approval_request_id)
                    <div class="small text-muted">req: {{ $log->approval_request_id }}</div>
                  @endif
                  @if($log->loggable_id)
                    <div class="small">id: {{ $log->loggable_id }}</div>
                    <div class="small text-muted">{{ class_basename($log->loggable_type ?? '') }}</div>
                  @endif
                  @if($log->flow_id)
                    <div class="small text-muted">flow: {{ $log->flow_id }}</div>
                  @endif
                </td>
                <td class="text-wrap" style="max-width: 260px;">
                  {{ $log->comments }}
                </td>
                <td>
                  @if($log->meta_json)
                    <button class="btn btn-sm btn-outline-secondary" data-toggle-meta="#meta-{{ $log->id }}">View</button>
                  @endif
                </td>
              </tr>
              @if($log->meta_json)
              <tr id="meta-{{ $log->id }}" class="d-none">
                <td colspan="10">
                  <pre class="meta mb-0">{{ $log->meta_json }}</pre>
                </td>
              </tr>
              @endif
            @empty
              <tr><td colspan="10" class="text-center py-4 text-muted">Tidak ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer py-2">
      {{ $logs->links() }}
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-toggle-meta]').forEach(btn=>{
  btn.addEventListener('click', function(){
    const target = document.querySelector(this.getAttribute('data-toggle-meta'));
    if(!target) return;
    const pre = target.querySelector('pre.meta');
    target.classList.toggle('d-none');
    if (pre) pre.style.display = target.classList.contains('d-none') ? 'none' : 'block';
  });
});

const auto = document.getElementById('autoRefresh');
let timer = null;
if (auto) {
  auto.addEventListener('change', function(){
    if (this.checked) {
      timer = setInterval(()=>{ window.location.reload(); }, 10000);
    } else if (timer) {
      clearInterval(timer); timer = null;
    }
  });
}
</script>
@endpush
