<div class="row">
    <div class="col-md-12">
      <div class="card shadow mb-4">
        <div class="card-header">
          <div class="row rounded">
            <div class="col-md-auto text-center">
                <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="all">{{ __('All Task') }}</button>
                <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="draft">Draft</button>
                <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="{{ __('Pending') }}">{{ __('Pending') }}</button>
                <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="{{ __('Approved') }}">{{ __('Approved') }}</button>
            </div>
          </div>
        </div>
        <div class="card-body">
            <table class="table table-sm table-hover align-middle activate-select dt-responsive nowrap w-100 fs-14" id="onBehalfTable">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th>Employees</th>
                        <th>Appraisal</th>
                        <th>Approval Status</th>
                        <th>Initiated On</th>
                        <th>{{ __('Initiated By') }}</th>
                        <th>{{ __('Last Updated On') }}</th>
                        <th>Updated By</th>
                        <th class="sorting_1">Action</th>
                    </tr>
                </thead>
                <tbody>
                  @foreach ($data as $row)
                    @php $rowKey = $row->request->employee_id; @endphp
                    <tr id="row-{{ $rowKey }}">
                      <td>{{ $row->request->employee->fullname .' ('.$row->request->employee->employee_id.')'}}</td>
                      <td class="text-center">
                        <a href="javascript:void(0)" class="btn btn-outline-secondary rounded btn-sm {{ $row->request->appraisal->form_status === 'Draft' ? 'disabled' : '' }}" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $row->request->appraisal->id }}"><i class="ri-file-text-line"></i></a>
                      </td>
                      <td class="text-center">
                        <a href="javascript:void(0)" data-bs-id="{{ $row->request->employee_id }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $row->request->approvalLayer ? 'Manager L'.$row->request->approvalLayer.' : '.$row->request->name : $row->request->name }}" class="badge py-1 px-2 rounded-pill {{ $row->request->appraisal->form_status == 'Draft' || $row->request->status == 'Sendback' ? 'bg-secondary' : ($row->request->status === 'Approved' ? 'bg-success' : 'bg-warning')}} ">
                          {{ $row->request->appraisal->form_status == 'Draft' ? 'Draft': ($row->request->status == 'Pending' ? __('Pending') : ($row->request->status == 'Sendback' ? 'Waiting For Revision' : $row->request->status)) }}
                        </a>
                      </td>
                      <td class="text-center">{{ $row->request->formatted_created_at }}</td>
                      <td>{{ $row->request->initiated->name ? $row->request->initiated->name .' ('. $row->request->initiated->employee_id .')'  : '-' }}</td>
                      <td class="text-center">{{ $row->request->formatted_updated_at }}</td>
                      <td>{{ $row->request->updatedBy ? $row->request->updatedBy->name.' ('.$row->request->updatedBy->employee_id.')' : '-' }}</td>

                      <td class="text-center sorting_1 px-1">
                        @can('approvalonbehalf')
                        <div class="btn-group dropstart">
                          @php
                            $hasCalibration = $row->request->calibration && $row->request->calibration->isNotEmpty();
                            $canAct = $row->request->status != 'Sendback' && $row->request->appraisal->form_status != 'Draft';
                            $selfData = auth()->user()->id === $row->request->employee->id;
                          @endphp

                          @if (!$selfData)
                            @if ($canAct && $hasCalibration)
                              <button class="btn btn-sm btn-light px-1 rounded" type="button"
                                onclick="Swal.fire({ icon: 'info', title: 'Calibration Ongoing', text: 'Employee already in Calibration process.', confirmButtonText: 'OK', confirmButtonColor: '#3e60d5' })">
                                Action
                              </button>
                            @else
                              <button class="btn btn-sm btn-primary px-1 rounded" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="animated-preview" data-bs-offset="0,10">
                                Action
                              </button>
                              <div class="dropdown-menu dropdown-menu-animated">
                                @if ($row->request->status === 'Pending')
                                  <a class="dropdown-item" href="{{ route('admin.create.approval.appraisal', [encrypt($row->request->employee_id), 'onbehalf']) }}">Approve</a>

                                  {{-- REVOKE AJAX --}}
                                  <button type="button"
                                          class="dropdown-item js-revoke"
                                          data-url="{{ route('admin.revoke.approval.appraisal', [encrypt($row->request->employee_id), 'onbehalf']) }}"
                                          data-row="#row-{{ $rowKey }}"
                                          data-emp="{{ $rowKey }}">
                                    Revoke
                                  </button>
                                @else
                                  <a class="dropdown-item" href="{{ route('admin.create.approval.appraisal', [encrypt($row->request->employee_id), 'onbehalf']) }}">Revise</a>
                                @endif
                              </div>
                            @endif
                          @else
                            <button class="btn btn-sm btn-light px-1 rounded disabled" type="button">Action</button>
                          @endif
                        </div>
                        @else
                          {{ "-" }}
                        @endcan
                      </td>
                    </tr>
                  @endforeach
                  </tbody>

                </table>
                @foreach ($data as $row)
                  @include('pages.onbehalfs.appraisal_detail', ['row' => $row])
                @endforeach
              </div>
            </div>
    </div>
</div>