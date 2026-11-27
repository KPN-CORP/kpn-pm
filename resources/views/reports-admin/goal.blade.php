<div class="row">
    <div class="col-md-12">
      <div class="card shadow mb-4">
        <div class="card-header">
            <div class="row">
              <div class="col-md-auto text-center">
                  <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="all">{{ __('All Task') }}</button>
                  <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="draft">Draft</button>
                  <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="waiting for revision">{{ __('Waiting For Revision') }}</button>
                  <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="waiting for approval">{{ __('Pending') }}</button>
                  <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="approved">{{ __('Approved') }}</button>
              </div>
            </div>
          </div>
        <div class="card-body">
            <table class="table table-sm table-hover nowrap align-middle w-100" id="adminReportTable" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Employees</th>
                        <th>KPI</th>
                        <th>Goal Status</th>
                        <th>Period</th>
                        <th>Approval Status</th>
                        <th>Initiated On</th>
                        <th>{{ __('Initiated By') }}</th>
                        <th>{{ __('Last Updated On') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $row)
                    <tr>
                        <td><p class="m-0">{{ $row->employee->fullname }} <span class="text-muted">{{ $row->employee_id }}</span></p></td>
                        <td class="text-center">
                            <a href="javascript:void(0)" class="btn btn-light btn-sm font-weight-medium btn-goal-detail" data-detail-url="{{ route('admin.reports.goal-detail', $row->goal->id) }}"><i class="ri-search-line"></i></a>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $row->goal->form_status == 'Approved' ? 'bg-success' : ($row->goal->form_status == 'Draft' ? 'badge-outline-secondary' : 'bg-secondary')}} px-1">{{ $row->goal->form_status == 'Draft' ? 'Draft' : $row->goal->form_status }}</span>
                        </td>
                        <td>{{ $row->goal->period }}</td>
                        <td class="text-center">
                        <a href="javascript:void(0)" data-bs-id="{{ $row->employee_id }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $row->goal->form_status=='Draft' ? 'Draft' : ($row->approvalLayer ? 'Manager L'.$row->approvalLayer.' : '.$row->name : $row->name) }}" class="badge {{ $row->status == 'Approved' ? 'bg-success' : ( $row->status=='Sendback' || $row->goal->form_status=='Draft' ? 'bg-secondary' : 'bg-warning' ) }} px-1">{{ $row->status == 'Pending' ? ($row->goal->form_status=='Draft' ? 'Not Started' : __('Pending')) : ( $row->status=='Sendback'? 'Waiting For Revision' : $row->status) }}</a>
                        </td>
                        <td class="text-center">{{ $row->formatted_created_at }}</td>
                        <td>{{ $row->initiated->name }}<br>{{ $row->initiated->employee_id }}</td>
                        <td class="text-center">{{ $row->formatted_updated_at }}</td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
      </div>
    </div>
</div>

{{-- Modal detail Goal — SATU shell, isinya dimuat lewat AJAX.

     Dulu di sini ada satu modal lengkap per baris, di dalam <tr> (yang juga
     HTML tidak valid). Pada report Goal 2026 itu 1.422 modal = 38 MB HTML.
     Sekarang isinya diambil saat modal dibuka. --}}
<div class="modal fade" id="modalGoalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl mt-2" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Goals</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-primary-subtle" id="goalDetailBody">
                <div class="p-5 text-center text-muted">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>{{ __('Loading') }}...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Blok ini di-inject lewat AJAX dan dieksekusi ulang setiap kali
    // konten dimuat ulang. Tanpa penjaga ini, handler click menumpuk
    // dan satu klik akan memicu beberapa request sekaligus.
    if (!window.kpnAdminGoalDetailBound) {
        window.kpnAdminGoalDetailBound = true;

        // Isi modal dimuat saat dibuka, bukan di-render untuk semua baris.
        document.addEventListener('click', function (e) {
            const button = e.target.closest('.btn-goal-detail');
            if (!button) {
                return;
            }

            e.preventDefault();

            const body = document.getElementById('goalDetailBody');
            body.innerHTML = '<div class="p-5 text-center text-muted"><div class="spinner-border spinner-border-sm me-2" role="status"></div>{{ __('Loading') }}...</div>';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalGoalDetail')).show();

            fetch(button.dataset.detailUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error(response.status);
                    }
                    return response.text();
                })
                .then(function (html) {
                    body.innerHTML = html;
                })
                .catch(function () {
                    body.innerHTML = '<div class="p-5 text-center text-muted">{{ __('Failed to load goal details.') }}</div>';
                });
        });
    }
</script>
