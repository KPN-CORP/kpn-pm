@extends('layouts_.vertical', ['page_title' => 'Appraisal'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="detail-employee">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md">
                                    <div class="row mb-1">
                                        <div class="col-lg-4 col-md-5">
                                            <span class="text-muted">Employee ID</span>
                                        </div>
                                        <div class="col">
                                            : {{ $employee->employee_id }}
                                        </div>
                                    </div>
                                    <div class="row mb-1">
                                        <div class="col-lg-4 col-md-5">
                                            <span class="text-muted">Employee Name</span>
                                        </div>
                                        <div class="col">
                                            : {{ $employee->fullname }}
                                        </div>
                                    </div>
                                    <div class="row mb-1">
                                        <div class="col-lg-4 col-md-5">
                                            <span class="text-muted">Job Level</span>
                                        </div>
                                        <div class="col">
                                            : {{ $employee->job_level }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="row mb-1">
                                        <div class="col-lg-4 col-md-5">
                                            <span class="text-muted">Business Unit</span>
                                        </div>
                                        <div class="col">
                                            : {{ $employee->group_company }}
                                        </div>
                                    </div>
                                    <div class="row mb-1">
                                        <div class="col-lg-4 col-md-5">
                                            <span class="text-muted">Division</span>
                                        </div>
                                        <div class="col">
                                            : {{ $employee->unit }}
                                        </div>
                                    </div>
                                    <div class="row mb-1">
                                        <div class="col-lg-4 col-md-5">
                                            <span class="text-muted">Designation</span>
                                        </div>
                                        <div class="col">
                                            : {{ $employee->designation_name }}
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
                    <div class="card-header d-flex justify-content-md-center">
                        <div class="col-md-10 text-center">
                            <div class="stepper mt-3 d-flex justify-content-between justify-content-md-around">
                                @foreach ($filteredFormDatas['filteredFormData'] as $index => $tabs)
                                <div class="step" data-step="{{ $step }}"></div>
                                    <div class="step d-flex flex-column align-items-center" data-step="{{ $index + 1 }}">
                                        <div class="circle {{ $step == $index + 1 ? 'active' : ($step > $index + 1 ? 'completed' : '') }}">
                                            <i class="{{ $tabs['icon'] }}"></i>
                                        </div>
                                        <div class="label">{{ $tabs['name'] }}</div>
                                    </div>
                                    @if ($index < count($filteredFormDatas['filteredFormData']) - 1)
                                        <div class="connector {{ $step > $index + 1 ? 'completed' : '' }} col mx-4"></div>
                                    @endif
                                @endforeach
                            </div>
                                              
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="appraisalForm" action="{{ route('appraisals-task.submit') }}" method="POST">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $goal->employee_id }}">
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
                                    <a data-id="submit_form" name="submit_form" class="btn btn-primary submit-btn px-md-4" style="display: none;"><span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>{{ __('Submit') }}</a>
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
