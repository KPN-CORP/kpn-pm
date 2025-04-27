@extends('layouts_.vertical', ['page_title' => 'Appraisal'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success mt-3">
                {!! session('success') !!}
            </div>
        @endif
        <div class="mandatory-field">
            <div id="alertField" class="alert alert-danger alert-dismissible {{ Session::has('error') ? '':'fade' }}" role="alert" {{ Session::has('error') ? '':'hidden' }}>
                <strong>{!! Session::get('error') !!}{!! Session::get('errorMessage') !!}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        @if (isset($calibrations) && !empty($calibrations))            
        <div class="row">
            <div class="col-md p-0 p-md-2">
                <div class="card">
                    <div class="card-body p-2">
                        <ul class="nav nav-pills mb-3 border-bottom justify-content-evenly justify-content-md-start">
                                @foreach ($calibrations as $level => $data)
                                    <li class="nav-item">
                                        <a href="#{{ strtolower($level) }}" data-bs-toggle="tab" 
                                        aria-expanded="{{ $level == $activeLevel ? 'true' : 'false' }}" 
                                        class="nav-link {{ $level == $activeLevel ? 'active' : '' }}">
                                            Job Level {{ str_replace('Level', '', $level) == '23' ? '2-3' : (str_replace('Level', '', $level) == '45' ? '4-5' : (str_replace('Level', '', $level) == '67' ? '6-7' : '8-9')) }}
                                        </a>
                                    </li>
                                @endforeach
                        </ul>
                        <div class="tab-content">
                            @foreach ($calibrations as $level => $data)
                            <div class="tab-pane {{ $activeLevel == $level ? 'show active' : '' }}" id="{{ strtolower($level) }}">
                                @php
                                        // Count items where 'is_calibrator' is true in $ratingDatas[$level]
                                        $calibratorCount = collect($ratingDatas[$level])->where('is_calibrator', false)->count();
                                        $rating_incomplete = collect($ratingDatas[$level])->where('rating_incomplete', '!=', 0)->count();
                                        $rating_status = collect($ratingDatas[$level])->where('rating_status', 'Approved')->count();
                                        $employeeCount = collect($ratingDatas[$level])->count();
                                        $ratingDone = collect($ratingDatas[$level])->where('rating_value', false)->count();
                                        $ratingNotAllowed = collect($ratingDatas[$level])->where(function ($data) {
                                            return isset($data['rating_allowed']['status']) && $data['rating_allowed']['status'] === false;
                                        })->count();
                                        $requestApproved = collect($ratingDatas[$level])
                                        ->where(function ($data) {
                                            return isset($data['status']) && $data['status'] === 'Approved';
                                        })
                                        ->count();
                                        $keys = array_keys($data['combined']);
                                        $firstKey = $keys[0];
                                        $secondKey = $keys[1];
                                        $lastKey = array_key_last($data['combined']);
                                    @endphp
                                    <div class="row">
                                        @if ($rating_status == $employeeCount)
                                        <div class="mb-3">
                                            <div id="alertField" class="alert alert-success alert-dismissible" role="alert">
                                                <div class="row fs-5">
                                                    <div class="col-auto my-auto">
                                                        <i class="ri-check-double-line h3 fw-light"></i>
                                                    </div>
                                                    <div class="col">
                                                        <strong>You already submitted a rating. Thank you!</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @else
                                        <div class="col-md-5 order-2 order-md-1">
                                            <table id="table-{{ $level }}" class="table table-sm text-center">
                                                <thead>
                                                    <tr>
                                                        <td rowspan="2" class="align-middle table-secondary fw-bold">KPI</td>
                                                        <td colspan="2" class="table-success fw-bold">Targeted Ratings</td>
                                                        <td colspan="2" class="table-info fw-bold">Your Ratings</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="table-success fw-bold">Employee</td>
                                                        <td class="table-success fw-bold">%</td>
                                                        <td class="table-info fw-bold">Employee</td>
                                                        <td class="table-info fw-bold">%</td>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                        @foreach ($data['combined'] as $key => $values)
                                                        @php
                                                            $formattedKey = str_replace(' ', '', $key); // Replace spaces with hyphens
                                                        @endphp
                                                        <tr>
                                                            <td class="key-{{ $level }}">{{ $key }}</td>
                                                            <td class="rating">{{ $data['count'] <= 2 ? 0 : $values['rating_count'] }}</td>
                                                            <td>{{ $values['percentage'] }}</td>
                                                            <td class="suggested-rating-count-{{ $formattedKey.'-'.$level }}">{{ $values['suggested_rating_count'] }}</td>
                                                            <td class="suggested-rating-percentage-{{ $formattedKey.'-'.$level }}">{{ $values['suggested_rating_percentage'] }}</td>
                                                        </tr>
                                                        @endforeach
                                                        <tr>
                                                            <td>Total</td>
                                                            <td>{{ $data['count'] <= 2 ? 0 : $data['count'] }}</td>
                                                            <td>100%</td>
                                                            <td class="rating-total-count-{{ $level }}">{{ count($ratingDatas[$level]) }}</td>
                                                            <td class="rating-total-percentage-{{ $level }}">100%</td>
                                                        </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        @endif
                                        <div class="col-md text-end order-1 order-md-2 mb-2">
                                            <a class="btn btn-outline-info m-1 {{ $ratingNotAllowed || $calibratorCount ? 'd-none' : '' }}" data-bs-toggle="modal" data-bs-id="{{ $level }}" data-bs-target="#importModal{{ $level }}" title="Import Rating"><i class="ri-upload-cloud-2-line d-md-none"></i><span class="d-none d-md-block">Upload Rating</span></a>
                                            <a href="{{ route('rating.export', $level) }}" class="btn btn-outline-success m-1"><i class="ri-download-cloud-2-line d-md-none "></i><span class="d-none d-md-block">Download Rating</span></a>
                                            <button class="btn btn-primary m-1 {{ $ratingDone ? '' : 'd-none' }}" data-id="{{ $level }}">Submit Rating</button>
                                        </div>
                                    </div>
                                    <div class="mb-3 rating-info">
                                        @if (!$rating_status)
                                            <div id="alertField" class="alert alert-danger alert-dismissible {{ ($calibratorCount && $ratingDone ) || $ratingNotAllowed || !$requestApproved ? '' : 'fade' }}" role="alert" {{ ($calibratorCount && $ratingDone) || $ratingNotAllowed || !$requestApproved? '' : 'hidden' }}>
                                                <div class="row text-primary fs-5">
                                                    <div class="col-auto my-auto">
                                                        <i class="ri-error-warning-line h3 fw-light"></i>
                                                    </div>
                                                    <div class="col">
                                                        <strong>{{ __('rating_alert') }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="alertField" class="alert alert-warning alert-dismissible {{ ((!$calibratorCount && !$ratingDone ) || $requestApproved) && $employeeCount <= 2 ? '' : 'fade' }}" role="alert" {{ ((!$calibratorCount && !$ratingDone) || $requestApproved) && $employeeCount <= 2 ? '' : 'hidden' }}>
                                                <div class="row fs-5">
                                                    <div class="col-auto my-auto">
                                                        <i class="ri-information-line h3 fw-light"></i>
                                                    </div>
                                                    <div class="col">
                                                        <strong class="{{ $employeeCount == 1 ? '' : 'd-none' }}">
                                                            {{ __('rating_employee_single', ['secondKey' => $secondKey, 'lastKey' => $lastKey]) }}
                                                        </strong>
                                                        <strong class="{{ $employeeCount > 1 ? '' : 'd-none' }}">
                                                            {{ __('rating_employee_double', ['firstKey' => $firstKey, 'lastKey' => $lastKey]) }}
                                                        </strong>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="employee-list-container" data-level="{{ $level }}">
                                        <div class="row justify-content-end">
                                            <div class="col-auto">
                                                <div class="input-group mb-3">
                                                    <input type="text" name="search-{{ $level }}" id="search-{{ $level }}" data-id="{{ $level }}" class="form-control search-input" placeholder="search.." aria-label="search" aria-describedby="search">
                                                    <span class="input-group-text bg-primary text-white" id="search"><i class="ri-search-line"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="employeeList-{{ $level }}">
                                            <form id="formRating{{ $level }}" action="{{ route('rating.submit') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="calibrator_pending_count" value="{{ $calibrations[$level]['calibratorPendingCount'] }}">
                                                <input type="hidden" name="id_calibration_group" value="{{ $id_calibration_group }}">
                                                <input type="hidden" name="approver_id" value="{{ Auth::user()->employee_id }}">
                                                @forelse ($ratingDatas[$level] as $index => $item)
                                                @php
                                                 $suggestRating = $item->previous_rating ?? $item->suggested_rating;
                                                @endphp
                                                <input type="hidden" name="employee_id[]" value="{{ $item->employee->employee_id }}">
                                                    <input type="hidden" name="appraisal_id[]" value="{{ $item->form_id }}">
                                                    <div class="row employee-row">
                                                        <div class="col-md">
                                                            <div class="card mb-2 bg-light-subtle">
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md col-sm-12">
                                                                            <span class="text-muted">Employee Name</span>
                                                                            <p class="mt-1 fw-medium">{{ $item->employee->fullname }}<span class="text-muted ms-1">{{ $item->employee->employee_id }}</span></p>
                                                                        </div>
                                                                        <div class="col d-none d-md-block">
                                                                            <span class="text-muted">Designation</span>
                                                                            <p class="mt-1 fw-medium">{{ $item->employee->designation_name }}</p>
                                                                        </div>
                                                                        <div class="col d-none d-md-block">
                                                                            <span class="text-muted">Unit</span>
                                                                            <p class="mt-1 fw-medium">{{ $item->employee->unit }}</p>
                                                                        </div>
                                                                        <div class="col-md col-sm-12 text-md-center">
                                                                            <span class="text-muted">Review Status</span>
                                                                            <div class="mb-2">
                                                                                @if ($item->rating_allowed['status'] && $item->form_id)
                                                                                    @if ($item->rating_incomplete || !$requestApproved || $item->status == 'Pending')
                                                                                        @if ($item->rating_allowed['status'] && $item->form_id && $item->current_calibrator)
                                                                                            <a href="javascript:void(0)" data-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $item->current_calibrator }}" class="badge bg-warning rounded-pill py-1 px-2 mt-1">Pending Calibration</a>
                                                                                        @else
                                                                                            @if ($item->current_calibrator)
                                                                                                <a href="javascript:void(0)" data-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="360 Review incomplete" class="badge bg-warning rounded-pill py-1 px-2 mt-1">Pending 360</a>
                                                                                            @else
                                                                                                <a href="javascript:void(0)" data-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Waiting Review: {{ $item->approver_name.' ('.$item->current_approval_id.')' }}" class="badge bg-warning rounded-pill py-1 px-2 mt-1">On Manager Review</a>
                                                                                            @endif
                                                                                        @endif
                                                                                    @else
                                                                                        <a href="javascript:void(0)" data-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $item->approver_name }} @isset($item->rating_approved_date) :{{ $item->rating_approved_date }} @endisset" class="badge bg-success rounded-pill py-1 px-2 mt-1">Approved</a>
                                                                                    @endif
                                                                                @else
                                                                                    @if (!$item->form_id)
                                                                                        <a href="javascript:void(0)" data-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ 'No Appraisal Initiated' }}" class="badge bg-warning rounded-pill py-1 px-2 mt-1">Empty Appraisal</a>
                                                                                    @else
                                                                                        <a href="javascript:void(0)" data-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="360 Review incomplete" class="badge bg-warning rounded-pill py-1 px-2 mt-1">Pending 360</a>
                                                                                    @endif
                                                                                @endif 
                                                                            </div>
                                                                        </div>
                                                                        <div class="col text-center">
                                                                            <span class="text-muted">Score To Rating</span>
                                                                            <p class="mt-1 fw-medium">{{ $item->rating_allowed['status'] && $item->form_id && $item->current_calibrator || !$item->rating_incomplete ? $item->suggested_rating : '-' }}</p>
                                                                        </div>
                                                                        <div class="col text-center">
                                                                            <span class="text-muted">Previous Rating</span>
                                                                            <p class="mt-1 fw-medium" data-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $item->previous_rating_name }}">{{ $item->rating_allowed['status'] && $item->form_id && $item->current_calibrator && $item->previous_rating || !$item->rating_incomplete ? $item->previous_rating : '-' }}</p>
                                                                        </div>
                                                                        <div class="col rating-field">
                                                                            <span class="text-muted">Your Rating</span>
                                                                            <select name="rating[]" id="rating{{ $level }}-{{ $index }}" data-id="{{ $level }}" class="form-select form-select-sm rating-select" {{ $item->is_calibrator && $item->rating_allowed['status'] && $item->status == 'Approved' || $calibrations[$level]['calibratorPendingCount'] ? '' : 'disabled' }} @required(true)>
                                                                                <option value="">-</option>
                                                                                @foreach ($masterRating as $rating)
                                                                                    <option value="{{ $rating->value }}" 
                                                                                        {{ $item->rating_value ? ($item->rating_value == $rating->value ? 'selected' : '') : ($suggestRating == $rating->parameter ? 'selected' : '') }}>
                                                                                        {{ $rating->parameter }}
                                                                                    </option>
                                                                                @endforeach
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div id="emptyState-{{ $level }}" class="row">
                                                        <div class="col-md-12">
                                                            <div class="card">
                                                                <div class="card-body text-center">
                                                                    <h5 class="card-title">No Employees Found</h5>
                                                                    <p class="card-text">There are no employees matching your search criteria or the employee list is empty.</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforelse
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <!-- Modal -->
                                <div class="modal fade" id="importModal{{ $level }}" role="dialog" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" id="importModalLabel">Upload Rating - {{ $level }}</h4>
                                            </div>
                                            <form id="importRating" action="{{ route('rating.import') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col">
                                                            <div class="mb-4 mt-2">
                                                                <label class="form-label" for="excelFile">Upload Excel File</label>
                                                                <input type="file" class="form-control" id="excelFile" name="excelFile" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Hidden field to pass rating quotas data -->
                                                    <input type="hidden" name="ratingCounts" value="{{ $data['count'] }}">
                                                    <input type="hidden" name="ratingQuotas" value="{{ json_encode($data['combined']) }}">
                                                    
                                                </div>
                                                <!-- Submit button inside the form -->
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" id="importRatingButton{{ $level }}" class="btn btn-primary">
                                                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                                                        Submit
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div> <!-- end card-body -->
                </div> <!-- end card-->
            </div>
        </div>
        @else
        <div class="row">
            <div class="col-md p-0 p-md-2">
                <div class="card">
                    <div class="card-body p-2">
                        <div>Data not available.</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

@endsection
@push('scripts')
    <script>
        const titleEmpty = '{{ __('Ratings are Empty') }}';
        const textEmpty = '{{ __('Text Ratings are Empty') }}';
        const titleMismatch = '{{ __('Rating Mismatch') }}';
        const textMismatch_1 = '{{ __('Text Mismatch_1') }}';
        const textMismatch_2 = '{{ __('Text Mismatch_2') }}';
        const titleNotAllowed = '{{ __('Submit Not Allowed') }}';
        const textNotAllowed = '{{ __('Text Not Allowed') }}';
        const mismatchedRatingsMessages = '{{ __('Mismatch Error Message') }}';
    </script>
    @if(!$calibrations)
    <script>
        document.addEventListener('DOMContentLoaded', function () {                
            Swal.fire({
                icon: "error",
                title: "Cannot initiate rating!",
                text: '{{ Session::pull('error') }}',
                confirmButtonText: "OK",
            }).then((result) => {
                if (result.isConfirmed) {
                    history.back(); // Go back to the previous page
                }
            });
        });
    </script>
    @endif
    @if(Session::has('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {                
            Swal.fire({
                icon: "error",
                title: "Error!",
                text: '{{ Session::pull('error') }}',
                confirmButtonText: "OK",
            }).then((result) => {
                if (result.isConfirmed) {
                    return; // Go back to the previous page
                }
            });
        });
    </script>
    @endif
@endpush