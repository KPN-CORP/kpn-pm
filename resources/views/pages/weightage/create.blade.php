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
        <div class="row">
          <div class="col-md-12">
            <div class="card shadow mb-4">
                <form id="form-weightage" action="{{ route('admin-weightage.submit') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md">
                                        <div class="mb-2">
                                            <h5>Period</h5>
                                            <input name="period" id="period" placeholder="select period" type="text" class="form-control required-input" required>
                                            <div class="text-danger error-message fs-14"></div>
                                        </div>
                                    </div>
                                    <div class="col-md">
                                        <div class="mb-2">
                                            <h5>Business Unit</h5>
                                            <select name="group_company" id="weightage-group-company" class="form-select select2 required-input" required>
                                                <option value="">please select</option>
                                                @foreach ($group_company as $item)
                                                    <option value="{{ $item->group_company }}">{{ $item->group_company }}</option>  
                                                @endforeach
                                            </select>
                                            <div class="text-danger error-message fs-14"></div>
                                        </div>
                                    </div>
                                    <div class="col-md">
                                        <div class="mb-2">
                                            <h5>Number of Assessment Form</h5>
                                            <select name="number_assessment_form" id="number_assessment_form" class="form-select select2 required-input" required>
                                                <option value="">please select</option>
                                                @for ($i = 1; $i <= $max_form; $i++)
                                                    <option value="{{ $i }}">{{ $i }}</option>
                                                @endfor
                                            </select>
                                            <div class="text-danger error-message fs-14"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="assessment-forms-container"></div>
                    </div>
                </form>
              <div class="card-footer">
                <div class="float-end">
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" id="submit-weightage" data-id="create" class="btn btn-primary px-3"><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>Submit</button>
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
const defaultCompetencies = {!! json_encode($competency) !!};

</script>
@endpush