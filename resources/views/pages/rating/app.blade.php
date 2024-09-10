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
                            <li class="nav-item">
                                <a href="#level23" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                    Job Level 2-3
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#level45" data-bs-toggle="tab" aria-expanded="false" class="nav-link ">
                                    Job Level 4-5
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#level67" data-bs-toggle="tab" aria-expanded="false" class="nav-link ">
                                    Job Level 6-7
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#level89" data-bs-toggle="tab" aria-expanded="false" class="nav-link ">
                                    Job Level 8-9
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane show active" id="level23">
                                <div class="row">
                                    <div class="col-md-5 order-2 order-md-1">
                                        <table class="table table-sm text-center">
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
                                                <tr>
                                                    <td>A</td>
                                                    <td>1</td>
                                                    <td>10.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>B</td>
                                                    <td>1</td>
                                                    <td>10.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>C</td>
                                                    <td>1</td>
                                                    <td>10.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>D</td>
                                                    <td>1</td>
                                                    <td>20.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>E</td>
                                                    <td>1</td>
                                                    <td>50.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>Total</td>
                                                    <td>5</td>
                                                    <td>100.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md text-end order-1 order-md-2 mb-2">
                                        <button class="btn btn-outline-info m-1"><i class="ri-upload-cloud-2-line d-md-none "></i><span class="d-none d-md-block">Upload Data</span></button>
                                        <button class="btn btn-outline-success m-1"><i class="ri-download-cloud-2-line d-md-none "></i><span class="d-none d-md-block">Download Data</span></button>
                                        <button class="btn btn-primary m-1">Submit Rating</button>
                                    </div>
                                </div>
                                <div class="mandatory-field">
                                    <div class="alert alert-danger d-flex align-items-center text-primary" role="alert">
                                        <i class="ri-error-warning-line me-2 fs-16"></i>
                                        <div class="fw-medium">
                                            You can't provide a rating at this moment, because some employees 360 reviews are still incomplete. Please reach out to the relevant parties to follow up on these reviews.
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md">
                                        <div class="card bg-light-subtle">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md col-sm-12">
                                                        <span class="text-muted">Employee Name</span>
                                                        <p class="mt-1 fw-medium">Supardy<span class="text-muted ms-1">011020040011</span></p>
                                                    </div>
                                                    <div class="col d-none d-md-block text-center">
                                                        <span class="text-muted">Job Level</span>
                                                        <p class="mt-1 fw-medium">3A</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Designation</span>
                                                        <p class="mt-1 fw-medium">Sysdev</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Unit</span>
                                                        <p class="mt-1 fw-medium">HCIS</p>
                                                    </div>
                                                    <div class="col-md col-sm-12 text-md-center">
                                                        <span class="text-muted">Review Status</span>
                                                        <div class="mb-2">
                                                            <a href="javascript:void(0)" d ata-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="" class="badge bg-success rounded-pill py-1 px-2 mt-1">Done</a>
                                                        </div>
                                                    </div>
                                                    <div class="col text-center">
                                                        <span class="text-muted">Suggested Rating</span>
                                                        <p class="mt-1 fw-medium">C</p>
                                                    </div>
                                                    <div class="col">
                                                        <span class="text-muted">Your Rating</span>
                                                        <select name="rating" id="rating" class="form-select">
                                                            <option value="">Please Select</option>
                                                            <option value="1">A</option>
                                                            <option value="2">B</option>
                                                            <option value="3">C</option>
                                                            <option value="4">D</option>
                                                            <option value="5">E</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md">
                                        <div class="card bg-light-subtle">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md col-sm-12">
                                                        <span class="text-muted">Employee Name</span>
                                                        <p class="mt-1 fw-medium">Messi<span class="text-muted ms-1">011020040011</span></p>
                                                    </div>
                                                    <div class="col d-none d-md-block text-center">
                                                        <span class="text-muted">Job Level</span>
                                                        <p class="mt-1 fw-medium">3A</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Designation</span>
                                                        <p class="mt-1 fw-medium">Sysdev</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Unit</span>
                                                        <p class="mt-1 fw-medium">HCIS</p>
                                                    </div>
                                                    <div class="col-md col-sm-12 text-md-center">
                                                        <span class="text-muted">Review Status</span>
                                                        <div class="mb-2">
                                                            <a href="javascript:void(0)" d ata-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="" class="badge bg-warning rounded-pill py-1 px-2 mt-1">On Review</a>
                                                        </div>
                                                    </div>
                                                    <div class="col text-center">
                                                        <span class="text-muted">Suggested Rating</span>
                                                        <p class="mt-1 fw-medium">C</p>
                                                    </div>
                                                    <div class="col">
                                                        <span class="text-muted">Your Rating</span>
                                                        <select name="rating" id="rating" class="form-select">
                                                            <option value="">Please Select</option>
                                                            <option value="1">A</option>
                                                            <option value="2">B</option>
                                                            <option value="3">C</option>
                                                            <option value="4">D</option>
                                                            <option value="5">E</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md">
                                        <div class="card bg-light-subtle">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md col-sm-12">
                                                        <span class="text-muted">Employee Name</span>
                                                        <p class="mt-1 fw-medium">Valentino Rossi<span class="text-muted ms-1">011020040011</span></p>
                                                    </div>
                                                    <div class="col d-none d-md-block text-center">
                                                        <span class="text-muted">Job Level</span>
                                                        <p class="mt-1 fw-medium">3A</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Designation</span>
                                                        <p class="mt-1 fw-medium">Sysdev</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Unit</span>
                                                        <p class="mt-1 fw-medium">HCIS</p>
                                                    </div>
                                                    <div class="col-md col-sm-12 text-md-center">
                                                        <span class="text-muted">Review Status</span>
                                                        <div class="mb-2">
                                                            <a href="javascript:void(0)" d ata-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="" class="badge bg-warning rounded-pill py-1 px-2 mt-1">On Review</a>
                                                        </div>
                                                    </div>
                                                    <div class="col text-center">
                                                        <span class="text-muted">Suggested Rating</span>
                                                        <p class="mt-1 fw-medium">C</p>
                                                    </div>
                                                    <div class="col">
                                                        <span class="text-muted">Your Rating</span>
                                                        <select name="rating" id="rating" class="form-select">
                                                            <option value="">Please Select</option>
                                                            <option value="1">A</option>
                                                            <option value="2">B</option>
                                                            <option value="3">C</option>
                                                            <option value="4">D</option>
                                                            <option value="5">E</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="level45">
                                <div class="row">
                                    <div class="col-md-5 order-2 order-md-1">
                                        <table class="table table-sm text-center">
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
                                                <tr>
                                                    <td>A</td>
                                                    <td>1</td>
                                                    <td>10.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>B</td>
                                                    <td>1</td>
                                                    <td>10.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>C</td>
                                                    <td>1</td>
                                                    <td>10.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>D</td>
                                                    <td>1</td>
                                                    <td>20.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>E</td>
                                                    <td>1</td>
                                                    <td>50.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>Total</td>
                                                    <td>5</td>
                                                    <td>100.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md text-end order-1 order-md-2 mb-2">
                                        <button class="btn btn-outline-info m-1"><i class="ri-upload-cloud-2-line d-md-none "></i><span class="d-none d-md-block">Upload Data</span></button>
                                        <button class="btn btn-outline-success m-1"><i class="ri-download-cloud-2-line d-md-none "></i><span class="d-none d-md-block">Download Data</span></button>
                                        <button class="btn btn-primary m-1">Submit Rating</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md">
                                        <div class="card bg-light-subtle">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md col-sm-12">
                                                        <span class="text-muted">Employee Name</span>
                                                        <p class="mt-1 fw-medium">Hussein Al-Barqoni<span class="text-muted ms-1">011020040011</span></p>
                                                    </div>
                                                    <div class="col d-none d-md-block text-center">
                                                        <span class="text-muted">Job Level</span>
                                                        <p class="mt-1 fw-medium">4A</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Designation</span>
                                                        <p class="mt-1 fw-medium">Sysdev</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Unit</span>
                                                        <p class="mt-1 fw-medium">HCIS</p>
                                                    </div>
                                                    <div class="col-md col-sm-12 text-md-center">
                                                        <span class="text-muted">Review Status</span>
                                                        <div class="mb-2">
                                                            <a href="javascript:void(0)" d ata-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="" class="badge bg-success rounded-pill py-1 px-2 mt-1">Done</a>
                                                        </div>
                                                    </div>
                                                    <div class="col text-center">
                                                        <span class="text-muted">Suggested Rating</span>
                                                        <p class="mt-1 fw-medium">C</p>
                                                    </div>
                                                    <div class="col">
                                                        <span class="text-muted">Your Rating</span>
                                                        <select name="rating" id="rating" class="form-select">
                                                            <option value="">Please Select</option>
                                                            <option value="1">A</option>
                                                            <option value="2">B</option>
                                                            <option value="3">C</option>
                                                            <option value="4">D</option>
                                                            <option value="5">E</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md">
                                        <div class="card bg-light-subtle">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md col-sm-12">
                                                        <span class="text-muted">Employee Name</span>
                                                        <p class="mt-1 fw-medium">Arnold<span class="text-muted ms-1">011020040011</span></p>
                                                    </div>
                                                    <div class="col d-none d-md-block text-center">
                                                        <span class="text-muted">Job Level</span>
                                                        <p class="mt-1 fw-medium">4A</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Designation</span>
                                                        <p class="mt-1 fw-medium">Sysdev</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Unit</span>
                                                        <p class="mt-1 fw-medium">HCIS</p>
                                                    </div>
                                                    <div class="col-md col-sm-12 text-md-center">
                                                        <span class="text-muted">Review Status</span>
                                                        <div class="mb-2">
                                                            <a href="javascript:void(0)" d ata-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="" class="badge bg-warning rounded-pill py-1 px-2 mt-1">On Review</a>
                                                        </div>
                                                    </div>
                                                    <div class="col text-center">
                                                        <span class="text-muted">Suggested Rating</span>
                                                        <p class="mt-1 fw-medium">C</p>
                                                    </div>
                                                    <div class="col">
                                                        <span class="text-muted">Your Rating</span>
                                                        <select name="rating" id="rating" class="form-select">
                                                            <option value="">Please Select</option>
                                                            <option value="1">A</option>
                                                            <option value="2">B</option>
                                                            <option value="3">C</option>
                                                            <option value="4">D</option>
                                                            <option value="5">E</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md">
                                        <div class="card bg-light-subtle">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md col-sm-12">
                                                        <span class="text-muted">Employee Name</span>
                                                        <p class="mt-1 fw-medium">Franklin<span class="text-muted ms-1">011020040011</span></p>
                                                    </div>
                                                    <div class="col d-none d-md-block text-center">
                                                        <span class="text-muted">Job Level</span>
                                                        <p class="mt-1 fw-medium">4A</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Designation</span>
                                                        <p class="mt-1 fw-medium">Sysdev</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Unit</span>
                                                        <p class="mt-1 fw-medium">HCIS</p>
                                                    </div>
                                                    <div class="col-md col-sm-12 text-md-center">
                                                        <span class="text-muted">Review Status</span>
                                                        <div class="mb-2">
                                                            <a href="javascript:void(0)" d ata-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="" class="badge bg-warning rounded-pill py-1 px-2 mt-1">On Review</a>
                                                        </div>
                                                    </div>
                                                    <div class="col text-center">
                                                        <span class="text-muted">Suggested Rating</span>
                                                        <p class="mt-1 fw-medium">C</p>
                                                    </div>
                                                    <div class="col">
                                                        <span class="text-muted">Your Rating</span>
                                                        <select name="rating" id="rating" class="form-select">
                                                            <option value="">Please Select</option>
                                                            <option value="1">A</option>
                                                            <option value="2">B</option>
                                                            <option value="3">C</option>
                                                            <option value="4">D</option>
                                                            <option value="5">E</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="level67">
                                <div class="row">
                                    <div class="col-md-5 order-2 order-md-1">
                                        <table class="table table-sm text-center">
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
                                                <tr>
                                                    <td>A</td>
                                                    <td>1</td>
                                                    <td>10.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>B</td>
                                                    <td>1</td>
                                                    <td>10.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>C</td>
                                                    <td>1</td>
                                                    <td>10.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>D</td>
                                                    <td>1</td>
                                                    <td>20.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>E</td>
                                                    <td>1</td>
                                                    <td>50.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>Total</td>
                                                    <td>5</td>
                                                    <td>100.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md text-end order-1 order-md-2 mb-2">
                                        <button class="btn btn-outline-info m-1"><i class="ri-upload-cloud-2-line d-md-none "></i><span class="d-none d-md-block">Upload Data</span></button>
                                        <button class="btn btn-outline-success m-1"><i class="ri-download-cloud-2-line d-md-none "></i><span class="d-none d-md-block">Download Data</span></button>
                                        <button class="btn btn-primary m-1">Submit Rating</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md">
                                        <div class="card bg-light-subtle">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md col-sm-12">
                                                        <span class="text-muted">Employee Name</span>
                                                        <p class="mt-1 fw-medium">Robert Downey<span class="text-muted ms-1">011020040011</span></p>
                                                    </div>
                                                    <div class="col d-none d-md-block text-center">
                                                        <span class="text-muted">Job Level</span>
                                                        <p class="mt-1 fw-medium">6A</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Designation</span>
                                                        <p class="mt-1 fw-medium">Sysdev</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Unit</span>
                                                        <p class="mt-1 fw-medium">HCIS</p>
                                                    </div>
                                                    <div class="col-md col-sm-12 text-md-center">
                                                        <span class="text-muted">Review Status</span>
                                                        <div class="mb-2">
                                                            <a href="javascript:void(0)" d ata-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="" class="badge bg-success rounded-pill py-1 px-2 mt-1">Done</a>
                                                        </div>
                                                    </div>
                                                    <div class="col text-center">
                                                        <span class="text-muted">Suggested Rating</span>
                                                        <p class="mt-1 fw-medium">C</p>
                                                    </div>
                                                    <div class="col">
                                                        <span class="text-muted">Your Rating</span>
                                                        <select name="rating" id="rating" class="form-select">
                                                            <option value="">Please Select</option>
                                                            <option value="1">A</option>
                                                            <option value="2">B</option>
                                                            <option value="3">C</option>
                                                            <option value="4">D</option>
                                                            <option value="5">E</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md">
                                        <div class="card bg-light-subtle">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md col-sm-12">
                                                        <span class="text-muted">Employee Name</span>
                                                        <p class="mt-1 fw-medium">Stephanie<span class="text-muted ms-1">011020040011</span></p>
                                                    </div>
                                                    <div class="col d-none d-md-block text-center">
                                                        <span class="text-muted">Job Level</span>
                                                        <p class="mt-1 fw-medium">6A</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Designation</span>
                                                        <p class="mt-1 fw-medium">Sysdev</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Unit</span>
                                                        <p class="mt-1 fw-medium">HCIS</p>
                                                    </div>
                                                    <div class="col-md col-sm-12 text-md-center">
                                                        <span class="text-muted">Review Status</span>
                                                        <div class="mb-2">
                                                            <a href="javascript:void(0)" d ata-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="" class="badge bg-warning rounded-pill py-1 px-2 mt-1">On Review</a>
                                                        </div>
                                                    </div>
                                                    <div class="col text-center">
                                                        <span class="text-muted">Suggested Rating</span>
                                                        <p class="mt-1 fw-medium">C</p>
                                                    </div>
                                                    <div class="col">
                                                        <span class="text-muted">Your Rating</span>
                                                        <select name="rating" id="rating" class="form-select">
                                                            <option value="">Please Select</option>
                                                            <option value="1">A</option>
                                                            <option value="2">B</option>
                                                            <option value="3">C</option>
                                                            <option value="4">D</option>
                                                            <option value="5">E</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md">
                                        <div class="card bg-light-subtle">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md col-sm-12">
                                                        <span class="text-muted">Employee Name</span>
                                                        <p class="mt-1 fw-medium">Lucy<span class="text-muted ms-1">011020040011</span></p>
                                                    </div>
                                                    <div class="col d-none d-md-block text-center">
                                                        <span class="text-muted">Job Level</span>
                                                        <p class="mt-1 fw-medium">6A</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Designation</span>
                                                        <p class="mt-1 fw-medium">Sysdev</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Unit</span>
                                                        <p class="mt-1 fw-medium">HCIS</p>
                                                    </div>
                                                    <div class="col-md col-sm-12 text-md-center">
                                                        <span class="text-muted">Review Status</span>
                                                        <div class="mb-2">
                                                            <a href="javascript:void(0)" d ata-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="" class="badge bg-warning rounded-pill py-1 px-2 mt-1">On Review</a>
                                                        </div>
                                                    </div>
                                                    <div class="col text-center">
                                                        <span class="text-muted">Suggested Rating</span>
                                                        <p class="mt-1 fw-medium">C</p>
                                                    </div>
                                                    <div class="col">
                                                        <span class="text-muted">Your Rating</span>
                                                        <select name="rating" id="rating" class="form-select">
                                                            <option value="">Please Select</option>
                                                            <option value="1">A</option>
                                                            <option value="2">B</option>
                                                            <option value="3">C</option>
                                                            <option value="4">D</option>
                                                            <option value="5">E</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="level89">
                                <div class="row">
                                    <div class="col-md-5 order-2 order-md-1">
                                        <table class="table table-sm text-center">
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
                                                <tr>
                                                    <td>A</td>
                                                    <td>1</td>
                                                    <td>10.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>B</td>
                                                    <td>1</td>
                                                    <td>10.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>C</td>
                                                    <td>1</td>
                                                    <td>10.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>D</td>
                                                    <td>1</td>
                                                    <td>20.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>E</td>
                                                    <td>1</td>
                                                    <td>50.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                                <tr>
                                                    <td>Total</td>
                                                    <td>5</td>
                                                    <td>100.0%</td>
                                                    <td>0</td>
                                                    <td>0.0%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md text-end order-1 order-md-2 mb-2">
                                        <button class="btn btn-outline-info m-1"><i class="ri-upload-cloud-2-line d-md-none "></i><span class="d-none d-md-block">Upload Data</span></button>
                                        <button class="btn btn-outline-success m-1"><i class="ri-download-cloud-2-line d-md-none "></i><span class="d-none d-md-block">Download Data</span></button>
                                        <button class="btn btn-primary m-1">Submit Rating</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md">
                                        <div class="card bg-light-subtle">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md col-sm-12">
                                                        <span class="text-muted">Employee Name</span>
                                                        <p class="mt-1 fw-medium">Mark Water<span class="text-muted ms-1">011020040011</span></p>
                                                    </div>
                                                    <div class="col d-none d-md-block text-center">
                                                        <span class="text-muted">Job Level</span>
                                                        <p class="mt-1 fw-medium">8A</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Designation</span>
                                                        <p class="mt-1 fw-medium">Sysdev</p>
                                                    </div>
                                                    <div class="col d-none d-md-block">
                                                        <span class="text-muted">Unit</span>
                                                        <p class="mt-1 fw-medium">HCIS</p>
                                                    </div>
                                                    <div class="col-md col-sm-12 text-md-center">
                                                        <span class="text-muted">Review Status</span>
                                                        <div class="mb-2">
                                                            <a href="javascript:void(0)" d ata-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="" class="badge bg-success rounded-pill py-1 px-2 mt-1">Done</a>
                                                        </div>
                                                    </div>
                                                    <div class="col text-center">
                                                        <span class="text-muted">Suggested Rating</span>
                                                        <p class="mt-1 fw-medium">C</p>
                                                    </div>
                                                    <div class="col">
                                                        <span class="text-muted">Your Rating</span>
                                                        <select name="rating" id="rating" class="form-select">
                                                            <option value="">Please Select</option>
                                                            <option value="1">A</option>
                                                            <option value="2">B</option>
                                                            <option value="3">C</option>
                                                            <option value="4">D</option>
                                                            <option value="5">E</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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