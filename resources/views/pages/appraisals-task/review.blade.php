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
        @if ($type == 'onbehalf')
            <div class="row">
                <div class="col-12">
                    <h4 class="h4 mb-4 text-gray-800">On Behalf as<span class="text-muted"> {{ $approval->approver->fullname .' ('.$approval->approver_id.')'}}</span></h4>
                </div>
            </div>
        @endif
        <div class="detail-employee">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md">
                                    <div class="row">
                                        <div class="col">
                                            <p><span class="text-muted">Employee Name:</span> {{ $goals->employee->fullname }}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <p><span class="text-muted">Employee ID:</span> {{ $goals->employee->employee_id }}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <p><span class="text-muted">Job Level:</span> {{ $goals->employee->job_level }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="row">
                                        <div class="col">
                                            <p><span class="text-muted">Business Unit:</span> {{ $goals->employee->group_company }}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <p><span class="text-muted">Division:</span> {{ $goals->employee->unit }}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <p><span class="text-muted">Designation:</span> {{ $goals->employee->designation_name }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- @if ($row->request->employee->group_company == 'Cement') --}}
        <div class="card-body m-0 py-2">
            @php
                $achievement = [
                    ["month" => "January", "value" => "-"],
                    ["month" => "February", "value" => "-"],
                    ["month" => "March", "value" => "-"],
                    ["month" => "April", "value" => "-"],
                    ["month" => "May", "value" => "-"],
                    ["month" => "June", "value" => "-"],
                    ["month" => "July", "value" => "-"],
                    ["month" => "August", "value" => "-"],
                    ["month" => "September", "value" => "-"],
                    ["month" => "October", "value" => "-"],
                    ["month" => "November", "value" => "-"],
                    ["month" => "December", "value" => "-"],
                ];
                if ($achievements && $achievements->isNotEmpty()) {
                    $achievement = json_decode($achievements->first()->data, true);
                }
            @endphp

            <div class="rounded mb-2 p-3 bg-white text-primary align-items-center">
                <div class="row mb-2">
                    <span class="fs-16 mx-1">
                        Achievements
                    </span>      
                </div>                         
                <div class="row">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 text-center align-middle">
                            <thead class="bg-primary-subtle">
                                <tr>
                                    @forelse ($achievement as $item)
                                        <th>{{ substr($item['month'], 0, 3) }}</th>
                                    @empty
                                        <th colspan="{{ count($achievement) }}">No Data
                                    @endforelse
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr>
                                    @foreach ($achievement as $item)
                                        <td>{{ $item['value'] }}</td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        {{-- @endif --}}
        <div class="step" data-step="{{ $step }}"></div>
        <div class="row justify-content-center">
            <div class="col">
                <div class="card">
                    <div class="card-header d-flex justify-content-center">
                        <div class="col col-md-10 text-center">
                            <div class="stepper mt-3 d-flex justify-content-around">
                                @foreach ($filteredFormDatas['filteredFormData'] as $index => $tabs)
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
                        <form id="formAppraisalUser" action="{{ route('appraisals-task.submitReview') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="appraisal_id" value="{{ $appraisalId }}">
                        <input type="hidden" name="employee_id" value="{{ $goals->employee_id }}">
                        <input type="hidden" name="form_group_id" value="{{ $formGroupData['data']['id'] }}">
                        <input type="hidden" class="form-control" name="approver_id" value="{{ $approval->approver_id }}">
                        <input type="hidden" class="form-control" name="userid" value="{{ $approval->approver->id }}">
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
                            <div class="row">
                            @if ($formGroupData['data']['name'] != 'Appraisal Form 360')
                                @if($appraisal?->file)
                                    @if ($appraisal->created_by == Auth::user()->id)
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="attachment" class="form-label">Supporting documents for achievement result</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <input class="form-control form-control-sm" id="attachment" name="attachment" type="file" accept=".pdf" style="max-width: 75%;">
                                                
                                                @if($appraisal?->file)
                                                    <a href="{{ asset($appraisal->file) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                        <i class="ri-file-text-line"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                Maximum file size: 10MB. Only PDF files are allowed.
                                            </small>
                                        </div>
                                    </div>
                                    @else
                                    <div class="col-md">
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <a href="{{ asset($appraisal->file) }}" target="_blank" class="btn btn-sm btn-warning rounded-pill px-2 py-1">
                                                    <i class="ri-file-text-line"></i> Supporting documents for achievement result
                                                </a>
                                            </div>
                                        </div>                                    
                                    </div>
                                    @endif
                                @endif
                            @endif
                            @if ($type != 'onbehalf')
                                <div class="col-md">
                                    <div class="mb-3 text-end">
                                        <a data-id="submit_draft" type="button" class="btn btn-sm btn-outline-secondary submit-draft"><span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>{{ __('Save as Draft') }}</a>
                                    </div>
                                </div>
                            @endif
                            </div>
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
