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
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted">Employee ID</p>
                                        </div>
                                        <div class="col">
                                            : {{ $goals->employee->employee_id }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted">Employee Name</p>
                                        </div>
                                        <div class="col">
                                            : {{ $goals->employee->fullname }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted">Job Level</p>
                                        </div>
                                        <div class="col">
                                            : {{ $goals->employee->job_level }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted">Business Unit</p>
                                        </div>
                                        <div class="col">
                                            : {{ $goals->employee->group_company }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted">Division</p>
                                        </div>
                                        <div class="col">
                                            : {{ $goals->employee->unit }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted">Designation</p>
                                        </div>
                                        <div class="col">
                                            : {{ $goals->employee->designation }}
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
                        <div class="col-10 text-center">
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
                                        <div class="connector {{ $step > $index + 1 ? 'completed' : '' }} col mx-4"></div>
                                    @endif
                                @endforeach
                            </div>
                                              
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="stepperForm" action="{{ route('appraisals-task.submitReview') }}" method="POST">
                        @csrf
                        <input type="hidden" name="appraisal_id" value="{{ $appraisalId }}">
                        <input type="hidden" name="employee_id" value="{{ $goals->employee_id }}">
                        <input type="hidden" class="form-control" name="approver_id" value="{{ $approval->approver_id }}">
                        <input type="hidden" name="formGroupName" value="{{ $formGroupData['name'] }}">
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
                            <div class="d-flex justify-content-center py-2">
                                <button type="button" class="btn btn-light border me-3 btn-lg prev-btn" style="display: none;"><i class="ri-arrow-left-line"></i>{{ __('Prev') }}</button>
                                <button type="button" class="btn btn-primary btn-lg next-btn me-3">{{ __('Next') }} <i class="ri-arrow-right-line"></i></button>
                                @if ($filteredFormDatas['viewCategory']=="detail")
                                    <a href="{{ route('appraisals-task') }}" class="btn btn-outline-secondary border-2 btn-lg px-md-4">{{ __('Close') }}</a>
                                @else
                                    <button type="submit" class="btn btn-primary btn-lg submit-btn px-md-4" style="display: none;">{{ __('Submit') }}</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection