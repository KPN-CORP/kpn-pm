@extends('layouts_.vertical', ['page_title' => 'Appraisal'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success mt-3">
                {{ session('success') }}
            </div>
        @endif
        <div class="mandatory-field">
            <div id="alertField" class="alert alert-danger alert-dismissible {{ Session::has('error') ? '':'fade' }}" role="alert" {{ Session::has('error') ? '':'hidden' }}>
                <strong>{{ Session::get('error') }}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
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
                                            Job Level {{ str_replace('Level', '', $level) }}
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
                                        $ratingDone = collect($ratingDatas[$level])->where('rating_value', false)->count();
                                        $ratingNotAllowed = collect($ratingDatas[$level])->where(function ($data) {
                                            return isset($data['rating_allowed']['status']) && $data['rating_allowed']['status'] === false;
                                        })->count();
                                    @endphp
                                    <div class="row">
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
                                                            <tr>
                                                                <td class="key-{{ $level }}">{{ $key }}</td>
                                                                <td class="rating">{{ $values['rating_count'] }}</td>
                                                                <td>{{ $values['percentage'] }}</td>
                                                                <td class="suggested-rating-count-{{ $key.'-'.$level }}">{{ $values['suggested_rating_count'] }}</td>
                                                                <td class="suggested-rating-percentage-{{ $key.'-'.$level }}">{{ $values['suggested_rating_percentage'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                        <tr>
                                                            <td>Total</td>
                                                            <td>{{ $data['count'] }}</td>
                                                            <td>100%</td>
                                                            <td class="rating-total-count-{{ $level }}">{{ count($ratingDatas[$level]) }}</td>
                                                            <td class="rating-total-percentage-{{ $level }}">100%</td>
                                                        </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="col-md text-end order-1 order-md-2 mb-2">
                                            <button class="btn btn-outline-info m-1"><i class="ri-upload-cloud-2-line d-md-none "></i><span class="d-none d-md-block">Upload Data</span></button>
                                            <button class="btn btn-outline-success m-1"><i class="ri-download-cloud-2-line d-md-none "></i><span class="d-none d-md-block">Download Data</span></button>
                                            <button class="btn btn-primary m-1 {{ $ratingDone ? '' : 'd-none' }}" data-id="{{ $level }}">Submit Rating</button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div id="alertField" class="alert alert-danger alert-dismissible {{ ($calibratorCount && $ratingDone) || $ratingNotAllowed ? '' : 'fade' }}" role="alert" {{ ($calibratorCount && $ratingDone) || $ratingNotAllowed ? '' : 'hidden' }}>
                                            <div class="row text-primary">
                                                <div class="col-auto my-auto">
                                                    <i class="ri-error-warning-line h3 fw-light"></i>
                                                </div>
                                                <div class="col">
                                                    <strong>You can't provide a rating at this moment, because some employees 360 reviews are still incomplete. Please reach out to the relevant parties to follow up on these reviews.</strong>
                                                </div>
                                            </div>
                                        </div>
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
                                                <input type="hidden" name="approver_id" value="{{ Auth::user()->employee_id }}">
                                                @forelse ($ratingDatas[$level] as $index => $item)
                                                    <input type="hidden" name="employee_id[]" value="{{ $item->employee->employee_id }}">
                                                    <input type="hidden" name="appraisal_id[]" value="{{ $item->form_id }}">
                                                    <div class="row employee-row mb-3">
                                                        <div class="col-md">
                                                            <div class="card bg-light-subtle">
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md col-sm-12">
                                                                            <span class="text-muted">Employee Name</span>
                                                                            <p class="mt-1 fw-medium">{{ $item->employee->fullname }}<span class="text-muted ms-1">{{ $item->employee->employee_id }}</span></p>
                                                                        </div>
                                                                        <div class="col d-none d-md-block text-center">
                                                                            <span class="text-muted">Job Level</span>
                                                                            <p class="mt-1 fw-medium">{{ $item->employee->job_level }}</p>
                                                                        </div>
                                                                        <div class="col d-none d-md-block">
                                                                            <span class="text-muted">Designation</span>
                                                                            <p class="mt-1 fw-medium">{{ $item->employee->designation }}</p>
                                                                        </div>
                                                                        <div class="col d-none d-md-block">
                                                                            <span class="text-muted">Unit</span>
                                                                            <p class="mt-1 fw-medium">{{ $item->employee->unit }}</p>
                                                                        </div>
                                                                        <div class="col-md col-sm-12 text-md-center">
                                                                            <span class="text-muted">Review Status</span>
                                                                            <div class="mb-2">
                                                                                @if ($item->rating_allowed['status'] && $item->approval_request)
                                                                                    @if ($item->rating_value)
                                                                                        <a href="javascript:void(0)" data-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="" class="badge bg-success rounded-pill py-1 px-2 mt-1">Approved</a>
                                                                                    @else
                                                                                        <a href="javascript:void(0)" data-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $item->current_calibrator }}" class="badge bg-warning rounded-pill py-1 px-2 mt-1">Pending Calibration</a>
                                                                                    @endif
                                                                                @else
                                                                                    @if (!$item->approval_request)
                                                                                        <a href="javascript:void(0)" data-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ 'No Appraisal Initiated' }}" class="badge bg-warning rounded-pill py-1 px-2 mt-1">Empty Appraisal</a>
                                                                                    @else
                                                                                        <a href="javascript:void(0)" data-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="360 Review incomplete" class="badge bg-warning rounded-pill py-1 px-2 mt-1">Pending 360</a>
                                                                                    @endif
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <div class="col text-center">
                                                                            <span class="text-muted">Suggested Rating</span>
                                                                            <p class="mt-1 fw-medium">{{ $item->rating_allowed['status'] ? $item->suggested_rating : '-' }}</p>
                                                                        </div>
                                                                        <div class="col">
                                                                            <span class="text-muted">Your Rating</span>
                                                                            <select name="rating[]" id="rating{{ $level }}-{{ $index }}" data-id="{{ $level }}" class="form-select form-select-sm rating-select" {{ $item->is_calibrator && $item->rating_allowed['status'] ? '' : 'disabled' }} @required(true)>
                                                                                <option value="">Please Select</option>
                                                                                @foreach ($masterRating as $rating)
                                                                                    <option value="{{ $rating->value }}" {{ $item->rating_value == $rating->value ? 'selected' : '' }}>{{ $rating->parameter }}</option>
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
                            @endforeach
                        </div>
                    </div> <!-- end card-body -->
                </div> <!-- end card-->
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    @if(Session::has('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {                
            Swal.fire({
                icon: "error",
                title: "Cannot initiate appraisal!",
                text: '{{ Session::get('error') }}',
                confirmButtonText: "OK",
            });
        });
    </script>
    @endif
@endpush