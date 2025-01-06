@extends('layouts_.vertical', ['page_title' => 'Appraisal'])

@section('css')
<style>

    .card p {
        margin: 5px 0;
    }
    
    </style>
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="detail-employee">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md">
                                    <div class="row">
                                        <div class="col">
                                            <p><span class="text-muted">Employee Name:</span> {{ $appraisal->employee->fullname }}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <p><span class="text-muted">Employee ID:</span> {{ $appraisal->employee->employee_id }}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <p><span class="text-muted">Job Level:</span> {{ $appraisal->employee->job_level }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="row">
                                        <div class="col">
                                            <p><span class="text-muted">Business Unit:</span> {{ $appraisal->employee->group_company }}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <p><span class="text-muted">Division:</span> {{ $appraisal->employee->unit }}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <p><span class="text-muted">Designation:</span> {{ $appraisal->employee->designation_name }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col">
                <div class="card">
                    <div class="card-header d-flex justify-content-center">
                        <div class="col col-md-10 text-center">
                            <div class="stepper mt-3 d-flex justify-content-between justify-content-md-around">
                                @foreach ($filteredFormDatas['filteredFormData'] as $index => $tabs)
                                <div class="step" data-step="{{ $step }}"></div>
                                    <div class="step d-flex flex-column align-items-center" data-step="{{ $index + 1 }}">
                                        <div class="circle {{ $step == $index + 1 ? 'active' : ($step > $index + 1 ? 'completed' : '') }}">
                                            <i class="{{ $tabs['icon'] }}"></i>
                                        </div>
                                        <div class="label {{ $step == $index + 1 ? 'active' : '' }}">{{ $tabs['name'] }}</div>
                                    </div>
                                    @if ($index < count($filteredFormDatas['filteredFormData']) - 1)
                                        <div class="connector {{ $step > $index + 1 ? 'completed' : '' }} col mx-md-4 d-none d-md-block"></div>
                                    @endif
                                @endforeach
                            </div>
                                              
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="appraisalForm" action="{{ route('appraisals-task.submitReview') }}" method="POST">
                        @csrf
                        <input type="hidden" name="appraisal_id" value="{{ $appraisalId }}">
                        <input type="hidden" name="employee_id" value="{{ $goals->employee_id }}">
                        <input type="hidden" name="form_group_id" value="{{ $formGroupData['data']['id'] }}">
                        <input type="hidden" class="form-control" name="approver_id" value="{{ $approval->approver_id }}">
                        <input type="hidden" name="formGroupName" value="{{ $formGroupData['data']['name'] }}">
                        @foreach ($filteredFormDatas['filteredFormData'] as $index => $row)
                            <div class="form-step {{ $step == $index + 1 ? 'active' : '' }}" data-step="{{ $index + 1 }}">
                                <div class="card-title h4 mb-4">{{ $row['title'] }}</div>
                                @include($row['blade'], [
                                'id' => 'input_' . strtolower(str_replace(' ', '_', $row['title'])),
                                'formIndex' => $index,
                                'name' => $row['name'],
                                'data' => $row['data'],
                                'isManager' => $approval->layer_type == 'manager',
                                'viewCategory' => $filteredFormDatas['viewCategory']
                                ])
                            </div>
                            @endforeach
                            <input type="hidden" name="submit_type" id="submitType" value="">
                            <div class="d-flex justify-content-center py-2">
                                <a type="button" class="btn btn-light border me-3 prev-btn" style="display: none;"><i class="ri-arrow-left-line"></i>{{ __('Prev') }}</a>
                                <a type="button" class="btn btn-primary next-btn">{{ __('Next') }} <i class="ri-arrow-right-line"></i></a>
                                @if ($filteredFormDatas['viewCategory']=="detail")
                                    <a href="{{ route('appraisals-task') }}" class="btn btn-outline-primary px-md-4">{{ __('Close') }}</a>
                                @else
                                    <a data-id="submit_form" type="submit" class="btn btn-primary submit-btn px-md-4" style="display: none;"><span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>{{ __('Submit') }}</a>
                                @endif
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
        const errorMessages = '{{ __('Empty Messages') }}';
    </script>
@endpush
