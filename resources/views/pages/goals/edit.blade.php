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

    <div class="mandatory-field"></div>
    
        <form id="goalForm" action="{{ route('goals.update') }}" method="POST">
        @csrf
          <input type="hidden" class="form-control" name="id" value="{{ $goal->id }}">
          <input type="hidden" class="form-control" name="employee_id" value="{{ $goal->employee_id }}">
          <input type="hidden" class="form-control" name="category" value="Goals">
          <!-- Content Row -->
          <div class="container-card">
          @foreach ($data as $index => $row)
              <div class="card col-md-12 mb-3 shadow">
                  <div class="card-body">
                      <div class='row card-title fs-16 mb-3'>
                        <div class='col'><h5>Goal {{ $index + 1 }}</h5></div>
                        @if ($index >= 1)
                            <div class='col-auto'><a class='btn-close remove_field' type='button'></a></div>
                        @endif
                      </div>
                      <div class="row mt-2">
                          <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label" for="kpi">KPI</label>
                                <textarea name="kpi[]" id="kpi" class="form-control" required>{{ $row['kpi'] }}</textarea>
                            </div>
                          </div>
                          <div class="col-md-2">
                              <div class="mb-3">
                                <label class="form-label" for="target">Target</label>
                                <input type="text" oninput="validateDigits(this, {{ $index }})" value="{{ number_format($row['target'], 0, '', ',') }}" class="form-control" required>
                                <input type="hidden" name="target[]" id="target{{ $index }}" value="{{ $row['target'] }}">
                            </div>
                          </div>
                          <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label" for="uom">{{ __('Uom') }}</label>
                                <select class="form-select select2 max-w-full select-uom" data-id="{{ $index }}" name="uom[]" id="uom{{ $index }}" title="Unit of Measure" required>
                                    <option value="">- Select -</option>
                                    @foreach ($uomOption as $label => $options)
                                    <optgroup label="{{ $label }}">
                                        @foreach ($options as $option)
                                            <option value="{{ $option }}"
                                                {{ $selectedUoM[$index] === $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>
                                <input 
                                    type="text" 
                                    name="custom_uom[]" 
                                    id="custom_uom{{ $index }}" 
                                    class="form-control mt-2" 
                                    value="{{ $row['custom_uom'] }}" 
                                    placeholder="Enter UoM" 
                                    @if ($selectedUoM[$index] !== 'Other') 
                                        style="display: none;" 
                                    @endif 
                                >
                            </div>
                          </div>
                          <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label" for="type">{{ __('Type') }}</label>
                                <select class="form-select" name="type[]" id="type{{ $index }}" required>
                                    @foreach ($typeOption as $label => $options)
                                        @foreach ($options as $option)
                                            <option value="{{ $option }}"
                                                {{ $selectedType[$index] === $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                          </div>
                          <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label" for="weightage">{{ __('Weightage') }}</label>
                                <div class="input-group flex-nowrap ">
                                    <input type="number" min="5" max="100" class="form-control" name="weightage[]" value="{{ $row['weightage'] }}" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>                                  
                                {{ $errors->first("weightage") }}
                            </div>
                          </div>
                      </div>
                  </div>
              </div>
              @endforeach
            </div>
        <input type="hidden" id="count" value="{{ $formCount }}">
        
        <div class="col-md-2">
            <a class="btn btn-outline-primary mb-4" id="addButton" data-id="edit"><i class="ri-add-line me-1"></i><span>{{ __('Add') }}</span></a>
        </div>
        @if ($approvalRequest->sendback_messages)
            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Send Back Messages') }}</label>
                        <textarea class="form-control" rows="5" @disabled(true)>{{ $approvalRequest->sendback_messages }}</textarea>
                    </div>
                </div>
            </div>
        @endif
        <div class="row align-items-center">
            <div class="col">
                <input type="hidden" name="submit_type" id="submitType" value=""> <!-- Hidden input to store the button clicked -->
                <div class="mb-3">
                    <h5>{{ __('Total Weightage') }} : <span class="font-weight-bold text-success" id="totalWeightage">{{ $totalWeightages.'%' }}</span></h5>
                </div>
            </div>
            <div class="col-md-auto">
                <div class="mb-3 text-center">
                    @if ($goal->form_status=='Draft')
                    <a id="submitButton" name="save_draft" class="btn btn-info save-draft me-3" data-id="save_draft" ><i class="fas fa-save d-sm-none"></i><span class="d-sm-block d-none">Save as Draft</span></a>  
                    @endif
                    <a href="{{ route('goals') }}" class="btn btn-outline-secondary px-3 me-2">{{ __('Cancel') }}</a>
                    <a id="submitButton" data-id="submit_form" name="submit_form" class="btn btn-primary px-3 shadow"><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>{{ __('Submit') }}</a>
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
        const errorConfirmMessages = '{{ __('Error Confirm Messages') }}';
    </script>
    @endpush