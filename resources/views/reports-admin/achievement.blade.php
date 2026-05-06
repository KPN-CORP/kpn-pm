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
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $row)
                    <tr>
                        <td>
                            <p class="m-0">{{ optional($row->employee)->fullname ?? '-' }} <span class="text-muted">{{ $row->employee_id ?? '-' }}</span></p>
                        </td>
                        <td class="text-center">
                            <a href="javascript:void(0)" class="btn btn-light btn-sm font-weight-medium" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $row->id }}"><i class="ri-search-line"></i></a>
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
                            {{ optional($achievement)->approval_status ?? '-' }}
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

                        @php
                            $formDataArr = $row->formData ?? [];
                            $months = [
                                            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                                            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                                            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                                        ];
                        @endphp
                        <div class="modal fade" id="modalDetail{{ $row->id }}" tabindex="-1">
                            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                <div class="modal-content border-0 shadow-lg">

                                    <!-- HEADER -->
                                    <div class="modal-header bg-light border-bottom">
                                        <h5 class="modal-title fw-bold">
                                            <i class="ri-file-list-3-line me-1 text-primary"></i>
                                            Goal Details - {{ $row->employee->fullname }}
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <!-- BODY -->
                                    <div class="modal-body p-0 bg-white">

                                        @php
                                            $formDataArr = $row->formData ?? [];
                                        @endphp

                                        @if(!empty($formDataArr))

                                            @foreach ($formDataArr as $kpiIndex => $kpi)

                                            <div class="p-4 {{ $loop->even ? 'bg-light-subtle' : 'bg-white' }} border-bottom">

                                                <div class="row g-3">

                                                    <!-- LEFT -->
                                                    <div class="col-md-5">
                                                        <small class="fw-bold text-uppercase mb-1 d-block text-danger">
                                                            KPI {{ $kpiIndex + 1 }}
                                                        </small>

                                                        <h6 class="fw-bold mb-1">
                                                            {{ $kpi['kpi'] ?? '-' }}
                                                        </h6>

                                                        <p class="text-muted small mt-2" style="white-space: pre-line;">
                                                            {{ $kpi['description'] ?? '-' }}
                                                        </p>
                                                    </div>

                                                    <!-- RIGHT -->
                                                    <div class="col-md-7">
                                                        <div class="row g-3 mb-3">

                                                            <div class="col-3">
                                                                <small class="fw-bold text-uppercase">Target</small>
                                                                <div>{{ data_get($kpi, 'target', '-') }}</div>
                                                            </div>

                                                            <div class="col-3">
                                                                <small class="fw-bold text-uppercase">UoM</small>
                                                                <div>
                                                                    {{ ($kpi['uom'] ?? '') !== 'Other'
                                                                        ? $kpi['uom']
                                                                        : ($kpi['custom_uom'] ?? '-') }}
                                                                </div>
                                                            </div>

                                                            <div class="col-3">
                                                                <small class="fw-bold text-uppercase">Weight</small>
                                                                <div>{{ $kpi['weightage'] ?? 0 }}%</div>
                                                            </div>

                                                            <div class="col-3">
                                                                <small class="fw-bold text-uppercase">Achievement</small>
                                                                <div>{{ $kpi['achievement'] ?? 0 }}%</div>

                                                                @php $percent = (int) ($kpi['achievement'] ?? 0); @endphp

                                                                <div class="mini-progress mt-1" style="height:5px;">
                                                                    <div class="mini-progress-bar bg-success" style="width: {{ $percent }}%"></div>
                                                                </div>
                                                            </div>

                                                            <div class="col-3">
                                                                <small class="fw-bold text-uppercase">Type</small>
                                                                <div>{{ $kpi['type'] ?? '-' }}</div>
                                                            </div>

                                                            <div class="col-3">
                                                                <small class="fw-bold text-uppercase d-block kpi-label mb-1">Review Period</small>
                                                                <span>{{ data_get($kpi, 'review_period_label', '-') }}</span>
                                                            </div>

                                                            <div class="col-3">
                                                                <small class="fw-bold text-uppercase">Calc</small>
                                                                <div>{{ data_get($kpi, 'calculation_method_label', '-') }}</div>
                                                            </div>

                                                        </div>
                                                    </div>

                                                </div>

                                                <!-- 🔥 ACHIEVEMENT TRACKING -->
                                                <div class="mt-4">
                                                    <h6 class="fw-bold text-uppercase mb-2">Achievement Tracking</h6>

                                                    <div class="row g-2">

                                                        @foreach($months as $monthNum => $monthLabel)

                                                        @php
                                                            $ach = $kpi['ach'] ?? [];
                                                            $attachments = $kpi['attachment'] ?? [];

                                                            $value = $ach[$monthNum] ?? null;

                                                            $formatted = is_null($value) || $value === ''
                                                                ? '-'
                                                                : rtrim(rtrim($value, '0'), '.');

                                                            $file = $attachments[$monthNum] ?? null;
                                                        @endphp

                                                        <div class="col-4 col-sm-3 col-md-2 col-lg-1">
                                                            <div class="border rounded p-2 text-center {{ $value ? 'bg-primary-subtle border-primary' : '' }}">

                                                                <small class="d-block text-muted">
                                                                    {{ $monthLabel }}
                                                                </small>

                                                                <div class="fw-bold">
                                                                    {{ $formatted }}
                                                                </div>

                                                                @if($file)
                                                                    <a href="{{ asset('storage/'.$file) }}"
                                                                    target="_blank"
                                                                    class="small text-info d-block mt-1">
                                                                        VIEW
                                                                    </a>
                                                                @endif

                                                            </div>
                                                        </div>

                                                        @endforeach

                                                    </div>
                                                </div>

                                            </div>

                                            @endforeach

                                        @else
                                            <div class="p-5 text-center text-muted">
                                                No KPI Data
                                            </div>
                                        @endif

                                    </div>

                                    <!-- FOOTER -->
                                    <div class="modal-footer bg-light">
                                        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
      </div>
    </div>
</div>