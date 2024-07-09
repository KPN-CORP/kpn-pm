@extends('layouts_.vertical', ['page_title' => 'Goals'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
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
        <!-- Page Heading -->
        <div class="d-flex align-items-center justify-content-start mb-4">
        </div>
        <form id="goalForm" action="{{ route('goals.submit') }}" method="POST">
            @csrf
          @foreach ($layer as $index => $data)
          <input type="hidden" class="form-control" name="users_id" value="{{ Auth::user()->id }}">
          <input type="hidden" class="form-control" name="approver_id" value="{{ $data->approver_id }}">
          <input type="hidden" class="form-control" name="employee_id" value="{{ $data->employee_id }}">
          <input type="hidden" class="form-control" name="category" value="Goals">
          @endforeach
          <!-- Content Row -->
          <div class="container-card">
            <div class="card col-md-12 mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title fs-16 mb-3">Goal {{ $index + 1 }}</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label" for="kpi">KPI</label>
                                <textarea name="kpi[]" id="kpi" class="form-control" required>{{ old('kpi.0') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label" for="target">Target</label>
                                <input  type="number" pattern="\d{1,10}" oninput="validateDigits(this)" name="target[]" value="{{ old('target.0') }}" id="target" class="form-control" required>
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
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label" for="type">Type</label>
                                <select class="form-select" name="type[]" id="type" required>
                                    <option value="">- Select -</option>
                                    <option value="Higher Better">Higher Better</option>
                                    <option value="Lower Better">Lower Better</option>
                                    <option value="Exact Value">Exact Value</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label" for="weightage">Weightage</label>
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
        <input type="hidden" id="count" value="{{ 1 }}">
        <div class="col-md text-end text-md-start">
            <div class="mb-4">
                <a class="btn btn-outline-primary rounded-pill mb-4" id="addButton" data-id="input"><i class="ri-add-line me-1"></i><span>Add</span></a>
            </div>
        </div>
        <input type="hidden" name="submit_type" id="submitType" value=""> <!-- Hidden input to store the button clicked -->
        <div class="row">
            <div class="col-md d-md-flex align-items-center mb-3">
                <h5>Total Weightage : <span class="font-weight-bold" id="totalWeightage">-</span></h5>
            </div>
            <div class="col-md-auto d-md-flex align-items-center justify-content-center text-center mb-3">
                <button type="submit" name="save_draft" class="btn btn-info rounded-pill save-draft me-2" onclick="return setSubmitType('save_draft')"><i class="ri-save-line d-sm-none"></i><span class="d-sm-block d-none">Save as Draft</span></button>
                <a href="{{ url()->previous() }}" class="btn btn-danger rounded-pill me-2">Cancel</a>
                <button type="submit" id="submitButton" name="submit_form" class="btn btn-primary rounded-pill shadow" onclick="return setSubmitType('submit_form')"><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>Submit</button>
            </div>
        </div>
        </form>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('js/goal-form.js') }}?v={{ config('app.version') }}"></script>
@endpush