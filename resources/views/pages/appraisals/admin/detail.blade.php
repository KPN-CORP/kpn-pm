@extends('layouts_.vertical', ['page_title' => 'Appraisal'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="detail-employee">
            <div class="row">
                <div class="col-12 fs-14">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md">
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="mb-1">
                                                <span class="text-muted">Employee ID</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            : <span class="employee-id">{{ $datas->employee_id }}</span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="mb-1">
                                                <span class="text-muted">Employee Name</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            : <span>{{ $datas->fullname }}</span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="mb-1">
                                                <span class="text-muted">Join Date</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            : <span>{{ $datas->join_date }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="mb-1">
                                                <span class="text-muted">Company</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            : <span>{{ $datas->company_name }}</span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="mb-1">
                                                <span class="text-muted">Office Location</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            : <span>{{ $datas->office_area }}</span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="mb-1">
                                                <span class="text-muted">Business Unit</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            : <span>{{ $datas->group_company }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="mb-1">
                                                <span class="text-muted">Unit</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            : <span>{{ $datas->unit }}</span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="mb-1">
                                                <span class="text-muted">Designation</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            : <span>{{ $datas->designation }}</span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="mb-1">
                                                <span class="text-muted">Final Score</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            : <span>-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header pb-0">
                        <input type="hidden" id="appraisal_id" value="{{ $datas->appraisal->first()->id }}">
                        <ul class="nav nav-pills" id="myTab" role="tablist">
                            @foreach ($groupedData['manager'] as $key => $manager)
                                @foreach ($manager as $value)
                                    <li class="nav-item">
                                        <button class="btn btn-sm btn-outline-primary position-relative me-2 mb-2 type-button" 
                                                id="{{ $key }}-tab" 
                                                data-id="{{ $value->contributor->id ?? null }}"
                                                data-bs-toggle="tab" 
                                                data-bs-target="#{{ $key }}" 
                                                type="button" 
                                                role="tab" 
                                                aria-controls="{{ $key }}" 
                                                aria-selected="false">
                                            {{ $key }}
                                        </button>
                                    </li>
                                @endforeach
                            @endforeach
                            @foreach ($groupedData['peers'] as $key => $peers)
                                @foreach ($peers as $value)
                                    <li class="nav-item">
                                        <button class="btn btn-sm btn-outline-primary position-relative me-2 mb-2 type-button" 
                                                id="{{ $key }}-tab" 
                                                data-id="{{ $value->contributor->id ?? null }}"
                                                data-bs-toggle="tab" 
                                                data-bs-target="#{{ $key }}" 
                                                type="button" 
                                                role="tab" 
                                                aria-controls="{{ $key }}" 
                                                aria-selected="false">
                                            {{ $key }}
                                        </button>
                                    </li>
                                @endforeach
                            @endforeach
                            @foreach ($groupedData['subordinate'] as $key => $subs)
                                @foreach ($subs as $value)
                                    <li class="nav-item">
                                        <button class="btn btn-sm btn-outline-primary position-relative me-2 mb-2 type-button" 
                                                id="{{ $key }}-tab" 
                                                data-id="{{ $value->contributor->id ?? null }}"
                                                data-bs-toggle="tab" 
                                                data-bs-target="#{{ $key }}" 
                                                type="button" 
                                                role="tab" 
                                                aria-controls="{{ $key }}" 
                                                aria-selected="false">
                                            {{ $key }}
                                        </button>
                                    </li>
                                @endforeach
                            @endforeach
                                <li class="nav-item">
                                    <button class="btn btn-sm btn-outline-primary position-relative me-2 mb-2 type-button" 
                                        id="summary-tab" 
                                        data-id="summary"
                                        data-bs-toggle="tab" 
                                        data-bs-target="#summary" 
                                        type="button" 
                                        role="tab" 
                                        aria-controls="summary" 
                                        aria-selected="false">
                                        Summary
                                    </button>
                                </li>
                          </ul>
                    </div>
                    <div class="card-body">
                        <div id="loadingSpinner" class="text-center d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="detailContent">
                            <div class="alert alert-info" role="alert">
                                <i class="ri-arrow-up-line"></i> Click the Tab key to display the data.
                            </div>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
        </div>
    </div>
@endsection