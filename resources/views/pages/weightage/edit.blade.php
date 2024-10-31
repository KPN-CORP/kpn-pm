@extends('layouts_.vertical', ['page_title' => 'Weightage'])

@section('css')
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Begin Page Content -->
    <div class="container-fluid"> 
        @if (session('success'))
            <div class="alert alert-success mt-3">
                {!! session('success') !!}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mt-3">
                {!! session('error') !!}
            </div>
        @endif
        <!-- Content Row -->
        @php
            $form = json_decode($datas->form_data, true);    
            $count = count($form);
        @endphp
        <div class="row">
          <div class="col-md-12">
            <div class="card shadow mb-4">
            <form id="form-weightage" action="{{ route('admin-weightage.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{ $datas->id }}">
                <div class="card-body">
                  <div class="card bg-light">
                      <div class="card-body">
                          <div class="row">
                              <div class="col-md">
                                  <div class="mb-2">
                                      <h5>Period</h5>
                                      <input name="period" id="period" value="{{ $datas->period }}" placeholder="select period" type="text" class="form-control" disabled>
                                      <div class="text-danger error-message fs-14"></div>
                                  </div>
                              </div>
                              <div class="col-md">
                                  <div class="mb-2">
                                      <h5>Business Unit</h5>
                                      <select name="group_company" id="weightage-group-company" class="form-select select2" disabled>
                                          <option value="">please select</option>
                                          @foreach ($group_company as $item)
                                              <option value="{{ $item->group_company }}" {{ $item->group_company == $datas->group_company ? 'selected' : '' }}>{{ $item->group_company }}</option>  
                                          @endforeach
                                      </select>
                                      <div class="text-danger error-message fs-14"></div>
                                  </div>
                              </div>
                              <div class="col-md">
                                  <div class="mb-2">
                                      <h5>Number of Assessment Form</h5>
                                      <select name="number_assessment_form" id="number_assessment_form" class="form-select select2">
                                          <option value="">please select</option>
                                          @for ($i = 1; $i <= $max_form; $i++)
                                              <option value="{{ $i }}" {{ $count == $i ? 'selected' : '' }}>{{ $i }}</option>
                                          @endfor
                                      </select>
                                      <div class="text-danger error-message fs-14"></div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div id="assessment-forms-container">
                      @foreach ($form as $key => $data)
                        <div class="card bg-light assessment-form">
                            <div class="card-header pb-0">
                                <h5>Assessment Form {{ $key + 1 }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md">
                                        <div class="mb-3">
                                            <h5>Job Level</h5>
                                            <select name="job_level[{{ $key }}][]" id="job_level-{{ $key }}" class="form-select select2" multiple>
                                                @foreach ($job_level as $level)
                                                    <option value="{{ $level->job_level }}" @if(in_array($level->job_level, $data['jobLevel'])) selected @endif>{{ $level->job_level }}</option>
                                                @endforeach
                                            </select>
                                            <div class="text-danger error-message fs-14"></div>
                                        </div>
                                    </div>
                                </div>
                                @foreach ($data['competencies'] as $index => $competencies)
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <h5>{{ $competencies['competency'] }}</h5>
                                            <input type="hidden" name="competency-{{ $key }}-{{ $index }}" value="{{ $competencies['competency'] }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <h5>Weightage</h5>
                                            <div class="input-group">
                                                <input type="number" name="weightage-{{ $key }}-{{ $index }}" id="weightage-{{ $key }}-{{ $index }}" min="0" max="100" class="form-control weightage-input" value="{{ $competencies['weightage'] }}">
                                                <span class="input-group-text"><i class="ri-percent-line"></i></span>
                                            </div>
                                            <div class="text-danger error-message fs-14"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3 {{ $competencies['formName']=='KPI'?'d-none':'' }}">
                                            <h5>Form Name</h5>
                                            <select name="form-name-{{ $key }}{{ $index }}" id="form-name-{{ $key }}{{ $index }}" class="form-select select2" {{ $competencies['competency']=='Key Performance Indicator'?'':'required' }} {{ $competencies['formName']=='KPI'?'disabled':'' }}>
                                                <option value="">please select</option>
                                                @foreach ($formAppraisal as $form)
                                                    <option value="{{ $form->name }}" {{ $form->name == $competencies['formName'] ? 'selected' : '' }}>{{ $form->name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="text-danger error-message fs-14"></div>
                                        </div>
                                    </div>
                                    <div class="col-md">
                                        <div class="mb-3 {{ $competencies['formName']=='KPI'?'d-none':'' }}">
                                            <h5>{{ $competencies['competency'] }} Weightage 360 in %</h5>
                                            <select name="weightage-360-{{ $key }}-{{ $index }}" id="weightage-360-{{ $key }}{{ $index }}" class="form-select select2" {{ $competencies['competency']=='Key Performance Indicator'?'':'required' }} {{ $competencies['formName']=='KPI'?'disabled':'' }}>
                                                <option value="">please select</option>
                                                @foreach ($data360s as $data)
                                                    <option value="{{ $data->form_data }}" {{ str_replace(' ', '', $data->form_data) == json_encode($competencies['weightage360']) ? 'selected' : '' }}>{{ $data->name .' '.str_replace(' ', '', $data->form_data) }}</option>
                                                @endforeach
                                            </select>
                                            <div class="text-danger error-message fs-14"></div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <h5>Total</h5>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="input-group">
                                                <input id="total-{{ $key }}-0" type="number" min="0" max="100" class="form-control" value="" readonly>
                                                <span class="input-group-text"><i class="ri-percent-line"></i></span>
                                            </div>
                                            <div class="invalid-feedback"></div>
                                            <div class="text-danger error-message fs-14"></div>
                                        </div>
                                    </div>
                                    <div class="col md">
                                        {{-- Empty Column --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                      @endforeach
                  </div>
                  </div>
            </form>
              <div class="card-footer">
                <div class="float-end">
                    <a href="{{ route('admin-weightage.detail', $datas->id) }}" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" id="submit-weightage" data-id="update" class="btn btn-primary px-3"><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>Save</button>
                </div>
              </div>
            </div>
          </div>
        </div>
    </div>
@endsection
@push('scripts')
<script>
// Function to calculate totals grouped by first index number
function calculateFormTotals() {
    // Create an object to store totals for each group
    const groupTotals = {};
    
    // Get all weightage inputs
    const weightageInputs = document.querySelectorAll('input[id^="weightage-"]');
    
    // Calculate totals for each group
    weightageInputs.forEach(input => {
        // Extract the group index (first number after 'weightage-')
        const groupIndex = input.id.split('-')[1];
        
        // Initialize group total if not exists
        if (!groupTotals[groupIndex]) {
            groupTotals[groupIndex] = 0;
        }
        
        // Add current input value to group total
        const value = parseFloat(input.value) || 0;
        groupTotals[groupIndex] += value;
    });
    
    // Update total fields for each group
    Object.keys(groupTotals).forEach(groupIndex => {
        const total = groupTotals[groupIndex];
        const totalInput = document.querySelector(`input[id^="total-${groupIndex}"]`);
        
        if (totalInput) {
            const errorMessage = totalInput.closest('.input-group').nextElementSibling;
            totalInput.value = total.toFixed(0);
            
            // Add visual feedback if total is not 100%
            if (total !== 100) {
                totalInput.classList.add('is-invalid');
                errorMessage.textContent = 'Total must be 100%';
            } else {
                totalInput.classList.remove('is-invalid');
                errorMessage.textContent = '';
            }
        }
    });
}

// Add event listeners to all weightage inputs
document.addEventListener('DOMContentLoaded', () => {
    const weightageInputs = document.querySelectorAll('input[id^="weightage-"]');
    
    weightageInputs.forEach(input => {
        // Calculate totals when input changes
        input.addEventListener('input', () => {
            // Ensure input value is between 0 and 100
            if (input.value > 100) {
                input.value = 100;
            } else if (input.value < 0) {
                input.value = 0;
            }
            
            calculateFormTotals();
        });
    });
    
    // Initial calculation
    calculateFormTotals();
});

const jobLevels = {!! json_encode($job_level) !!};
const formAppraisals = {!! json_encode($formAppraisal) !!};
const data360s = {!! json_encode($data360s) !!};
const defaultCompetencies = {!! json_encode($competency) !!};

</script>
@endpush