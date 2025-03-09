<div class="modal fade" id="modalDetail{{ $goalId }}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl mt-2" role="document">
        <div class="modal-content">
            <div class="modal-header">
                  <h4 class="modal-title h4" id="viewFormEmployeeLabel">Goal's</h4>
                  <button type="button" class="btn-close mr-3" data-bs-dismiss="modal" aria-label="Close"></button>
              <div class="input-group-md">
                  <input type="text" id="employee_name" class="form-control" placeholder="Search employee.." hidden>
              </div>
        </div>
        <div class="modal-body bg-secondary-subtle">
          <div class="container-fluid py-3">
              <form action="" method="post">
                  <div class="d-sm-flex align-items-center mb-3">
                        <h4 class="me-1">{{ $task->employee->fullname }}</h4><span class="text-muted h4">{{ $task->employee->employee_id }}</span>
                  </div>
                  <!-- Content Row -->
                  <div class="container-card">
                    @php
                        $formData = json_decode($goalData, true);
                    @endphp
                    @if ($formData)
                    @foreach ($formData as $index => $data)
                        <div class="card col-md-12 mb-4 shadow-sm">
                            <div class="card-header bg-white pb-0">
                                <h4>KPI {{ $index + 1 }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-5 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="kpi">KPI</label>
                                            <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['kpi'] }}</p>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="target">Target in {{ $data['uom'] }}</label>
                                            <input type="text" value="{{ $data['target'] }}" class="form-control bg-gray-100" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="weightage">{{ __('Weightage') }}</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-gray-100" value="{{ $data['weightage'] }}" readonly>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                            <!-- Tambahkan kode untuk menampilkan error weightage jika ada -->
                                            @if ($errors->has("weightage"))
                                                <span class="text-danger">{{ $errors->first("weightage") }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-2 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="type">{{ __('Type') }}</label>
                                            <input type="text" value="{{ $data['type'] }}" class="form-control bg-gray-100" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @else
                        <p>No form data available.</p>
                    @endif                
        </div>
              </form>
          </div>
        </div>
      </div>
    </div>
  </div>