@extends('layouts_.vertical', ['page_title' => 'Appraisal'])

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
                            <li class="breadcrumb-item">{{ $parentLink }}</li>
                            <li class="breadcrumb-item active">{{ $link }}</li>
                        </ol>
                    </div>
                    <h4 class="page-title">{{ $link }}</h4>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-primary border-5 border-start-0 border-end-0">
                    <div class="card-header">
                        <div class="stepper mt-3 d-flex justify-content-between justify-content-md-around">
                            @foreach ($filteredFormData as $index => $tabs)
                            <div class="step d-flex flex-column align-items-center" data-step="{{ $index + 1 }}">
                                <div class="circle {{ $step == $index + 1 ? 'active' : ($step > $index + 1 ? 'completed' : '') }}"><i class="{{ $tabs['icon'] }}"></i></div>
                                <div class="label">{{ $tabs['name'] }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="stepperForm" action="{{ route('appraisal.submit') }}" method="POST">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $goal->employee_id }}">
                        <input type="hidden" class="form-control" name="approver_id" value="{{ $approval->approver_id }}">
                        <input type="hidden" name="formGroupName" value="{{ $formGroupData['name'] }}">
                        @foreach ($filteredFormData as $index => $row)
                            <div class="form-step {{ $step == $index + 1 ? 'active' : '' }}" data-step="{{ $index + 1 }}">
                                <div class="card-title h4 mb-4">{{ $row['title'] }}</div>
                                @include($row['blade'], [
                                'id' => 'input_' . strtolower(str_replace(' ', '_', $row['title'])),
                                'formIndex' => $index,
                                'name' => $row['name'],
                                'data' => $row['data'],
                                ])
                            </div>
                            @endforeach
                            <div class="d-flex justify-content-center py-2">
                                <button type="button" class="btn btn-light border me-3 btn-lg prev-btn" style="display: none;"><i class="ri-arrow-left-line"></i> Prev<span class="d-none d-md-inline">ious</span></button>
                                <button type="button" class="btn btn-primary btn-lg next-btn">Next <i class="ri-arrow-right-line"></i></button>
                                <button type="submit" class="btn btn-primary btn-lg submit-btn px-md-4" style="display: none;">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
<script>
    $(document).ready(function() {
        let currentStep = {{ $step }};
        const totalSteps = $('.form-step').length;

        function updateStepper(step) {
            $('.circle').removeClass('active completed');
            $('.circle').each(function(index) {
                if (index < step - 1) {
                    $(this).addClass('completed');
                } else if (index == step - 1) {
                    $(this).addClass('active');
                }
            });

            $('.form-step').removeClass('active').hide();
            $(`.form-step[data-step="${step}"]`).addClass('active').fadeIn();

            if (step === 1) {
                $('.prev-btn').hide();
            } else {
                $('.prev-btn').show();
            }

            if (step === totalSteps) {
                $('.next-btn').hide();
                $('.submit-btn').show();
            } else {
                $('.next-btn').show();
                $('.submit-btn').hide();
            }
        }

        function validateStep(step) {
            let isValid = true;
            $(`.form-step[data-step="${step}"] .form-select, .form-step[data-step="${step}"] .form-control`).each(function() {
                if (!$(this).val()) {
                    $(this).siblings('.error-message').text('This field is required.');
                    $(this).addClass('border-danger');
                    isValid = false;
                } else {
                    $(this).removeClass('border-danger');
                    $(this).siblings('.error-message').text('');
                }
            });
            return isValid;
        }

        $('.next-btn').click(function() {
            if (validateStep(currentStep)) {
                currentStep++;
                updateStepper(currentStep);
            }
        });

        $('.submit-btn').click(function() {
            if (validateStep(currentStep)) {
                return true;
            }
        });

        $('.prev-btn').click(function() {
            currentStep--;
            updateStepper(currentStep);
        });

        updateStepper(currentStep);
        
    });

    $(document).ready(function() {
        $('[id^="achievement"]').on('input', function() {
            let currentValue = $(this).val();
            let numberPart = currentValue.replace(/[^0-9]/g, ''); // Remove non-numeric characters
            $(this).val(numberPart);
        });
    });

</script>
<script src="{{ asset('js/demo.form-wizard.js') }}"></script>
@endpush