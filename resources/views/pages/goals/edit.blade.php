@extends('layouts_.vertical', ['page_title' => 'Goals'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('goals') }}">{{ $parentLink }}</a></li>
                        <li class="breadcrumb-item active">{{ $link }}</li>
                    </ol>
                </div>
                <h4 class="page-title">{{ $link }}</h4>
            </div>
        </div>
    </div>
        
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
                                <input type="number" oninput="validateDigits(this)" name="target[]" value="{{ $row['target'] }}" id="target" class="form-control" required>
                            </div>
                          </div>
                          <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label" for="uom">UoM</label>
                                <select class="form-select select2 max-w-full" name="uom[]" id="uom{{ $index }}" onchange="otherUom('{{ $index }}')" title="Unit of Measure" required>
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
                                <label class="form-label" for="type">Type</label>
                                <select class="form-select" name="type[]" id="type" required>
                                    <option value="">Select</option>
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
                                <label class="form-label" for="weightage">Weightage</label>
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
            <a class="btn btn-outline-primary rounded-pill mb-4" id="addButton" data-id="edit"><i class="ri-add-line me-1"></i><span>Add</span></a>
        </div>
        @if ($approvalRequest->sendback_messages)
            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <label class="form-label">Send Back Messages</label>
                        <textarea class="form-control" rows="5" @disabled(true)>{{ $approvalRequest->sendback_messages }}</textarea>
                    </div>
                </div>
            </div>
        @endif
        <div class="row align-items-center">
            <div class="col">
                <input type="hidden" name="submit_type" id="submitType" value=""> <!-- Hidden input to store the button clicked -->
                <div class="mb-3">
                    <h5>Total Weightage : <span class="font-weight-bold text-success" id="totalWeightage">{{ $totalWeightages.'%' }}</span></h5>
                </div>
            </div>
            <div class="col-md-auto">
                <div class="mb-3 text-center">
                    @if ($goal->form_status=='Draft')
                    <button type="submit" name="save_draft" class="btn btn-info save-draft me-3 rounded-pill" onclick="return setSubmitType('save_draft')"><i class="fas fa-save d-sm-none"></i><span class="d-sm-block d-none">Save as Draft</span></button>  
                    @endif
                    <a href="{{ url()->previous() }}" class="btn btn-danger px-3 me-2 rounded-pill">Cancel</a>
                    <button type="submit" id="submitButton" name="submit_form" class="btn btn-primary px-3 rounded-pill shadow" onclick="return setSubmitType('submit_form')"><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>Submit</button>
                </div>
            </div>
        </div>
        </form>
    </div>
    @endsection

    @push('scripts')
        <script src="{{ asset('js/goal-form.js') }}?v={{ config('app.version') }}"></script>
    @endpush
