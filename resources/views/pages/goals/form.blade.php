@extends('layouts_.vertical', ['page_title' => 'Goals'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

    @if ($errors->any())
    <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                {{ $error }}
            @endforeach
    </div>
    @endif

    <!-- Page Heading -->
    <div class="detail-employee">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md">
                                <div class="row">
                                    <div class="col">
                                        <p class="mb-2"><span class="text-muted">Employee Name:</span> {{ $datas->first()->employee->fullname }}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <p class="mb-2"><span class="text-muted">Employee ID:</span> {{ $datas->first()->employee->employee_id }}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <p class="mb-2"><span class="text-muted">Job Level:</span> {{ $datas->first()->employee->job_level }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="row">
                                    <div class="col">
                                        <p class="mb-2"><span class="text-muted">Business Unit:</span> {{ $datas->first()->employee->group_company }}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <p class="mb-2"><span class="text-muted">Division:</span> {{ $datas->first()->employee->unit }}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <p class="mb-2"><span class="text-muted">Designation:</span> {{ $datas->first()->employee->designation_name }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mandatory-field"></div>
        <!-- Page Heading -->
        <form id="goalForm" action="{{ route('goals.submit') }}" method="POST">
            @csrf
          @foreach ($datas as $index => $data)
          <input type="hidden" class="form-control" name="users_id" value="{{ Auth::user()->id }}">
          <input type="hidden" class="form-control" name="approver_id" value="{{ $data->approver_id }}">
          <input type="hidden" class="form-control" name="employee_id" value="{{ $data->employee_id }}">
          <input type="hidden" class="form-control" name="category" value="Goals">
          @endforeach
          <!-- Content Row -->
          <div class="row">
            <div class="col-md">
                <h4>{{ __('Target') }} {{ $period }}</h4>
            </div>
          </div>
          <div class="container-fluid p-0">
            <div class="card col-md-12 mb-3 shadow">
                <div class="card-body pb-0 px-2 px-md-3">
                    <div class="container-card">
                      <div class="card border-primary border col-md-12 mb-3 bg-primary-subtle">
                          <div class="card-body">
                              <h5 class="card-title fs-16 text-primary">Goal {{ $index + 1 }}</h5>
                              <div class="row">
                                <div class="col-md">
                                    <div class="mb-3">
                                        <textarea name="kpi[]" id="kpi" class="form-control" placeholder="input your goals.." required @style('height: 100px')>{{ old('kpi.0') }}</textarea>
                                    </div>
                                </div>
                              </div>
                              <div class="row justify-content-between">
                                  <div class="col-md">
                                      <div class="mb-3">
                                          <label class="form-label text-primary" for="target">Target</label>
                                          <input  type="text" oninput="validateDigits(this, {{ $index }})" value="{{ number_format(old('target.0'), 0, '', ',') }}" class="form-control" required>
                                          <input type="hidden" name="target[]" id="target{{ $index }}" value="{{ old('target.0') }}">
                                      </div>
                                  </div>
                                  <div class="col-md">
                                      <div class="mb-3">
                                          <label class="form-label text-primary" for="uom">{{ __('Uom') }}</label>
                                          <select class="form-select select2 max-w-full select-uom" data-id="{{ $index }}" name="uom[]" id="uom{{ $index }}" title="Unit of Measure" required>
                                              <option value="">- Select -</option>
                                              @foreach ($uomOption as $label => $options)
                                              <optgroup label="{{ $label }}">
                                                  @foreach ($options as $option)
                                                      <option value="{{ $option }}">
                                                          {{ $option }}
                                                      </option>
                                                  @endforeach
                                              </optgroup>
                                              @endforeach
                                          </select>
                                          <input type="text" class="form-control mt-2" name="custom_uom[]" id="custom_uom{{ $index }}" @style('display: none') placeholder="Enter UoM">
                                      </div>
                                  </div>
                                  <div class="col-md">
                                      <div class="mb-3">
                                          <label class="form-label text-primary" for="type">{{ __('Type') }}</label>
                                          <select class="form-select select-type" name="type[]" id="type{{ $index }}" required>
                                              <option value="">- Select -</option>
                                              <option value="Higher Better">Higher Better</option>
                                              <option value="Lower Better">Lower Better</option>
                                              <option value="Exact Value">Exact Value</option>
                                          </select>
                                      </div>
                                  </div>
                                  <div class="col-md">
                                      <div class="mb-3">
                                          <label class="form-label text-primary" for="weightage">{{ __('Weightage') }}</label>
                                          <div class="input-group">
                                              <input type="number" min="5" max="100" class="form-control" name="weightage[]" value="{{ old('weightage.0') }}" required>
                                              <div class="input-group-append">
                                                  <span class="input-group-text">%</span>
                                              </div>
                                          </div>                                  
                                      </div>
                                      {{ $errors->first("weightage") }}
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
                </div>
                <div class="card-footer">
                    <input type="hidden" id="count" value="{{ 1 }}">
                    <div class="col-md text-end text-md-start">
                        <div class="mb-3">
                            <a class="btn btn-outline-primary rounded" id="addButton" data-id="input"><i class="ri-add-line me-1"></i><span>{{ __('Add') }}</span></a>
                        </div>
                    </div>
                    <input type="hidden" name="submit_type" id="submitType" value=""> <!-- Hidden input to store the button clicked -->
                    <div class="row">
                        <div class="col-md d-md-flex align-items-center">
                            <div class="mb-3 text-center text-md-start">
                                <h5>{{ __('Total Weightage') }} : <span class="font-weight-bold" id="totalWeightage">-</span></h5>
                            </div>
                        </div>
                        <div class="col-md-auto">
                            <div class="mb-3 text-center">
                                <a id="submitButton" data-id="save_draft" name="save_draft" class="btn btn-info rounded save-draft me-1"><span class="d-sm-inline d-none">Save as </span>Draft</a>
                                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary rounded me-1">{{ __('Cancel') }}</a>
                                <a id="submitButton" data-id="submit_form" name="submit_form" class="btn btn-primary rounded shadow"><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>{{ __('Submit') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </form>
    </div>
@endsection
@push('scripts')
    <script>
        const uom = '{{ __('Uom') }}';
        const type = '{{ __('Type') }}';
        const weightage = '{{ __('Weightage') }}';
        const errorMessages = '{{ __('Error Messages') }}';
        const errorAlertMessages = '{{ __('Error Alert Messages') }}';
        const confirmTitle = '{{ __('Confirm Title') }}';
        const confirmMessages = '{{ __('Confirm Messages') }}';
        const errorConfirmMessages = '{{ __('Error Confirm Messages') }}';
        const errorConfirmWeightageMessages1 = '{{ __('Error Confirm Weightage Messages_1') }}';
        const errorConfirmWeightageMessages2 = '{{ __('Error Confirm Weightage Messages_2') }}';
    </script>
@endpush