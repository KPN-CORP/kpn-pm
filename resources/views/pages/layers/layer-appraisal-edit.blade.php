@extends('layouts_.vertical', ['page_title' => 'Layer Appraisal'])

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
                                            <p class="text-muted mb-1">Employee Name</p>
                                        </div>
                                        <div class="col">
                                            : Supardy
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Employee ID</p>
                                        </div>
                                        <div class="col">
                                            : 011020040011
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Join Date</p>
                                        </div>
                                        <div class="col">
                                            : 01 Jan 2024
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Business Unit</p>
                                        </div>
                                        <div class="col">
                                            : Cement
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Company</p>
                                        </div>
                                        <div class="col">
                                            : Cemindo Gemilang
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Unit</p>
                                        </div>
                                        <div class="col">
                                            : HCIS
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Designation</p>
                                        </div>
                                        <div class="col">
                                            : Sysdev
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Office Location</p>
                                        </div>
                                        <div class="col">
                                            : Head Office - Jakarta
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
                    <div class="card-body p-1 p-md-3">
                        <div class="row">
                            <div class="col">
                                <div class="card bg-secondary-subtle shadow-none">
                                    <div class="card-body">
                                        <h5>Manager</h5>
                                        <select name="manager" id="manager" class="form-select">
                                            <option value="">- Please Select -</option>
                                            <option value="Manager A">Manager A</option>
                                            <option value="Manager B">Manager B</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="card bg-secondary-subtle shadow-none">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md">
                                                <div class="mb-2">
                                                    <h5>Peers 1</h5>
                                                    <select name="peer1" id="peer1" class="form-select">
                                                        <option value="">- Please Select -</option>
                                                        <option value="Peers A">Peers A</option>
                                                        <option value="Peers B">Peers B</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md">
                                                <div class="mb-2">
                                                    <h5>Peers 2</h5>
                                                    <select name="peer2" id="peer2" class="form-select">
                                                        <option value="">- Please Select -</option>
                                                        <option value="Peers A">Peers A</option>
                                                        <option value="Peers B">Peers B</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md">
                                                <div class="mb-2">
                                                    <h5>Peers 3</h5>
                                                    <select name="peer3" id="peer3" class="form-select">
                                                        <option value="">- Please Select -</option>
                                                        <option value="Peers A">Peers A</option>
                                                        <option value="Peers B">Peers B</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="card bg-secondary-subtle shadow-none">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md">
                                                <div class="mb-2">
                                                    <h5>Subordinate 1</h5>
                                                    <select name="sub1" id="sub1" class="form-select">
                                                        <option value="">- Please Select -</option>
                                                        <option value="Subordinate A">Subordinate A</option>
                                                        <option value="Subordinate B">Subordinate B</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md">
                                                <div class="mb-2">
                                                    <h5>Subordinate 2</h5>
                                                    <select name="sub2" id="sub2" class="form-select">
                                                        <option value="">- Please Select -</option>
                                                        <option value="Subordinate A">Subordinate A</option>
                                                        <option value="Subordinate B">Subordinate B</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md">
                                                <div class="mb-2">
                                                    <h5>Subordinate 3</h5>
                                                    <select name="sub3" id="sub3" class="form-select">
                                                        <option value="">- Please Select -</option>
                                                        <option value="Subordinate A">Subordinate A</option>
                                                        <option value="Subordinate B">Subordinate B</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="card bg-secondary-subtle shadow-none">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-10">
                                                <div class="mb-2">
                                                    <h5>Calibrator 1</h5>
                                                    <select name="calibrator1" id="calibrator1" class="form-select">
                                                        <option value="">- Please Select -</option>
                                                        <option value="Calibrator A">Calibrator A</option>
                                                        <option value="Calibrator B">Calibrator B</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="calibrator-container"></div> <!-- Container for dynamic calibrators -->
                                        <div class="row">
                                            <div class="col">
                                                <button id="add-calibrator" class="btn btn-sm rounded btn-outline-primary"><i class="ri-add-line me-1 fs-16"></i>Add Calibrator</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row justify-content-end">
                            <div class="col-6 col-md-auto">
                                <button class="btn btn-outline-secondary w-100 w-md-auto">{{ __('Cancel') }}</button>
                            </div>
                            <div class="col-6 col-md-auto">
                                <button class="btn btn-primary px-3 w-100 w-md-auto">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection