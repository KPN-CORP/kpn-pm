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
                        <th>Details</th>
                        <th>Status</th>
                        <th>Period</th>
                        <th>Initiated On</th>
                        <th>{{ __('Last Updated On') }}</th>
                        <th class="sorting_1">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $row)
                    <tr>
                        <td>
                            <p class="m-0">{{ optional($row->employee)->fullname ?? '-' }} <span class="text-muted">{{ $row->employee_id ?? '-' }}</span></p>
                        </td>
                        <td class="text-center">
                            <a href="javascript:void(0)" class="btn btn-light btn-sm font-weight-medium btn-achievement-detail" data-detail-url="{{ route('admin.onbehalf.achievement-detail', $row->id) }}"><i class="ri-search-line"></i></a>
                        </td>
                        <td class="text-center">
                            @php
                                $achievement = $row->achievement;
                            @endphp

                            <a href="javascript:void(0)"
                            data-bs-content="{{
                                    optional($achievement)->approval_status == 'Pending'
                                    ? optional($achievement)->approval_status
                                    : (optional($achievement)->approvalLayer
                                        ? 'Manager L'.optional($achievement)->approvalLayer.' : '.optional($achievement)->name
                                        : optional($achievement)->name)
                                }}"
                            class="badge {{
                                    optional($achievement)->approval_status == 'Approved'
                                    ? 'bg-success'
                                    : 'bg-secondary'
                            }}">
                            {{ optional($achievement)->approval_status == 'Pending' ? 'Waiting for Approval' : (optional($achievement)->approval_status == 'Draft' && optional($achievement)->approval_info ? 'Waiting for Revision' : (optional($achievement)->approval_status ?? '-'))  }}
                            </a>
                        </td>
                        <td>
                            {{ $row->period }}
                        </td>
                        <td class="text-center">
                            {{ optional($row->achievement)->formatted_created_at ?? '-' }}
                        </td>

                        <td class="text-center">
                            {{ optional($row->achievement)->formatted_updated_at ?? '-' }}
                        </td>
                        <td class="text-center sorting_1 px-1">
                          @can('approvalonbehalf')
                          <div class="btn-group dropstart">
                            <button class="btn btn-sm {{ $achievement->approval_status != 'Draft' ? 'btn-primary' : 'btn-light disabled' }} px-1 rounded" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="animated-preview" data-bs-offset="0,10">
                              Action
                            </button>
                            <div class="dropdown-menu dropdown-menu-animated">
                              @if ( $achievement->approval_status != 'Approved' )
                                <a class="dropdown-item" href="{{ route('goals.approval-achievement', $achievement->goal_id) }}">Approve</a>
                              @else
                              <a class="dropdown-item disabled" href="javascript:void(0)">{{ __('No Action') }}</a>
                              @endif
                            </div>
                          </div>
                          @else
                          {{ "-" }}
                          @endcan
                        </td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
      </div>
    </div>
</div>

{{-- Modal detail Achievement — SATU shell, isinya dimuat lewat AJAX. --}}
<div class="modal fade" id="modalAchievementDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" id="achievementDetailContent">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold">{{ __('Achievement') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
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
    if (!window.kpnObAchievementDetailBound) {
        window.kpnObAchievementDetailBound = true;

        // Isi modal dimuat saat dibuka, bukan di-render untuk semua baris.
        document.addEventListener('click', function (e) {
            const button = e.target.closest('.btn-achievement-detail');
            if (!button) {
                return;
            }

            e.preventDefault();

            const content = document.getElementById('achievementDetailContent');
            const placeholder = '<div class="modal-header bg-light border-bottom">'
                + '<h5 class="modal-title fw-bold">{{ __('Achievement') }}</h5>'
                + '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
                + '<div class="modal-body"><div class="p-5 text-center text-muted">'
                + '<div class="spinner-border spinner-border-sm me-2" role="status"></div>{{ __('Loading') }}...</div></div>';

            content.innerHTML = placeholder;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAchievementDetail')).show();

            fetch(button.dataset.detailUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error(response.status);
                    }
                    return response.text();
                })
                .then(function (html) {
                    content.innerHTML = html;
                })
                .catch(function () {
                    content.innerHTML = placeholder.replace(/<div class="p-5[\s\S]*?<\/div>\s*<\/div>/,
                        '<div class="p-5 text-center text-muted">{{ __('Failed to load achievement details.') }}</div></div>');
                });
        });
    }
</script>
